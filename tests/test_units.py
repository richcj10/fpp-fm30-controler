import os
import sys
import unittest

sys.path.insert(0, os.path.join(os.path.dirname(__file__), "..", "fm30d"))

import commands as C   # noqa: E402
import pager as P      # noqa: E402
from show import Show  # noqa: E402


class Commands(unittest.TestCase):
    def ok(self, text, want):
        self.assertEqual(C.parse_command(text), want)

    def bad(self, text):
        with self.assertRaises(C.CommandError):
            C.parse_command(text)

    def test_freq(self):
        self.ok("freq 98.1", "FREQ 98.1")
        self.ok("FREQ 105.5", "FREQ 105.5")
        self.ok("FREQ 98.06", "FREQ 98.1")
        self.ok("FREQ 108", "FREQ 108.0")
        self.bad("FREQ 108.1")
        self.bad("FREQ 87.4")
        self.bad("FREQ abc")
        self.bad("FREQ -98")

    def test_power_never_octal(self):
        self.ok("POWER 080", "POWER 80")
        self.ok("POWER 50%", "POWER 50%")
        self.ok("power 3968", "POWER 3968")
        self.bad("POWER 3969")
        self.bad("POWER 101%")
        self.bad("POWER 0x10")

    def test_misc(self):
        self.ok("tx on", "TX ON")
        self.ok("TX 0", "TX OFF")
        self.bad("TX maybe")
        self.ok("BAL -127", "BAL -127")
        self.bad("BAL -128")
        self.ok("PI 0x1a", "PI 001A")
        self.bad("PI 12345")
        self.ok("TP 1", "TP 1")
        self.ok("U6 auto", "U6 AUTO")
        self.ok("VTSCALE 3", "VTSCALE 3.000")
        self.bad("VTSCALE 0.5")
        self.ok("?", "HELP")
        self.bad("SAVE now")
        self.bad("REBOOT")
        self.bad("FREQ 98.1\nTX ON")

    def test_lcd(self):
        self.ok("LCD MSG 2 Merry|Christmas from us", "LCD MSG 2 Merry|Christmas from us")
        self.ok("LCD MSG 3 bottom only", "LCD MSG 3 bottom only")
        self.ok("LCD MSG 4", "LCD MSG 4")
        self.ok("LCD MSG 2 TooLongTop|x", "LCD MSG 2 TooLongT|x")
        self.ok("LCD ROTATE 6, 1 5", "LCD ROTATE 1 5 6")
        self.ok("LCD page 0", "LCD PAGE 0")
        self.bad("LCD MSG 5 x")
        self.bad("LCD ROTATE 9")

    def test_sanitize(self):
        self.assertEqual(C.sanitize("Café “Noël” – Björk…", 64), 'Cafe "Noel" - Bjork...')
        self.assertEqual(C.build_ps("Wizards in Winter"), "PS Wizards")
        self.assertEqual(C.build_rt(""), "RT")
        self.assertEqual(C.sanitize("a|b", 10, True), "ab")

    def test_reply_words(self):
        self.assertIsNone(C.expected_reply_word("STATUS"))
        self.assertEqual(C.expected_reply_word("SAVE"), "SAVED")
        self.assertEqual(C.expected_reply_word("VTSCALE 3.000"), "")
        self.assertEqual(C.expected_reply_word("FREQ 98.1"), "FREQ")


class Pager(unittest.TestCase):
    def test_ps_word_pages(self):
        self.assertEqual(P.ps_pages("Wizards in Winter", center=False), ["WIZARDS", "IN", "WINTER"])
        self.assertEqual(P.ps_pages("Pentatonix", center=False), ["PENTATO-", "NIX"])
        self.assertEqual(P.ps_pages("Silent Night", center=False), ["SILENT", "NIGHT"])
        self.assertEqual(P.ps_pages("Joy to the World", center=False), ["JOY TO", "THE", "WORLD"])
        self.assertEqual(P.ps_pages("IN", center=True), ["   IN"])
        for p in P.ps_pages("Baby, It's Cold Outside Extraordinarily"):
            self.assertLessEqual(len(p), 8)

    def test_rt_counter_every_page(self):
        f = {"count": "2/4", "title": "Baby, It's Cold Outside", "artist": "Drew Holcomb & The Neighbors",
             "extra": "Merry Christmas from the Smith house!"}
        pages = P.rt_pages("{count} {title} - {artist} | {extra}", f)
        self.assertGreater(len(pages), 1)
        for p in pages:
            self.assertTrue(p.startswith("2/4 "), p)
            self.assertLessEqual(len(p), 64)
        joined = " ".join(p[4:] for p in pages)
        self.assertEqual(joined, "Baby, It's Cold Outside - Drew Holcomb & The Neighbors | Merry Christmas from the Smith house!")

    def test_rt_single_and_tidy(self):
        f = {"count": "1/4", "title": "Silent Night", "artist": "Lady A", "extra": ""}
        self.assertEqual(P.rt_pages("{count} {title} - {artist} | {extra}", f), ["1/4 Silent Night - Lady A"])
        f = {"count": "", "title": "105.5 FM Station ID", "artist": "", "extra": ""}
        self.assertEqual(P.rt_pages("{count} {title} - {artist} | {extra}", f), ["105.5 FM Station ID"])
        self.assertEqual(P.render("{title} - {artist}", {"title": "AC/DC Song", "artist": ""}), "AC/DC Song")


