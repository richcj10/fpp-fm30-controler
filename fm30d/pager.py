"""RDS text layout: templates, PS word pages and RT pages.

PS  - 8 chars. Text is broken into word pages (greedy, words kept whole);
      a word longer than 8 is hyphen-split ("PENTATONIX" -> "PENTATO-", "NIX").
RT  - 64 chars. Longer text is split at word boundaries; when the template
      starts with {count}, every page starts with the counter ("2/4 ...").

Template tokens: {count} {n} {N} {title} {artist} {short} {extra} {station}
{next}. Empty tokens take their neighbouring " - " / " | " separators with
them, so "{count} {title} - {artist}" with no artist renders "2/4 Title".
"""
import re

from commands import sanitize, PS_LEN, RT_LEN

TOKEN = re.compile(r"\{(\w+)\}")
SEP = r"(?:-|\||/|:|,)"


def render(template, fields):
    out = TOKEN.sub(lambda m: str(fields.get(m.group(1), "") or ""), template or "")
    return tidy(out)


def tidy(s):
    s = re.sub(r"\s+", " ", s).strip()
    prev = None
    while prev != s:
        prev = s
        s = re.sub(rf"^{SEP}\s*", "", s)                     # leading separator
        s = re.sub(rf"\s*{SEP}$", "", s)                     # trailing separator
        s = re.sub(rf"\s+{SEP}\s+(?={SEP}\s)", " ", s)       # " - | x" -> " | x"
        s = re.sub(r"\(\s*\)", "", s).strip()
    return s


def counter(n, total):
    return f"{n}/{total}" if n and total else ""


def _chunks(word, width, hyphen):
    if len(word) <= width:
        return [word]
    out = []
    step = width - 1 if hyphen else width
    while len(word) > width:
        out.append(word[:step] + ("-" if hyphen else ""))
        word = word[step:]
    out.append(word)
    return out


def wrap(text, width, hyphen=False):
    """Greedy word wrap; never returns an empty list for non-empty text."""
    words = []                      # (text, must break after: hyphen-split piece)
    for w in text.split(" "):
        if w:
            ch = _chunks(w, width, hyphen)
            words.extend((c, i < len(ch) - 1) for i, c in enumerate(ch))
    pages, cur, brk = [], "", False
    for w, split in words:
        if not cur:
            cur = w
        elif len(cur) + 1 + len(w) <= width and not brk:
            cur += " " + w
        else:
            pages.append(cur)
            cur = w
        brk = split
    if cur:
        pages.append(cur)
    return pages


def ps_pages(text, uppercase=True, center=True):
    s = sanitize(text, 1000)
    if uppercase:
        s = s.upper()
    pages = wrap(s, PS_LEN, hyphen=True) or [""]
    if center:
        pages = [p.center(PS_LEN).rstrip() if len(p) < PS_LEN else p for p in pages]
    return pages


def rt_pages(template, fields, counter_every_page=True):
    """-> list of RT strings (each <= 64 chars)."""
    count = fields.get("count", "")
    t = (template or "").lstrip()
    if counter_every_page and count and t.startswith("{count}"):
        prefix = count + " "
        body = render(t[len("{count}"):], fields)
        body = sanitize(body, 10000)
        if not body:
            return [count]
        width = RT_LEN - len(prefix)
        return [prefix + p for p in wrap(body, width)]
    full = sanitize(render(t, fields), 10000)
    return wrap(full, RT_LEN) or [""]
