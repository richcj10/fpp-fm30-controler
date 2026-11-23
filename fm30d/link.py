"""Serial link to the FM30 STM32 (port of FM30-Controller Link.cpp).

One command at a time: wait for the reply (500 ms, optional single retry)
before sending the next. The STM32 echoes the command word ("OK FREQ 98.1",
"OK SAVED", bare "OK" for VTSCALE/HELP/DACDBG); a reply that doesn't match
the command in flight is dropped as stale. JSON lines are STATUS output.

Transports: a device path (/dev/serial0, raw 115200 8N1 via termios) or
"socket://host:port" (used by the tests with tests/mock_stm32.py --tcp).
"""
import collections
import os
import select
import socket
import threading
import time

from commands import MAX_LINE, expected_reply_word, command_word

TIMEOUT_S = 0.5
GAP_S = 0.005
QUEUE_LEN = 10
RX_MAX = 1024
REOPEN_S = 5.0
ALIVE_S = 6.0


class LinkError(Exception):
    pass


class _Tcp:
    def __init__(self, url):
        host, _, port = url[len("socket://"):].rpartition(":")
        self.s = socket.create_connection((host or "127.0.0.1", int(port)), timeout=3)
        self.s.setblocking(False)

    def fileno(self):
        return self.s.fileno()

    def write(self, data):
        self.s.sendall(data)

    def read(self):
        try:
            d = self.s.recv(4096)
        except BlockingIOError:
            return b""
        if not d:
            raise OSError("connection closed")
        return d

    def close(self):
        self.s.close()


class _Uart:
    def __init__(self, path, baud=115200):
        import termios  # Linux only; imported here so the tests run on Windows
        self.fd = os.open(path, os.O_RDWR | os.O_NOCTTY | os.O_NONBLOCK)
        a = termios.tcgetattr(self.fd)
        speed = getattr(termios, f"B{baud}")
        a[0] = 0                                     # iflag: raw
        a[1] = 0                                     # oflag
        a[2] = termios.CS8 | termios.CREAD | termios.CLOCAL   # 8N1, no flow control
        a[3] = 0                                     # lflag: no echo/canonical
        a[4] = a[5] = speed
        a[6][termios.VMIN] = 0
        a[6][termios.VTIME] = 0
        termios.tcsetattr(self.fd, termios.TCSANOW, a)
        termios.tcflush(self.fd, termios.TCIOFLUSH)

    def fileno(self):
        return self.fd

    def write(self, data):
        while data:
            try:
                n = os.write(self.fd, data)
                data = data[n:]
            except BlockingIOError:
                select.select([], [self.fd], [], 0.1)

    def read(self):
        try:
            return os.read(self.fd, 4096)
        except BlockingIOError:
            return b""

    def close(self):
        os.close(self.fd)


def open_transport(port):
    if port.startswith("socket://"):
        return _Tcp(port)
    return _Uart(port)


class _Entry:
    __slots__ = ("line", "expect", "retry", "tries", "done", "result", "body")

    def __init__(self, line, retry):
        self.line = line
        self.expect = expected_reply_word(line)
        self.retry = retry
        self.tries = 0
        self.done = threading.Event()
        self.result = None      # ("ok"|"err"|"timeout", reply line, body)
        self.body = []