PLAYLIST = {
    "leadIn": [{"type": "both", "sequenceName": "Intro.fseq", "mediaName": "Intro.wav", "duration": 77.0}],
    "mainPlaylist": [
        {"type": "both", "sequenceName": "LadyA.fseq", "mediaName": "LadyA-SilentNight.wav", "duration": 189.6},
        {"type": "both", "sequenceName": "BiCOS.fseq", "mediaName": "DHN -BiCOS.wav", "duration": 160.7},
        {"type": "both", "sequenceName": "StationID.fseq", "mediaName": "StationID.wav", "duration": 25.6},
        {"type": "both", "sequenceName": "WiW.fseq", "mediaName": "TSO-WiW.wav", "duration": 181.7},
        {"type": "both", "sequenceName": "MDYK.fseq", "mediaName": "MDYK.wav", "duration": 200.3},
        {"type": "both", "sequenceName": "StationID.fseq", "mediaName": "StationID.wav", "duration": 25.6},
    ],
    "leadOut": [],
}


def fake_fpp(local_status, master_status):
    def fetch(url, timeout=3.0):
        if url.startswith("http://local/api/fppd/status"):
            return local_status
        if url.startswith("http://local/api/fppd/multiSyncSystems"):
            return {"systems": [{"address": "10.0.0.2", "fppModeString": "player"},
                                {"address": "10.0.0.3", "fppModeString": "remote"}]}
        if url.startswith("http://10.0.0.2/api/fppd/status"):
            return master_status
        if url.startswith("http://10.0.0.2/api/playlists"):
            return ["Show"]
        if url.startswith("http://10.0.0.2/api/playlist/"):
            return PLAYLIST
        if "/api/media/" in url:
            if "BiCOS" in url:
                return {"format": {"tags": {"title": "Baby, It's Cold Outside", "artist": "Drew Holcomb"}}}
            return {"format": {"tags": {}}}
        raise OSError("unexpected " + url)
    return fetch


class ShowTests(unittest.TestCase):
    def mk(self, index, song, names=None):
        local = {"mode_name": "remote", "status_name": "playing", "current_playlist": None,
                 "current_song": song, "current_sequence": ""}
        master = {"mode_name": "player", "status_name": "playing", "current_song": song,
                  "current_playlist": {"playlist": "Show", "index": str(index), "count": "7"},
                  "scheduler": {"nextPlaylist": {"scheduledStartTimeStr": "Mon Nov 23 @ 05:30 PM - (Everyday)"}}}
        return Show({"fpp_local": "http://local", "master": "auto"}, names or {}, fetch=fake_fpp(local, master),
                    log=lambda m: None)

    def test_counter_skips_intro_and_station_id(self):
        n = self.mk(3, "DHN -BiCOS.wav").now()
        self.assertEqual((n["n"], n["N"], n["type"], n["section"]), (2, 4, "show", "mainPlaylist"))
        self.assertEqual(n["next"], "Mon Nov 23 05:30 PM")
        n = self.mk(4, "StationID.wav").now()
        self.assertEqual((n["n"], n["N"], n["type"]), (0, 4, "interstitial"))
        n = self.mk(1, "Intro.wav").now()
        self.assertEqual((n["n"], n["section"]), (0, "leadIn"))
        n = self.mk(6, "MDYK.wav").now()
        self.assertEqual((n["n"], n["N"]), (4, 4))

    def test_type_override_changes_count(self):
        names = {"TSO-WiW.wav": {"type": "interstitial"}}
        n = self.mk(6, "MDYK.wav", names).now()
        self.assertEqual((n["n"], n["N"]), (3, 3))

    def test_pieces_and_fields(self):
        s = self.mk(3, "DHN -BiCOS.wav")
        d = s.pieces()
        keys = [p["key"] for p in d["pieces"]]
        self.assertIn("StationID.wav", keys)
        self.assertEqual(d["playlists"]["Show"]["N"], 4)
        f = s.fields("http://10.0.0.2", "DHN -BiCOS.wav")
        self.assertEqual(f["title"], "Baby, It's Cold Outside")
        f = s.fields("http://10.0.0.2", "MDYK.wav")
        self.assertTrue(f["needs_name"])
        self.assertEqual(f["title"], "MDYK")


