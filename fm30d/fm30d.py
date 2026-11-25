#!/usr/bin/env python3
"""fm30d - FPP plugin service that runs the FM30 transmitter directly.

Replaces the ESP8266 front-end: talks to the STM32 over the Pi UART, follows
the FPP show (TX on/off, now-playing RDS with x/N counter) and serves a
small JSON API on 127.0.0.1 for the plugin's web page (proxied by fm30api.php).

  python3 fm30d.py [--config DIR] [--port /dev/serial0|socket://host:port]
"""
import argparse
import collections
import copy
import json
import os
import re
import socket
import sys
import threading
import time
import traceback
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import commands as C            # noqa: E402
import pager as P               # noqa: E402
from link import Link, LinkError  # noqa: E402
from show import Show, SHOW, INTERSTITIAL  # noqa: E402

VERSION = "0.3.2"
STATUS_PERIOD_S = 2.0
SAVE_MIN_GAP_S = 10.0
PIECE_KEYS = {"title", "artist", "short", "extra", "type", "ps_s", "rt_s", "ps_template", "rt_template",
              "ps_lines", "rt_lines", "vol"}
_REAPPLY = object()                     # forces the volume to be re-evaluated
LINE_KEYS = ("ps_lines", "rt_lines")   # custom RDS text: list of lines, tokens allowed
MAX_LINES = 20

DEFAULTS = {
    "port": "/dev/serial0",
    "http_port": 5440,
    "master": "auto",                 # "auto" (multisync player), "local", or an IP
    "fpp_local": "http://127.0.0.1",
    "tx_auto": True,                  # TX on when the show plays, off when it stops
    "tx_off_grace_s": 60,
    "rds_auto": True,                 # plugin drives PS/RT from the show
    "station_ps": "105.5 FM",         # PS tag shown between PS loops and when idle
    "station_between": True,
    "ps_uppercase": True,
    "ps_center": True,
    "ps_page_s": 2.5,
    "rt_page_s": 10.0,
    "ps_template": "{short}",
    "rt_template_show": "{count} {title} - {artist} | {extra}",
    "rt_template_inter": "{title} - {artist} | {extra}",
    "idle_ps": "{station}",
    "idle_rt": "Christmas light show - next show {next}",
    "lcd_slot1": True,                # slot 1 shows the show state
    "vol_default": -1,                # VOL between songs and for songs without their own; -1 = not set
    "lcd_msgs": {"2": ["", ""], "3": ["", ""], "4": ["", ""]},
}

LIMITS = {
    "tx_off_grace_s": (0, 3600), "ps_page_s": (1.0, 30.0), "rt_page_s": (4.0, 120.0),
    "http_port": (1024, 65535), "vol_default": (-1, 255),
}


class Store:
    """JSON file with atomic writes."""

    def __init__(self, path, default):
        self.path = path
        self.data = copy.deepcopy(default)
        try:
            with open(path, encoding="utf-8") as f:
                d = json.load(f)
            if isinstance(default, dict) and isinstance(d, dict):
                self.data.update(d)
        except FileNotFoundError:
            pass
        except Exception as ex:
            print(f"store: cannot read {path}: {ex}", flush=True)

    def save(self):
        tmp = self.path + ".tmp"
        with open(tmp, "w", encoding="utf-8") as f:
            json.dump(self.data, f, indent=1, sort_keys=True)
        os.replace(tmp, self.path)


