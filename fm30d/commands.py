"""FM30 STM32 command parsing and text sanitising.

Port of FM30-Controller lib/Fm30Core (Commands.cpp, Sanitize.cpp). Everything
that reaches the STM32 is parsed, range-checked and rebuilt from the parsed
values; raw input is never forwarded.
"""
import re
import unicodedata

MAX_LINE = 127          # STM32 line buffer is 128 incl. NUL
MAX_INPUT = 400
POWER_MAX = 3968        # POWER_MAX (0xF80) in the STM32 config.h
PS_LEN, RT_LEN = 8, 64
LCD_TOP_LEN, LCD_BOTTOM_LEN = 8, 48
LCD_SLOTS, LCD_PAGES = 4, 8

# Commands that change what goes on air or the tuning internals.
PROTECTED = {"FREQ", "POWER", "U6TRIM", "U6", "VTSCALE", "RDSLEVEL", "DACTEST", "DEFAULTS"}


class CommandError(ValueError):
    pass


# ---------------------------------------------------------------- sanitise
_TRANSLIT = {
    "Æ": "AE", "Þ": "Th", "ß": "ss", "æ": "ae", "þ": "th",
    "©": "(c)", "®": "(R)", "™": "TM", "…": "...", "€": "EUR",
    "Œ": "OE", "œ": "oe", "×": "x", "ˆ": "^", "˜": "~", "ƒ": "f",
    "·": "*", "•": "*", "Ø": "O", "ø": "o", "Ð": "D", "ð": "d",
    "Ł": "L", "ł": "l", "ı": "i",
}
for _c in "´‘’‚′‹›":
    _TRANSLIT[_c] = "'"
for _c in "«»“”„″":
    _TRANSLIT[_c] = '"'
for _c in "‐‑‒–—―−":
    _TRANSLIT[_c] = "-"

_SPACE = set("    ") | {chr(c) for c in range(0x2000, 0x200B)}
_DROP = {"​", "‌", "‍", "﻿"}


def _translit(ch):
    cp = ord(ch)
    if 0x20 <= cp < 0x7F:
        return ch
    if cp < 0x20 or cp == 0x7F or 0x80 <= cp < 0xA0 or ch in _SPACE:
        return " "
    if ch in _DROP or 0x300 <= cp <= 0x36F:
        return ""
    if ch in _TRANSLIT:
        return _TRANSLIT[ch]
    base = unicodedata.normalize("NFKD", ch)
    base = "".join(c for c in base if 0x20 <= ord(c) < 0x7F)
    return base[:1] if base else "?"


def sanitize(text, max_chars, strip_pipe=False):
    """Printable ASCII only, whitespace runs collapsed, trimmed, clipped."""
    out = []
    pending = False
    for ch in str(text or ""):
        r = _translit(ch)
        if r == "":
            continue
        if r == " ":
            pending = True
            continue
        if pending:
            if out and out[-1] != " ":
                out.append(" ")
            pending = False
        for c in r:
            if c == "|" and strip_pipe:
                continue
            out.append(c)
    s = "".join(out).strip()
    return s[:max_chars].rstrip()


# ---------------------------------------------------------------- parsers
def _parse_long(s, lo, hi):
    if not re.fullmatch(r"[+-]?\d{1,9}", s or ""):
        return None
    v = int(s, 10)          # always decimal: the STM32 parses POWER with strtol(.., 0)
    return v if lo <= v <= hi else None


def _parse_milli(s):
    m = re.fullmatch(r"(\d{0,6})(?:\.(\d{0,12}))?", s or "")
    if not m or not (m.group(1) or m.group(2)):
        return None
    ip = int(m.group(1) or 0)
    frac = ((m.group(2) or "") + "0000")[:4]
    return ip * 1000 + (int(frac) + 5) // 10


def _on_off(s):
    s = (s or "").upper()
    return 1 if s in ("ON", "1") else 0 if s in ("OFF", "0") else None


def _line(s):
    if len(s) > MAX_LINE:
        raise CommandError("command too long")
    return s


def _int_cmd(name, arg, lo, hi):
    v = _parse_long(arg, lo, hi)
    if v is None:
        raise CommandError(f"{name} {lo}..{hi}")
    return _line(f"{name} {v}")


def _on_off_cmd(name, arg, numeric):
    b = _on_off(arg)
    if b is None:
        raise CommandError(f"{name} 0|1" if numeric else f"{name} ON|OFF")
    return _line(f"{name} {b}" if numeric else f"{name} {'ON' if b else 'OFF'}")


def build_ps(text):
    return "PS " + sanitize(text, PS_LEN)


def build_rt(text):
    rt = sanitize(text, RT_LEN)
    return f"RT {rt}" if rt else "RT"


def build_lcd_msg(slot, top, bottom):
    if not isinstance(slot, int) or not 1 <= slot <= LCD_SLOTS:
        raise CommandError("LCD MSG slot 1-4")
    t = sanitize(top, LCD_TOP_LEN, True)
    b = sanitize(bottom, LCD_BOTTOM_LEN, True)
    if not t and not b:
        return f"LCD MSG {slot}"
    if not t:
        return _line(f"LCD MSG {slot} {b}")
    return _line(f"LCD MSG {slot} {t}|{b}")


def build_freq(mhz):
    milli = _parse_milli(str(mhz).strip())
    if milli is None:
        raise CommandError("FREQ 87.5-108.0")
    f10 = (milli + 50) // 100
    if not 875 <= f10 <= 1080:
        raise CommandError("FREQ 87.5-108.0")
    return f"FREQ {f10 // 10}.{f10 % 10}"


