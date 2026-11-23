"""Nothing answers on the port (STM32 not wired yet): stay quiet, send no RDS/TX."""
import os, socket, sys, tempfile, threading, time, unittest
HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(HERE, "..", "fm30d"))
sys.path.insert(0, HERE)
import fm30d                      # noqa: E402
from test_units import fake_fpp   # noqa: E402


class Silent(unittest.TestCase):
    def test_silent_port(self):
        srv = socket.socket(); srv.bind(("127.0.0.1", 0)); srv.listen(1)
        port = srv.getsockname()[1]
        got = bytearray()

        def sink():
            c, _ = srv.accept()
            c.settimeout(0.2)
            end = time.time() + 6
            while time.time() < end:
                try:
                    d = c.recv(4096)
                    if not d: break
                    got.extend(d)
                except socket.timeout:
                    pass
            c.close()
        threading.Thread(target=sink, daemon=True).start()
        with tempfile.TemporaryDirectory() as tmp:
            d = fm30d.Daemon(tmp, f"socket://127.0.0.1:{port}")
            d.s.update({"fpp_local": "http://local"})
            local = {"mode_name": "remote", "status_name": "playing", "current_song": "DHN -BiCOS.wav"}
            master = {"mode_name": "player", "status_name": "playing", "current_song": "DHN -BiCOS.wav",
                      "current_playlist": {"playlist": "Show", "index": "3"}}
            d.show.fetch = fake_fpp(local, master)
            d.link.start()
            threading.Thread(target=d.status_loop, daemon=True).start()
            threading.Thread(target=d.show_loop, daemon=True).start()
            time.sleep(5.5)
            self.assertFalse(d.link.alive())
            sent = got.decode()
            self.assertIn("STATUS", sent)
            for bad in ("TX ON", "PS ", "RT ", "LCD MSG"):
                self.assertNotIn(bad, sent, f"sent {bad!r} to a silent port")
            self.assertLessEqual(sent.count("STATUS"), 4)
            self.assertFalse(any("timeout" in l for l in d.logbuf), list(d.logbuf))
            d.link.stop()


if __name__ == "__main__":
    unittest.main()