class Daemon:
    def __init__(self, cfg_dir, port_override=None):
        os.makedirs(cfg_dir, exist_ok=True)
        self.settings_store = Store(os.path.join(cfg_dir, "fpp-fm30-settings.json"), DEFAULTS)
        self.names_store = Store(os.path.join(cfg_dir, "fpp-fm30-names.json"), {})
        self.s = self.settings_store.data
        if port_override:
            self.s["port"] = port_override
        self.logbuf = collections.deque(maxlen=300)
        self.lock = threading.RLock()
        self.fm30 = None               # last STATUS dict
        self.fm30_at = 0.0
        self.last_uptime = None
        self.now = {}
        self.layout = {"key": None, "ps": [], "rt": []}
        self.ps_i = self.rt_i = 0
        self.ps_next = self.rt_next = 0.0
        self.sent_ps = self.sent_rt = None
        self.sent_lcd1 = None
        self.was_playing = None
        self.idle_since = None
        self.tx_state = "unknown"
        self.tx_tried = 0.0
        self.vol_key = _REAPPLY          # piece whose volume was last applied
        self.vol_lock = threading.Lock()
        self.ovm_last = None             # last STM32 overmodulation counter
        self.ovm_hist = collections.deque()   # (time, new events)
        self.ovm_piece = {}              # piece key -> overmods while it played (since service start)
        self.last_save = 0.0
        self.show = Show(self.s, self.names_store.data, log=self.log)
        self.link = Link(self.s["port"], log=self.log)
        self.link.on_json = self._on_json
        self.link.on_reset = self._on_reset
        self.link.on_alive = self._on_reset

    # ------------------------------------------------------------ util
    def log(self, msg):
        line = time.strftime("%H:%M:%S ") + msg
        self.logbuf.append(line)
        print(line, flush=True)

    def send(self, line, retry=True):
        try:
            return self.link.request(line, retry=retry)
        except LinkError as ex:
            return ("err", f"ERR {ex}", "")

    # ------------------------------------------------------------ STM32 events
    def _on_json(self, line):
        try:
            d = json.loads(line)
        except ValueError:
            return
        up = d.get("uptime")
        with self.lock:
            if self.last_uptime is not None and isinstance(up, int) and up + 5 < self.last_uptime:
                self.log("STM32 uptime went backwards: reset")
                threading.Thread(target=self._on_reset, daemon=True).start()
            self.last_uptime = up
            ov = d.get("ovm")
            if isinstance(ov, int):
                if self.ovm_last is not None:
                    new = ov - self.ovm_last if ov >= self.ovm_last else ov    # counter restarts with the STM32
                    if new > 0:
                        self.ovm_hist.append((time.time(), new))
                        key = self.now.get("key") if self.now.get("playing") else None
                        if key:
                            self.ovm_piece[key] = self.ovm_piece.get(key, 0) + new
                self.ovm_last = ov
            while self.ovm_hist and time.time() - self.ovm_hist[0][0] > 60:
                self.ovm_hist.popleft()
            self.fm30 = d
            self.fm30_at = time.time()

    def _on_reset(self):
        """STM32 rebooted or port reopened: it keeps only SAVEd settings."""
        time.sleep(0.3)
        with self.lock:
            self.sent_ps = self.sent_rt = self.sent_lcd1 = None
            self.ps_next = self.rt_next = 0.0
            msgs = dict(self.s.get("lcd_msgs") or {})
            if self.s.get("tx_auto") and self.was_playing and self.tx_state == "on":
                self.tx_state = "error"          # STM32 came back with its SAVEd TX state: re-assert
                self.tx_tried = 0.0
            self.vol_key = _REAPPLY              # ... and its SAVEd volume
        for slot, (top, bottom) in sorted(msgs.items()):
            if not (top or bottom):
                continue                 # a reset STM32 starts with empty slots
            try:
                self.send(C.build_lcd_msg(int(slot), top, bottom))
            except C.CommandError:
                pass

    # ------------------------------------------------------------ loops
    def status_loop(self):
        while True:
            alive = self.link.alive()
            if self.link.connected and not self.link.pending_status():
                try:
                    self.link.submit("STATUS", retry=False)
                except LinkError:
                    pass
            time.sleep(STATUS_PERIOD_S if alive else 5.0)   # slow probe while nothing answers

    def show_loop(self):
        last_poll = 0.0
        while True:
            try:
                t = time.monotonic()
                if t - last_poll >= 1.0:
                    last_poll = t
                    now = self.show.now()
                    with self.lock:
                        self.now = now
                    self._tx_auto(now)
                    self._vol_auto(now)
                    self._relayout(now)
                    self._lcd1(now)
                self._pager_tick()
            except Exception:
                self.log("show loop error: " + traceback.format_exc().splitlines()[-1])
            time.sleep(0.1)

    # ------------------------------------------------------------ TX with the show
    def _tx_auto(self, now):
        playing = bool(now.get("playing"))
        if now.get("error") and not playing:
            return                      # can't see the show: don't switch anything
        if not self.s.get("tx_auto"):
            self.was_playing = playing
            return
        if not self.link.alive():
            return                      # act on the show state once the STM32 answers
        if playing:
            self.idle_since = None
            retry = self.tx_state == "error" and time.monotonic() - self.tx_tried >= 5.0
            if self.was_playing is not True or retry:
                self.log("show playing: TX ON" + (" (retry)" if retry else ""))
                self.tx_tried = time.monotonic()
                r = self.send("TX ON")
                self.tx_state = "on" if r[0] == "ok" else "error"
        else:
            if self.was_playing is not False and self.idle_since is None:
                self.idle_since = time.monotonic()
            grace = float(self.s.get("tx_off_grace_s", 60))
            if self.idle_since is not None and time.monotonic() - self.idle_since >= grace:
                self.idle_since = None
                if self.tx_state != "off":
                    self.log("show stopped: TX OFF")
                    r = self.send("TX OFF")
                    self.tx_state = "off" if r[0] == "ok" else "error"
        self.was_playing = playing

    # ------------------------------------------------------------ volume per piece
    def piece_vol(self, key):
        v = (self.names_store.data.get(key) or {}).get("vol") if key else None
        return int(v) if isinstance(v, (int, float)) else None

    def _vol_auto(self, now):
        """Slow path (1 s show poll): covers idle, errors and anything the
        fast watcher missed."""
        if now.get("error") and not now.get("playing"):
            return
        self._vol_for(now.get("key") if now.get("playing") else None)

    def vol_watch(self):
        """Fast path: watch this FPP's own status (cheap, local) so the volume
        changes within ~0.1 s of the next song starting instead of up to 1 s.
        Polls every 0.5 s, and every 0.1 s in the last 3 s of a song."""
        last = None
        while True:
            delay = 0.5
            try:
                st = self.show.fetch(self.show.local() + "/api/fppd/status", timeout=1.0)
                if st.get("status_name") == "playing":
                    key = st.get("current_song") or st.get("current_sequence") or None
                    if key and key != last:
                        last = key
                        self._vol_for(key)
                    rem = st.get("seconds_remaining")
                    if rem is not None and str(rem).isdigit() and int(rem) <= 3:
                        delay = 0.1
                else:
                    last = None
            except Exception:
                delay = 1.0
            time.sleep(delay)

    def _vol_for(self, key):
        """VOL follows the piece: its own volume, else the default.

        Nothing is sent until a piece has its own volume or a default is set,
        so the transmitter's volume is left alone unless this is used. The
        first time a piece has its own volume and no default is set, the
        transmitter's current volume becomes the default (shown in Settings).
        """
        if not self.link.alive():
            return
        with self.vol_lock:
            if key == self.vol_key:
                return
            want = self.piece_vol(key)
            base = int(self.s.get("vol_default", -1))
            cur = (self.fm30 or {}).get("vol")
            if want is not None and base < 0:
                if cur is None:
                    return                          # wait for the first STATUS
                with self.lock:
                    self.s["vol_default"] = base = int(cur)
                    self.settings_store.save()
                self.log(f"volume: default set to the transmitter's current {base}")
            if want is None:
                want = base if base >= 0 else None
            if want is None or want == cur:
                self.vol_key = key
                return
            r = self.send(f"VOL {want}")
            if r[0] == "ok":
                self.log(f"volume {want} for {key or 'idle'}")
                self.vol_key = key
                if self.fm30 is not None:
                    self.fm30["vol"] = want

    # ------------------------------------------------------------ RDS layout
    def piece_layout(self, key, host, n, N, typ, playing=True, nxt=""):
        """-> {"ps", "rt", "ps_s", "rt_s", "auto_ps", "auto_rt"}.

        A piece with custom lines (ps_lines / rt_lines) uses them as-is: each
        line is rendered (tokens such as {count} stay live) and split to fit.
        auto_ps / auto_rt are the lines the templates would give, with the
        tag text filled in but {count} {station} {next} kept as tokens: the
        editor's starting point for custom text.
        """
        s = self.s
        o = self.names_store.data.get(key) or {}
        f = self.show.fields(host, key) if key else {}
        fields = dict(f)
        fields.update({"n": n or "", "N": N or "", "count": P.counter(n, N),
                       "station": s.get("station_ps", ""), "next": nxt})
        up, center = s.get("ps_uppercase", True), s.get("ps_center", True)
        live = playing and key
        if not live:
            ps_t, rt_t = s.get("idle_ps", "{station}"), s.get("idle_rt", "")
        else:
            ps_t = o.get("ps_template") or s.get("ps_template", "{short}")
            rt_t = o.get("rt_template") or (s.get("rt_template_show") if typ == SHOW else s.get("rt_template_inter"))
        between = bool(live and s.get("station_between") and s.get("station_ps"))

        # automatic lines (templates), as editable text
        keep = dict(fields, count="{count}" if fields["count"] else "", station="{station}", next="{next}")
        ps_auto_text = P.render(ps_t, fields)
        auto_ps = [p.strip() for p in P.ps_pages(ps_auto_text, up, False) if p.strip()] if ps_auto_text else []
        if between and auto_ps:
            auto_ps.append("{station}")
        auto_rt = [P.render(rt_t, keep)] if P.render(rt_t, keep) else []

        if live and o.get("ps_lines"):
            ps = []
            for line in o["ps_lines"]:
                txt = P.render(line, fields)
                if txt:
                    ps += P.ps_pages(txt, up, center)
        else:
            ps_text = P.render(ps_t, fields)
            ps = P.ps_pages(ps_text, up, center) if ps_text else []
            station = P.ps_pages(s.get("station_ps", ""), False, center)[0] if s.get("station_ps") else ""
            if between and station and ps and ps != [station]:
                ps = ps + [station]
        if not ps:
            ps = [P.ps_pages(s["station_ps"], False, center)[0] if s.get("station_ps") else "FM30"]
        if live and o.get("rt_lines"):
            rt = []
            for line in o["rt_lines"]:
                if P.render(line, fields):
                    rt += P.rt_pages(line, fields)
            rt = rt or P.rt_pages(rt_t, fields)
        else:
            rt = P.rt_pages(rt_t, fields)
        return {"ps": ps, "rt": rt,
                "ps_s": float(o.get("ps_s") or s.get("ps_page_s", 2.5)),
                "rt_s": float(o.get("rt_s") or s.get("rt_page_s", 10)),
                "auto_ps": auto_ps, "auto_rt": auto_rt}

    def _relayout(self, now):
        if not self.s.get("rds_auto"):
            return
        playing = bool(now.get("playing"))
        key = now.get("key") if playing else None
        lay = self.piece_layout(key, now.get("source"), now.get("n"), now.get("N"), now.get("type"),
                                playing, now.get("next", ""))
        sig = (key, tuple(lay["ps"]), tuple(lay["rt"]))
        with self.lock:
            if sig != self.layout.get("sig"):
                lay["sig"] = sig
                lay["key"] = key
                self.layout = lay
                self.ps_i = self.rt_i = 0
                self.ps_next = self.rt_next = 0.0

    def _pager_tick(self):
        if not self.s.get("rds_auto") or not self.link.alive():
            return
        t = time.monotonic()
        with self.lock:
            lay = self.layout
            ps = rt = None
            if lay.get("ps") and t >= self.ps_next:
                ps = lay["ps"][self.ps_i % len(lay["ps"])]
                self.ps_i += 1
                self.ps_next = t + lay["ps_s"]
            if lay.get("rt") and t >= self.rt_next:
                rt = lay["rt"][self.rt_i % len(lay["rt"])]
                self.rt_i += 1
                self.rt_next = t + lay["rt_s"]
        if ps is not None and ps != self.sent_ps:
            if self.send(C.build_ps(ps), retry=False)[0] == "ok":
                self.sent_ps = ps
        if rt is not None and rt != self.sent_rt:
            if self.send(C.build_rt(rt), retry=False)[0] == "ok":
                self.sent_rt = rt

    def _lcd1(self, now):
        if not self.s.get("lcd_slot1"):
            return
        if now.get("playing") and now.get("key"):
            f = self.show.fields(now.get("source"), now["key"])
            cnt = P.counter(now.get("n"), now.get("N"))
            msg = (cnt or "SHOW", f.get("title", ""))
        else:
            msg = ("FPP", "idle " + local_ip())
        if msg != self.sent_lcd1 and self.link.alive():
            if self.send(C.build_lcd_msg(1, *msg), retry=False)[0] == "ok":
                self.sent_lcd1 = msg

    # ------------------------------------------------------------ API actions
    def api_cmd(self, text):
        line = C.parse_command(text)
        w = C.command_word(line)
        if w in ("PS", "RT") and self.s.get("rds_auto"):
            raise C.CommandError("RDS automation is on: turn it off in Settings to set PS/RT by hand")
        if w == "LCD" and line.startswith("LCD MSG 1") and self.s.get("lcd_slot1"):
            raise C.CommandError("LCD slot 1 belongs to the plugin (show state); use slots 2-4")
        if w == "SAVE":
            if time.time() - self.last_save < SAVE_MIN_GAP_S:
                raise C.CommandError("SAVE at most once per 10 s")
            self.last_save = time.time()
            r = self.send(line, retry=False)
        else:
            r = self.send(line)
        if w == "VOL" and r[0] == "ok":
            key = self.now.get("key") if self.now.get("playing") else None
            if self.piece_vol(key) is None:
                with self.lock:
                    self.s["vol_default"] = int(line.split()[1])
                    self.settings_store.save()
        if w == "TX" and r[0] == "ok":
            self.tx_state = "on" if line.endswith("ON") else "off"
        if w == "LCD" and line.startswith("LCD MSG ") and r[0] == "ok":
            m = re.match(r"LCD MSG (\d)(?: (.*))?$", line)
            slot, text = m.group(1), m.group(2) or ""
            top, _, bottom = text.rpartition("|") if "|" in text else ("", "", text)
            with self.lock:
                self.s.setdefault("lcd_msgs", {})[slot] = [top, bottom]
                self.settings_store.save()
        return {"ok": r[0] == "ok", "outcome": r[0], "sent": line, "reply": r[1], "body": r[2]}

    def api_status(self):
        with self.lock:
            lay = self.layout
            ps_cur = lay["ps"][(self.ps_i - 1) % len(lay["ps"])] if lay.get("ps") and self.ps_i else self.sent_ps
            rt_cur = lay["rt"][(self.rt_i - 1) % len(lay["rt"])] if lay.get("rt") and self.rt_i else self.sent_rt
            return {
                "version": VERSION,
                "link": {"connected": self.link.connected, "port": self.s["port"],
                         "stats": dict(self.link.stats),
                         "last_rx_age": round(time.time() - self.link.last_rx, 1) if self.link.last_rx else None},
                "fm30": self.fm30, "fm30_age": round(time.time() - self.fm30_at, 1) if self.fm30_at else None,
                "show": self.now, "tx_auto_state": self.tx_state,
                "ovm": {"total": self.ovm_last, "last_min": sum(n for _, n in self.ovm_hist),
                        "piece": self.ovm_piece.get(self.now.get("key"), 0) if self.now.get("playing") else None,
                        "by_piece": dict(self.ovm_piece)},
                "idle_off_in": (max(0, round(float(self.s.get("tx_off_grace_s", 60)) - (time.monotonic() - self.idle_since)))
                                if self.idle_since else None),
                "rds": {"auto": self.s.get("rds_auto"), "ps_pages": lay.get("ps", []), "rt_pages": lay.get("rt", []),
                        "ps_now": ps_cur, "rt_now": rt_cur, "ps_s": lay.get("ps_s"), "rt_s": lay.get("rt_s")},
            }

    def api_settings_put(self, d):
        if not isinstance(d, dict):
            raise C.CommandError("settings must be an object")
        with self.lock:
            for k, v in d.items():
                if k not in DEFAULTS or k in ("lcd_msgs",):
                    raise C.CommandError(f"unknown setting {k}")
                dv = DEFAULTS[k]
                if isinstance(dv, bool):
                    v = bool(v)
                elif isinstance(dv, (int, float)):
                    try:
                        v = type(dv)(v)
                    except (TypeError, ValueError):
                        raise C.CommandError(f"{k} must be a number")
                    lo, hi = LIMITS.get(k, (None, None))
                    if lo is not None and not lo <= v <= hi:
                        raise C.CommandError(f"{k} {lo}..{hi}")
                else:
                    v = str(v)
                    if len(v) > 200 or any(ord(c) < 0x20 for c in v):
                        raise C.CommandError(f"{k}: text too long or has control characters")
                self.s[k] = v
            self.settings_store.save()
            self.layout = {"key": None, "ps": [], "rt": []}    # force relayout
            self.vol_key = _REAPPLY
        return {"ok": True, "settings": self.s, "restart_needed": any(k in d for k in ("port", "http_port"))}

    def api_piece_put(self, key, d):
        if not key or len(key) > 255:
            raise C.CommandError("bad piece key")
        if not isinstance(d, dict):
            raise C.CommandError("piece must be an object")
        clean = {}
        for k, v in d.items():
            if k not in PIECE_KEYS:
                raise C.CommandError(f"unknown field {k}")
            if v in (None, ""):
                continue
            if k == "type":
                if v not in (SHOW, INTERSTITIAL):
                    raise C.CommandError("type must be show or interstitial")
            elif k in LINE_KEYS:
                if isinstance(v, str):
                    v = v.split("\n")
                if not isinstance(v, list) or len(v) > MAX_LINES:
                    raise C.CommandError(f"{k}: up to {MAX_LINES} lines")
                lines = []
                for line in v:
                    line = str(line).strip()
                    if len(line) > 200 or any(ord(c) < 0x20 for c in line):
                        raise C.CommandError(f"{k}: line too long or has control characters")
                    if line:
                        lines.append(line)
                if not lines:
                    continue
                v = lines
            elif k == "vol":
                try:
                    v = int(v)
                except (TypeError, ValueError):
                    raise C.CommandError("vol must be a number")
                if not 0 <= v <= 255:
                    raise C.CommandError("vol 0..255")
            elif k in ("ps_s", "rt_s"):
                lo, hi = LIMITS["ps_page_s" if k == "ps_s" else "rt_page_s"]
                try:
                    v = float(v)
                except (TypeError, ValueError):
                    raise C.CommandError(f"{k} must be a number")
                if not lo <= v <= hi:
                    raise C.CommandError(f"{k} {lo}..{hi}")
            else:
                v = str(v)
                if len(v) > 200 or any(ord(c) < 0x20 for c in v):
                    raise C.CommandError(f"{k}: text too long or has control characters")
            clean[k] = v
        with self.lock:
            if clean:
                self.names_store.data[key] = clean
            else:
                self.names_store.data.pop(key, None)
            self.names_store.save()
            self.layout = {"key": None, "ps": [], "rt": []}
            self.vol_key = _REAPPLY
        return {"ok": True, "key": key, "names": clean}

    def api_preview(self, d):
        """PS/RT pages for one piece as it would run in a given playlist."""
        key = d.get("key")
        plname = d.get("playlist")
        data = self.show.pieces()
        host = data["host"]
        n = N = 0
        typ = SHOW
        if plname in data["playlists"]:
            pl = data["playlists"][plname]
            N = pl["N"]
            for o in pl["order"]:
                if o["key"] == key:
                    n, typ = o["n"], o["type"]
                    break
        override = d.get("names")
        saved = self.names_store.data.get(key)
        try:
            if isinstance(override, dict):          # unsaved edits from the page
                self.names_store.data[key] = {k: v for k, v in override.items() if k in PIECE_KEYS and v not in (None, "", [])}
                if override.get("type") in (SHOW, INTERSTITIAL):
                    typ = override["type"]
                    if typ != SHOW:
                        n = 0
            lay = self.piece_layout(key, host, n, N, typ, True, "")
        finally:
            if saved is None:
                self.names_store.data.pop(key, None)
            else:
                self.names_store.data[key] = saved
        return {"key": key, "playlist": plname, "n": n, "N": N, "type": typ, **lay}