def build_power_percent(pct):
    if not isinstance(pct, int) or not 0 <= pct <= 100:
        raise CommandError("POWER 0-100%")
    return f"POWER {pct}%"


def _lcd(rest):
    sub, _, arg = rest.partition(" ")
    arg = arg.lstrip(" ")
    b = _on_off(sub)
    if b is not None and not arg:
        return f"LCD {'ON' if b else 'OFF'}"
    sub = sub.upper()
    if sub == "PAGE":
        v = _parse_long(arg.rstrip(" "), 0, LCD_PAGES)
        if v is None:
            raise CommandError("LCD PAGE 0-8")
        return f"LCD PAGE {v}"
    if sub == "MSG":
        m = re.match(r"(\d{1,3})(?: +|$)(.*)", arg)
        if not m:
            raise CommandError("LCD MSG 1-4 [top]|<bottom>")
        slot = _parse_long(m.group(1), 1, LCD_SLOTS)
        if slot is None:
            raise CommandError("LCD MSG 1-4 [top]|<bottom>")
        text = m.group(2)
        if "|" in text:
            top, _, bottom = text.partition("|")
            return build_lcd_msg(slot, top, bottom)
        return build_lcd_msg(slot, "", text)
    if sub == "ROTATE":
        arg = arg.rstrip(" ")
        if arg.upper() == "ALL":
            return "LCD ROTATE ALL"
        toks = [t for t in re.split(r"[ ,]+", arg) if t]
        pages = set()
        for t in toks:
            v = _parse_long(t, 1, LCD_PAGES)
            if v is None:
                raise CommandError("LCD ROTATE 1-8 ... | ALL")
            pages.add(v)
        if not pages:
            raise CommandError("LCD ROTATE 1-8 ... | ALL")
        return "LCD ROTATE " + " ".join(str(p) for p in sorted(pages))
    raise CommandError("LCD ON|OFF|PAGE n|MSG n text|ROTATE list")


def parse_command(text):
    """Validate one console/API command and return the line to send."""
    if text is None:
        raise CommandError("empty command")
    if len(text) > MAX_INPUT:
        raise CommandError("command too long")
    if any(ord(c) < 0x20 or ord(c) == 0x7F for c in text):
        raise CommandError("control characters (CR/LF/TAB) not allowed")
    s = text.lstrip(" ")
    if not s:
        raise CommandError("empty command")
    cmd, _, rest = s.partition(" ")
    rest = rest.lstrip(" ")
    cmd = cmd.upper()

    if cmd == "PS":
        return build_ps(rest)
    if cmd == "RT":
        return build_rt(rest)
    if cmd == "LCD":
        return _lcd(rest)
    rest = rest.rstrip(" ")

    if cmd == "FREQ":
        return build_freq(rest)
    if cmd == "POWER":
        if rest.endswith("%"):
            v = _parse_long(rest[:-1], 0, 100)
            if v is None:
                raise CommandError("POWER 0-100% or 0-3968")
            return build_power_percent(v)
        v = _parse_long(rest, 0, POWER_MAX)
        if v is None:
            raise CommandError("POWER 0-100% or 0-3968")
        return f"POWER {v}"
    if cmd == "TX":
        return _on_off_cmd("TX", rest, False)
    if cmd == "VOL":
        return _int_cmd("VOL", rest, 0, 255)
    if cmd == "BAL":
        return _int_cmd("BAL", rest, -127, 127)
    if cmd == "U6TRIM":
        return _int_cmd("U6TRIM", rest, -1000, 1000)
    if cmd == "U6":
        if rest.upper() == "AUTO":
            return "U6 AUTO"
        return _int_cmd("U6", rest, 0, 4095)
    if cmd == "VTSCALE":
        m = _parse_milli(rest)
        if m is None or not 1000 <= m <= 10000:
            raise CommandError("VTSCALE 1.0-10.0")
        return f"VTSCALE {m // 1000}.{m % 1000:03d}"
    if cmd in ("RDS", "RDSFREE"):
        return _on_off_cmd(cmd, rest, False)
    if cmd == "RDSLEVEL":
        return _int_cmd("RDSLEVEL", rest, 0, 2047)
    if cmd == "RDSPHASE":
        return _int_cmd("RDSPHASE", rest, -180, 180)
    if cmd == "PI":
        m = re.fullmatch(r"(?:0[xX])?([0-9A-Fa-f]{1,4})", rest)
        if not m:
            raise CommandError("PI hex 0000-FFFF")
        return f"PI {int(m.group(1), 16):04X}"
    if cmd == "PTY":
        return _int_cmd("PTY", rest, 0, 31)
    if cmd in ("TP", "TA", "MS", "STEREO"):
        return _on_off_cmd(cmd, rest, True)
    if cmd == "STATUS":
        if not rest:
            return "STATUS"
        return _int_cmd("STATUS", rest, 0, 3600)
    if cmd in ("SAVE", "DEFAULTS", "HELP", "?", "DACDBG"):
        if rest:
            raise CommandError(f"{'HELP' if cmd == '?' else cmd} takes no arguments")
        return "HELP" if cmd == "?" else cmd
    if cmd == "DACTEST":
        return _int_cmd("DACTEST", rest, 0, 2)
    raise CommandError("unknown command (HELP)")


def command_word(line):
    return line.split(" ", 1)[0].upper()


def is_protected(line):
    return command_word(line) in PROTECTED


def expected_reply_word(line):
    """None = expects a JSON line; "" = bare OK; else the echoed word."""
    w = command_word(line)
    if w == "STATUS":
        return None
    if w == "SAVE":
        return "SAVED"
    if w in ("VTSCALE", "HELP", "?", "DACDBG"):
        return ""
    return w