class Link:
    def __init__(self, port, log=print):
        self.port = port
        self.log = log
        self.on_json = None          # callback(str)
        self.on_reset = None         # callback() after STM32 reset detected
        self.on_connect = None       # callback() after the port (re)opens
        self.stats = collections.Counter()
        self.connected = False
        self.last_rx = 0.0
        self._last_rx_mono = 0.0
        self._was_alive = False
        self.on_alive = None         # callback() when the STM32 starts answering again
        self._t = None
        self._q = collections.deque()
        self._lock = threading.Lock()
        self._wake = threading.Event()
        self._stop = False
        self._inflight = None
        self._sent_at = 0.0
        self._done_at = 0.0
        self._rx = bytearray()
        self._rx_overflow = False
        self._thread = threading.Thread(target=self._run, name="fm30-link", daemon=True)

    # ------------------------------------------------------------ public
    def start(self):
        self._thread.start()

    def stop(self):
        self._stop = True
        self._wake.set()
        self._thread.join(2)

    def submit(self, line, retry=True):
        """Queue a validated line; returns the entry (wait on entry.done)."""
        if not line or len(line) > MAX_LINE or any(not 0x20 <= ord(c) < 0x7F for c in line):
            raise LinkError("bad command line")
        e = _Entry(line, retry)
        with self._lock:
            if len(self._q) >= QUEUE_LEN:
                self.stats["dropped"] += 1
                raise LinkError("queue full")
            self._q.append(e)
        self._wake.set()
        return e

    def request(self, line, retry=True, timeout=3.0):
        e = self.submit(line, retry)
        if not e.done.wait(timeout):
            return ("timeout", "", "")
        return e.result

    def alive(self):
        """Port open and the STM32 answered within the last few seconds."""
        a = self.connected and time.monotonic() - self._last_rx_mono < ALIVE_S
        if self._was_alive and not a:
            self._was_alive = False
            self.log("link: STM32 not responding (is it wired/powered?); probing every few seconds")
        return a

    def pending_status(self):
        with self._lock:
            return any(e.expect is None for e in self._q) or (
                self._inflight is not None and self._inflight.expect is None)

    # ------------------------------------------------------------ worker
    def _run(self):
        while not self._stop:
            try:
                self._t = open_transport(self.port)
            except Exception as ex:
                if self.connected or not self.stats["open_fail"]:
                    self.log(f"link: cannot open {self.port}: {ex}")
                self.connected = False
                self.stats["open_fail"] += 1
                self._fail_all("link down")
                time.sleep(REOPEN_S)
                continue
            self.connected = True
            self.log(f"link: opened {self.port}")
            if self.on_connect:
                threading.Thread(target=self.on_connect, daemon=True).start()
            try:
                self._loop()
            except Exception as ex:
                self.log(f"link: error {ex}")
            finally:
                self.connected = False
                try:
                    self._t.close()
                except Exception:
                    pass
                with self._lock:
                    if self._inflight:
                        self._finish("timeout", "")
                self._fail_all("link down")
            if not self._stop:
                time.sleep(1)

    def _fail_all(self, why):
        with self._lock:
            while self._q:
                e = self._q.popleft()
                e.result = ("err", f"ERR {why}", "")
                e.done.set()

    def _loop(self):
        fd = self._t.fileno()
        while not self._stop:
            r, _, _ = select.select([fd], [], [], 0.02)
            if r:
                data = self._t.read()
                for b in data:
                    self._rx_byte(b)
            now = time.monotonic()
            with self._lock:
                e = self._inflight
                if e and now - self._sent_at >= TIMEOUT_S:
                    quiet = not self._was_alive          # don't flood the log while nothing answers
                    if e.retry and e.tries < 2:
                        self.stats["retries"] += 1
                        if not quiet:
                            self.log(f"link: timeout, retrying: {e.line}")
                        self._send(e)
                    else:
                        self.stats["timeouts"] += 1
                        if not quiet:
                            self.log(f"link: timeout: {e.line}")
                        self._finish("timeout", "")
                if self._inflight is None and self._q and now - self._done_at >= GAP_S:
                    self._inflight = self._q.popleft()
                    self._send(self._inflight)

    def _send(self, e):
        self._t.write(e.line.encode("ascii") + b"\n")
        e.tries += 1
        e.body = []
        self._sent_at = time.monotonic()
        self.stats["sent"] += 1

    def _finish(self, outcome, line):
        e = self._inflight
        self._inflight = None
        self._done_at = time.monotonic()
        e.result = (outcome, line, "\n".join(e.body))
        e.done.set()

    def _rx_byte(self, b):
        if b in (13, 10):
            if self._rx_overflow:
                self.stats["overflows"] += 1
                self.log("link: rx line too long, dropped")
            elif self._rx:
                self._line(self._rx.decode("ascii"))
            self._rx = bytearray()
            self._rx_overflow = False
        elif 0x20 <= b < 0x7F:                    # noise while the STM32 resets is dropped
            if len(self._rx) < RX_MAX - 1:
                self._rx.append(b)
            else:
                self._rx_overflow = True

    def _line(self, line):
        self.last_rx = time.time()
        self._last_rx_mono = time.monotonic()
        if not self._was_alive:
            self._was_alive = True
            self.log("link: STM32 answering")
            if self.on_alive:
                threading.Thread(target=self.on_alive, daemon=True).start()
        with self._lock:
            e = self._inflight
            if line.startswith("{"):
                if self.on_json:
                    self.on_json(line)
                if e and e.expect is None:
                    self._finish("ok", line)
                return
            word = line.split(" ", 1)[0]
            if word in ("OK", "ERR"):
                if e is None:
                    self.stats["stale"] += 1
                    return
                if word == "ERR":
                    self.stats["errors"] += 1
                    self.log(f"link: {e.line} -> {line}")
                    self._finish("err", line)
                    return
                echoed = line[2:].lstrip(" ").split(" ", 1)[0]
                if e.expect is not None and echoed == e.expect:
                    self._finish("ok", line)
                else:
                    self.stats["stale"] += 1
                    self.log(f"link: stale reply for '{e.line}', ignored: {line}")
                return
            if line.startswith("fm30ctl ") and line.endswith(" ready"):
                self.log(f"link: STM32 reset ({line})")
                self.stats["resets"] += 1
                if e:   # whatever was in flight was probably lost; retry soon
                    self._sent_at = time.monotonic() - TIMEOUT_S + 0.1
                if self.on_reset:
                    threading.Thread(target=self.on_reset, daemon=True).start()
                return
            if line.startswith(("settings:", "clock:")):
                self.log(f"link: STM32 {line}")
                return
            if e and command_word(e.line) in ("HELP", "DACDBG"):
                e.body.append(line)
                return
        self.log(f"link: unexpected line: {line[:80]}")
