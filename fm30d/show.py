"""Follows FPP: what is playing, where it sits in the running playlist, and
which pieces exist across all playlists (for the name editor).

Nothing about the show is hardcoded. The running playlist is read live; on a
remote FPP (which never knows the playlist) the show master is asked.
Pieces are keyed by media file (sequence name when there is no media), so a
piece shared by several playlists has one set of names.
"""
import json
import threading
import time
import urllib.parse
import urllib.request

SHOW, INTERSTITIAL = "show", "interstitial"
SHORT_PIECE_S = 60        # pieces shorter than this default to interstitial
PLAYLIST_TTL = 30.0
SECTIONS = ("leadIn", "mainPlaylist", "leadOut")


def http_json(url, timeout=3.0):
    with urllib.request.urlopen(url, timeout=timeout) as r:
        return json.loads(r.read().decode("utf-8", "replace"))


def entry_key(e):
    return e.get("mediaName") or e.get("sequenceName") or ""


def _q(name):
    return urllib.parse.quote(name, safe="")


class Show:
    def __init__(self, settings, names, fetch=http_json, log=print):
        self.settings = settings          # dict-like, live
        self.names = names                # dict key -> overrides (live)
        self.fetch = fetch
        self.log = log
        self._pl_cache = {}               # (host, name) -> (time, playlist json)
        self._tag_cache = {}              # (host, file) -> {title, artist}
        self._master_auto = None
        self._lock = threading.Lock()

    # ------------------------------------------------------------ hosts
    def local(self):
        return self.settings.get("fpp_local", "http://127.0.0.1")

    def master(self):
        m = (self.settings.get("master") or "auto").strip()
        if m and m != "auto":
            return m if m.startswith("http") else "http://" + m
        if self._master_auto and time.time() - self._master_auto[0] < 300:
            return self._master_auto[1]
        try:
            d = self.fetch(self.local() + "/api/fppd/multiSyncSystems")
            for s in d.get("systems", []):
                addr = s.get("address", "")
                if s.get("fppModeString") in ("player", "master") and ":" not in addr:
                    self._master_auto = (time.time(), "http://" + addr)
                    return self._master_auto[1]
        except Exception as ex:
            self.log(f"show: master auto-detect failed: {ex}")
        return None

    # ------------------------------------------------------------ data
    def playlist(self, host, name):
        k = (host, name)
        c = self._pl_cache.get(k)
        if c and time.time() - c[0] < PLAYLIST_TTL:
            return c[1]
        pl = self.fetch(f"{host}/api/playlist/{_q(name)}")
        self._pl_cache[k] = (time.time(), pl)
        return pl

    def tags(self, host, media):
        k = (host, media)
        if k in self._tag_cache:
            return self._tag_cache[k]
        t = {}
        if media and media.lower().endswith((".wav", ".mp3", ".ogg", ".m4a", ".flac", ".aac", ".mp4")):
            try:
                meta = self.fetch(f"{host}/api/media/{_q(media)}/meta")
                raw = (meta.get("format") or {}).get("tags") or {}
                low = {str(a).lower(): str(b) for a, b in raw.items()}
                t = {"title": low.get("title", ""), "artist": low.get("artist", "")}
            except Exception as ex:
                self.log(f"show: no tags for {media}: {ex}")
        self._tag_cache[k] = t
        return t

    def piece_type(self, key, entry, section):
        o = self.names.get(key) or {}
        if o.get("type") in (SHOW, INTERSTITIAL):
            return o["type"]
        if section != "mainPlaylist":
            return INTERSTITIAL
        try:
            if float(entry.get("duration") or 0) and float(entry["duration"]) < SHORT_PIECE_S:
                return INTERSTITIAL
        except (TypeError, ValueError):
            pass
        return SHOW

    def fields(self, host, key):
        """Names for a piece: user overrides, else tags, else file name."""
        o = self.names.get(key) or {}
        t = self.tags(host, key) if host else {}
        stem = key.rsplit(".", 1)[0] if key else ""
        title = o.get("title") or t.get("title") or stem
        return {
            "title": title,
            "artist": o.get("artist") if o.get("artist") is not None and o.get("artist") != "" else t.get("artist", ""),
            "short": o.get("short") or title,
            "extra": o.get("extra", ""),
            "needs_name": not (o.get("title") or t.get("title")),
        }

    # ------------------------------------------------------------ now
    def now(self):
        """-> dict describing what is playing (never raises)."""
        out = {"playing": False, "source": None, "playlist": None, "media": None,
               "key": None, "section": None, "n": 0, "N": 0, "type": None,
               "next": "", "error": None, "seconds_remaining": None}
        try:
            st = self.fetch(self.local() + "/api/fppd/status")
        except Exception as ex:
            out["error"] = f"local FPP: {ex}"
            return out
        host = self.local()
        if not st.get("current_playlist") or st.get("mode_name") == "remote":
            m = self.master()
            if m:
                try:
                    mst = self.fetch(m + "/api/fppd/status")
                    st_play = mst
                    host = m
                except Exception as ex:
                    out["error"] = f"master {m}: {ex}"
                    st_play = st
            else:
                st_play = st
        else:
            st_play = st

        out["playing"] = st.get("status_name") == "playing" or st_play.get("status_name") == "playing"
        out["source"] = host
        media = st.get("current_song") or st_play.get("current_song") or ""
        seq = st.get("current_sequence") or st_play.get("current_sequence") or ""
        out["media"] = media or seq
        out["key"] = media or seq or None
        out["seconds_remaining"] = st.get("seconds_remaining") or st_play.get("seconds_remaining")
        sch = st_play.get("scheduler") or {}
        nxt = (sch.get("nextPlaylist") or {}).get("scheduledStartTimeStr", "")
        out["next"] = nxt.split(" - (")[0].replace(" @ ", " ") if nxt else ""

        cp = st_play.get("current_playlist") or {}
        name = cp.get("playlist")
        if not (out["playing"] and name):
            return out
        out["playlist"] = name
        try:
            pl = self.playlist(host, name)
        except Exception as ex:
            out["error"] = f"playlist {name}: {ex}"
            return out
        flat = [(sec, i, e) for sec in SECTIONS for i, e in enumerate(pl.get(sec) or [])]
        idx = int(cp.get("index") or 0) - 1
        hit = None
        if 0 <= idx < len(flat) and out["key"] in (flat[idx][2].get("mediaName"), flat[idx][2].get("sequenceName")):
            hit = flat[idx]
        else:
            for f in flat:
                if out["key"] in (f[2].get("mediaName"), f[2].get("sequenceName")):
                    hit = f
                    break
        main = pl.get("mainPlaylist") or []
        shows = [i for i, e in enumerate(main) if self.piece_type(entry_key(e), e, "mainPlaylist") == SHOW]
        out["N"] = len(shows)
        if hit:
            sec, i, e = hit
            out["key"] = entry_key(e)
            out["section"] = sec
            out["type"] = self.piece_type(out["key"], e, sec)
            if sec == "mainPlaylist" and out["type"] == SHOW:
                out["n"] = shows.index(i) + 1
        return out

    # ------------------------------------------------------------ editor
    def pieces(self):
        """Every piece across all playlists on the show master."""
        host = self.master() if (self.settings.get("master") or "auto") != "local" else self.local()
        host = host or self.local()
        names = []
        try:
            d = self.fetch(host + "/api/playlists")
            names = [n if isinstance(n, str) else n.get("name") for n in d]
        except Exception as ex:
            self.log(f"show: cannot list playlists on {host}: {ex}")
        pieces = {}
        playlists = {}
        for pname in names:
            if not pname:
                continue
            try:
                pl = self.playlist(host, pname)
            except Exception:
                continue
            main = pl.get("mainPlaylist") or []
            n_show = 0
            order = []
            for sec in SECTIONS:
                for i, e in enumerate(pl.get(sec) or []):
                    k = entry_key(e)
                    if not k:
                        continue
                    typ = self.piece_type(k, e, sec)
                    if sec == "mainPlaylist" and typ == SHOW:
                        n_show += 1
                    order.append({"key": k, "section": sec, "type": typ,
                                  "n": n_show if sec == "mainPlaylist" and typ == SHOW else 0})
                    p = pieces.setdefault(k, {"key": k, "playlists": [], "duration": e.get("duration"),
                                              "sections": set()})
                    if pname not in p["playlists"]:
                        p["playlists"].append(pname)
                    p["sections"].add(sec)
            playlists[pname] = {"N": sum(1 for e in main if self.piece_type(entry_key(e), e, "mainPlaylist") == SHOW),
                                "order": order}
        out = []
        for k, p in sorted(pieces.items(), key=lambda kv: kv[0].lower()):
            f = self.fields(host, k)
            t = self.tags(host, k)
            o = self.names.get(k) or {}
            sec = "mainPlaylist" if "mainPlaylist" in p["sections"] else next(iter(p["sections"]))
            out.append({"key": k, "playlists": p["playlists"], "duration": p["duration"],
                        "tags": t, "names": o, "effective": f,
                        "type": self.piece_type(k, {"duration": p["duration"]}, sec),
                        "type_auto": o.get("type") not in (SHOW, INTERSTITIAL)})
        return {"host": host, "pieces": out, "playlists": playlists}
