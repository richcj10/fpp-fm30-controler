"""fm30d end to end against tests/mock_stm32.py (TCP) and a fake FPP."""
import json
import os
import socket
import subprocess
import sys
import tempfile
import threading
import time
import unittest
import urllib.request

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(HERE, "..", "fm30d"))
sys.path.insert(0, HERE)

import fm30d                       # noqa: E402
from test_units import fake_fpp    # noqa: E402


def free_port():
    s = socket.socket()
    s.bind(("127.0.0.1", 0))
    p = s.getsockname()[1]
    s.close()
    return p


def wait(pred, timeout=8.0):
    end = time.time() + timeout
    while time.time() < end:
        v = pred()
        if v:
            return v
        time.sleep(0.1)
    return None


class EndToEnd(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.mport = free_port()
        cls.mock = subprocess.Popen([sys.executable, "-I", os.path.join(HERE, "mock_stm32.py"), "--tcp", str(cls.mport)],
                                    stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        time.sleep(0.8)
        cls.tmp = tempfile.TemporaryDirectory()
        cls.d = d = fm30d.Daemon(cls.tmp.name, f"socket://127.0.0.1:{cls.mport}")
        d.s.update({"fpp_local": "http://local", "tx_off_grace_s": 1, "ps_page_s": 1.0, "rt_page_s": 4.0,
                     "car_mode": False})
        cls.state = {"playing": True}

        def fetch(url, timeout=3.0):
            song = cls.state.get("song", "DHN -BiCOS.wav")
            st = "playing" if cls.state["playing"] else "idle"
            local = {"mode_name": "remote", "status_name": st, "current_playlist": None, "current_song": song if st == "playing" else ""}
            master = {"mode_name": "player", "status_name": st, "current_song": local["current_song"],
                      "current_playlist": {"playlist": "Show", "index": "3", "count": "7"} if st == "playing" else None}
            return fake_fpp(local, master)(url, timeout)

        d.show.fetch = fetch
        d.link.start()
        threading.Thread(target=d.status_loop, daemon=True).start()
        threading.Thread(target=d.show_loop, daemon=True).start()
        threading.Thread(target=d.vol_watch, daemon=True).start()
        cls.hport = free_port()
        cls.srv = fm30d.ThreadingHTTPServer(("127.0.0.1", cls.hport), fm30d.make_handler(d))
        threading.Thread(target=cls.srv.serve_forever, daemon=True).start()

    @classmethod
    def tearDownClass(cls):
        cls.srv.shutdown()
        cls.d.link.stop()
        cls.mock.terminate()
        cls.mock.wait(5)
        cls.tmp.cleanup()

    def api(self, method, path, body=None):
        req = urllib.request.Request(f"http://127.0.0.1:{self.hport}{path}", method=method,
                                     data=json.dumps(body).encode() if body is not None else None,
                                     headers={"Content-Type": "application/json"})
        try:
            with urllib.request.urlopen(req, timeout=5) as r:
                return r.status, json.loads(r.read())
        except urllib.error.HTTPError as e:
            return e.code, json.loads(e.read())

    def fm30(self):
        return self.api("GET", "/api/status")[1].get("fm30") or {}

    def test_1_show_drives_tx_and_rds(self):
        self.assertTrue(wait(lambda: self.fm30().get("tx") == 1), "TX not switched on by the show")
        self.assertTrue(wait(lambda: self.fm30().get("rt", "").startswith("2/4 Baby, It's Cold Outside")),
                        f"RT not set: {self.fm30().get('rt')}")
        seen = set()
        wait(lambda: seen.add(self.fm30().get("ps", "").strip()) or len(seen) >= 3, 10)
        self.assertTrue({"BABY,", "IT'S", "COLD"} & seen, seen)
        st = self.api("GET", "/api/status")[1]
        self.assertEqual((st["show"]["n"], st["show"]["N"]), (2, 4))

    def test_2_console_rules(self):
        code, r = self.api("POST", "/api/cmd", {"cmd": "FREQ 98.1"})
        self.assertTrue(r["ok"], r)
        self.assertEqual(r["reply"], "OK FREQ 98.1")
        code, r = self.api("POST", "/api/cmd", {"cmd": "POWER 080"})
        self.assertEqual(r["sent"], "POWER 80")
        code, r = self.api("POST", "/api/cmd", {"cmd": "PS hello"})
        self.assertEqual(code, 400)
        code, r = self.api("POST", "/api/cmd", {"cmd": "LCD MSG 1 hijack"})
        self.assertEqual(code, 400)
        code, r = self.api("POST", "/api/cmd", {"cmd": "LCD MSG 2 Merry|Christmas"})
        self.assertTrue(r["ok"], r)
        self.assertEqual(self.d.s["lcd_msgs"]["2"], ["Merry", "Christmas"])
        code, r = self.api("POST", "/api/cmd", {"cmd": "HELP"})
        self.assertIn("FREQ", r["body"])
        code, r = self.api("POST", "/api/cmd", {"cmd": "SAVE"})
        self.assertEqual(r["reply"], "OK SAVED")
        code, r = self.api("POST", "/api/cmd", {"cmd": "SAVE"})
        self.assertEqual(code, 400)          # rate limited

    def test_3_names_and_preview(self):
        code, r = self.api("PUT", "/api/piece", {"key": "DHN -BiCOS.wav",
                                                 "names": {"short": "Cold Outside", "extra": "Happy holidays!"}})
        self.assertTrue(r["ok"], r)
        code, r = self.api("POST", "/api/preview", {"key": "DHN -BiCOS.wav", "playlist": "Show"})
        self.assertEqual((r["n"], r["N"]), (2, 4))
        self.assertEqual(r["ps"][:2], ["  COLD", "OUTSIDE"])
        self.assertTrue(all(p.startswith("2/4 ") for p in r["rt"]))
        code, r = self.api("PUT", "/api/piece", {"key": "x", "names": {"type": "bogus"}})
        self.assertEqual(code, 400)

    def test_4_idle_turns_tx_off(self):
        self.state["playing"] = False
        self.assertTrue(wait(lambda: self.fm30().get("tx") == 0), "TX not switched off after the grace time")
        self.state["playing"] = True

    def test_5_volume_per_piece(self):
        self.assertTrue(wait(lambda: self.fm30().get("tx") == 1))
        start = self.fm30().get("vol")
        self.assertEqual(self.d.s["vol_default"], -1)            # unused: volume left alone
        code, r = self.api("PUT", "/api/piece", {"key": "DHN -BiCOS.wav", "names": {"vol": 300}})
        self.assertEqual(code, 400)
        code, r = self.api("PUT", "/api/piece", {"key": "DHN -BiCOS.wav", "names": {"vol": 90}})
        self.assertTrue(r["ok"], r)
        self.assertTrue(wait(lambda: self.fm30().get("vol") == 90), f"piece volume not applied: {self.fm30().get('vol')}")
        self.assertEqual(self.d.s["vol_default"], start)         # captured from the transmitter
        self.state["playing"] = False                             # between shows: back to the default
        self.assertTrue(wait(lambda: self.fm30().get("vol") == start), "default volume not restored")
        code, r = self.api("POST", "/api/cmd", {"cmd": "VOL 100"})  # manual change while idle = new default
        self.assertTrue(r["ok"], r)
        self.assertEqual(self.d.s["vol_default"], 100)
        self.state["playing"] = True
        self.assertTrue(wait(lambda: self.fm30().get("vol") == 90), "piece volume not re-applied")
        code, r = self.api("PUT", "/api/piece", {"key": "DHN -BiCOS.wav", "names": {}})
        self.assertTrue(wait(lambda: self.fm30().get("vol") == 100), "default not applied after clearing the piece")

    def test_6_volume_changes_quickly_at_song_change(self):
        self.assertTrue(wait(lambda: self.fm30().get("tx") == 1))
        self.api("PUT", "/api/piece", {"key": "MDYK.wav", "names": {"vol": 70}})
        time.sleep(1.5)                                   # let the slow path settle on the current song
        t0 = time.time()
        self.state["song"] = "MDYK.wav"
        self.assertTrue(wait(lambda: (self.d.fm30 or {}).get("vol") == 70, 3), "song volume not applied")
        took = time.time() - t0
        self.assertLess(took, 0.6, f"volume took {took:.2f} s")
        self.state["song"] = "DHN -BiCOS.wav"
        self.assertTrue(wait(lambda: (self.d.fm30 or {}).get("vol") == 100, 3))


if __name__ == "__main__":
    unittest.main()