class CustomLinesTests(unittest.TestCase):
    """Per-piece custom PS/RT lines (ps_lines / rt_lines)."""

    def setUp(self):
        import tempfile
        import fm30d as D
        self.D = D
        self.tmp = tempfile.TemporaryDirectory()
        self.d = D.Daemon(self.tmp.name, port_override="socket://127.0.0.1:1")
        self.d.show = ShowTests.mk(None, 3, "DHN -BiCOS.wav", self.d.names_store.data)
        self.d.show.names = self.d.names_store.data

    def tearDown(self):
        self.tmp.cleanup()

    def lay(self, key="DHN -BiCOS.wav", n=2, typ="show"):
        return self.d.piece_layout(key, "http://10.0.0.2", n, 4, typ, True, "Mon 5:30 PM")

    def test_auto_lines_prefill(self):
        lay = self.lay()
        self.assertEqual(lay["auto_rt"], ["{count} Baby, It's Cold Outside - Drew Holcomb"])
        self.assertEqual(lay["auto_ps"], ["BABY,", "IT'S", "COLD", "OUTSIDE", "{station}"])
        self.assertEqual(lay["rt"], ["2/4 Baby, It's Cold Outside - Drew Holcomb"])
        lay = self.lay("StationID.wav", 0, "interstitial")
        self.assertFalse(any("{count}" in l for l in lay["auto_rt"]))

    def test_custom_lines_used_with_live_tokens(self):
        r = self.d.api_piece_put("DHN -BiCOS.wav", {
            "ps_lines": "BABY,\nIT'S COLD\n\nmerry xmas\n{STATION}",
            "rt_lines": ["{count} Baby, It's Cold Outside - Drew Holcomb", "{count} Merry Christmas from our house!",
                         "Next show {next}"]})
        self.assertEqual(r["names"]["ps_lines"], ["BABY,", "IT'S COLD", "merry xmas", "{STATION}"])
        lay = self.lay()
        self.assertEqual([p.strip() for p in lay["ps"]], ["BABY,", "IT'S", "COLD", "MERRY", "XMAS", "105.5 FM"])
        self.assertEqual(lay["rt"], ["2/4 Baby, It's Cold Outside - Drew Holcomb", "2/4 Merry Christmas from our house!",
                                     "Next show Mon 5:30 PM"])
        lay = self.lay(n=3)                          # reordered show: counter follows
        self.assertTrue(all(p.startswith("3/4") for p in lay["rt"][:2]))
        self.assertEqual(lay["auto_rt"], ["{count} Baby, It's Cold Outside - Drew Holcomb"])

    def test_long_custom_rt_line_keeps_counter_on_each_page(self):
        long = "{count} " + " ".join(["Merry Christmas and happy holidays"] * 4)
        self.d.api_piece_put("DHN -BiCOS.wav", {"rt_lines": [long]})
        rt = self.lay()["rt"]
        self.assertGreater(len(rt), 1)
        self.assertTrue(all(p.startswith("2/4 ") and len(p) <= 64 for p in rt))

    def test_only_rt_custom_keeps_auto_ps_and_validation(self):
        self.d.api_piece_put("DHN -BiCOS.wav", {"rt_lines": ["Hello"]})
        lay = self.lay()
        self.assertEqual(lay["rt"], ["Hello"])
        self.assertEqual(lay["ps"][-1], "105.5 FM")
        for bad in (["x" * 201], ["a\tb"], ["l"] * 21):
            with self.assertRaises(C.CommandError):
                self.d.api_piece_put("DHN -BiCOS.wav", {"rt_lines": bad})
        r = self.d.api_piece_put("DHN -BiCOS.wav", {"rt_lines": ["", "  "]})   # all blank -> automatic
        self.assertNotIn("rt_lines", r["names"])
        self.assertEqual(self.lay()["rt"], ["2/4 Baby, It's Cold Outside - Drew Holcomb"])

    def test_idle_ignores_custom_lines(self):
        self.d.api_piece_put("DHN -BiCOS.wav", {"rt_lines": ["Hello"]})
        lay = self.d.piece_layout(None, "http://10.0.0.2", 0, 0, "show", False, "Mon 5:30 PM")
        self.assertEqual(lay["rt"], ["Christmas light show - next show Mon 5:30 PM"])


if __name__ == "__main__":
    unittest.main()