# ---------------------------------------------------------------- HTTP
def local_ip():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("192.0.2.1", 9))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except OSError:
        return "?"


def make_handler(d):
    class H(BaseHTTPRequestHandler):
        server_version = "fm30d/" + VERSION

        def log_message(self, *a):
            pass

        def _json(self, code, obj):
            body = json.dumps(obj).encode()
            self.send_response(code)
            self.send_header("Content-Type", "application/json")
            self.send_header("Content-Length", str(len(body)))
            self.send_header("Cache-Control", "no-store")
            self.end_headers()
            self.wfile.write(body)

        def _body(self):
            n = int(self.headers.get("Content-Length") or 0)
            if n > 65536:
                raise C.CommandError("request too large")
            raw = self.rfile.read(n) if n else b""
            return json.loads(raw.decode("utf-8")) if raw else {}

        def _route(self, method):
            path = self.path.split("?", 1)[0].rstrip("/")
            try:
                if method == "GET" and path == "/api/status":
                    return self._json(200, d.api_status())
                if method == "GET" and path == "/api/settings":
                    return self._json(200, {"settings": d.s, "defaults": DEFAULTS})
                if method == "PUT" and path == "/api/settings":
                    return self._json(200, d.api_settings_put(self._body()))
                if method == "GET" and path == "/api/pieces":
                    return self._json(200, d.show.pieces())
                if method == "PUT" and path == "/api/piece":
                    b = self._body()
                    return self._json(200, d.api_piece_put(b.get("key"), b.get("names") or {}))
                if method == "POST" and path == "/api/preview":
                    return self._json(200, d.api_preview(self._body()))
                if method == "POST" and path == "/api/cmd":
                    return self._json(200, d.api_cmd(self._body().get("cmd", "")))
                if method == "GET" and path == "/api/log":
                    return self._json(200, {"log": list(d.logbuf)})
                return self._json(404, {"ok": False, "error": "not found"})
            except C.CommandError as ex:
                return self._json(400, {"ok": False, "error": str(ex)})
            except ValueError as ex:
                return self._json(400, {"ok": False, "error": f"bad request: {ex}"})
            except Exception as ex:
                d.log("api error: " + traceback.format_exc().splitlines()[-1])
                return self._json(500, {"ok": False, "error": str(ex)})

        def do_GET(self):
            self._route("GET")

        def do_PUT(self):
            self._route("PUT")

        def do_POST(self):
            self._route("POST")

    return H


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--config", default="/home/fpp/media/config")
    ap.add_argument("--port", help="override the serial port (or socket://host:port)")
    ap.add_argument("--http-port", type=int)
    a = ap.parse_args()
    d = Daemon(a.config, a.port)
    if a.http_port:
        d.s["http_port"] = a.http_port
    d.log(f"fm30d {VERSION} starting, port {d.s['port']}")
    d.link.start()
    threading.Thread(target=d.status_loop, daemon=True).start()
    threading.Thread(target=d.show_loop, daemon=True).start()
    threading.Thread(target=d.vol_watch, daemon=True).start()
    srv = ThreadingHTTPServer(("127.0.0.1", int(d.s["http_port"])), make_handler(d))
    srv.daemon_threads = True
    srv.serve_forever()


if __name__ == "__main__":
    main()
