"""Check and apply A. Yusuf Ali's English translation to the site's verse cards.

The text comes from Tanzil (en.yusufali, the 1934-37 wording) and is cross-checked against the
Quran API project's copy. Whole-verse cards can be replaced mechanically; cards that quote only part of
a verse are listed for a person to excerpt, because Yusuf Ali's sentences do not follow the Arabic
word for word.

  python3 -I tools/verify/yusufali.py <repo> report   list every card: whole verse or excerpt, current and Yusuf Ali text
  python3 -I tools/verify/yusufali.py <repo> apply    rewrite whole-verse cards in tools/build/content/*.txt
"""
import json, re, sys, urllib.request
from pathlib import Path

REPO = Path(sys.argv[1]); MODE = sys.argv[2]
CACHE = Path(__file__).parent / "cache"; CACHE.mkdir(exist_ok=True)
UA = {"User-Agent": "islamicdilemma-verify"}


def fetch(url, name):
    f = CACHE / name
    if not f.exists():
        f.write_bytes(urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=60).read())
    return f.read_text(encoding="utf-8")


def yusufali():
    out = {}
    for line in fetch("https://tanzil.net/trans/en.yusufali", "en.yusufali.txt").splitlines():
        m = re.match(r"^(\d+)\|(\d+)\|(.*)$", line)
        if m:
            out[f"{m.group(1)}:{m.group(2)}"] = m.group(3).strip()
    check = json.loads(fetch("https://cdn.jsdelivr.net/gh/fawazahmed0/quran-api@1/editions/eng-abdullahyusufal.json", "eng-abdullahyusufal.json"))
    other = {f"{v['chapter']}:{v['verse']}": v["text"] for v in check["quran"]}
    norm = lambda s: re.sub(r"\W+", "", s.lower())
    bad = [k for k in out if k in other and norm(out[k]) != norm(other[k]) and not k.endswith(":1")]
    if bad:
        sys.exit("Tanzil and the Quran API disagree on: " + ", ".join(bad[:10]))
    return out


def uthmani():
    data = json.loads(fetch("https://api.alquran.cloud/v1/quran/quran-uthmani", "quran-uthmani.json"))
    out = {}
    for s in data["data"]["surahs"]:
        for a in s["ayahs"]:
            out[f"{s['number']}:{a['numberInSurah']}"] = a["text"]
    return out


MARKS = re.compile(r"[ؐ-ًؚ-ٰٟۖ-ۭـ]")
def letters(s):
    s = MARKS.sub("", s)
    s = re.sub("[أإآٱ]", "ا", s).replace("ى", "ي").replace("ة", "ه").replace("ؤ", "و").replace("ئ", "ي").replace("ء", "")
    s = s.replace("ا", "").replace("ي", "").replace("و", "")
    return re.sub(r"[^ء-ي]", "", s)


def refs(ref):
    m = re.search(r"(\d+):(\d+)(?:[–-](\d+))?", ref)
    a, b = int(m.group(2)), int(m.group(3) or m.group(2))
    return [f"{m.group(1)}:{i}" for i in range(a, b + 1)]


def cards():
    for f in sorted((REPO / "tools/build/content").glob("*.txt")):
        for n, line in enumerate(f.read_text(encoding="utf-8").splitlines()):
            if line.startswith("::ayah "):
                ref, arabic, tr = [s.strip() for s in line[7:].split("|", 2)]
                yield f, n, ref, arabic, tr


YA, AR = yusufali(), uthmani()
rows = []
for f, n, ref, arabic, tr in cards():
    keys = refs(ref)
    full = "".join(AR[k] for k in keys)
    whole = letters(arabic) == letters(full)
    rows.append((f, n, ref, arabic, tr, keys, whole))

if MODE == "report":
    whole_n = sum(1 for r in rows if r[6])
    print(f"{len(rows)} verse cards: {whole_n} quote the whole verse, {len(rows) - whole_n} quote part of it\n")
    for f, n, ref, arabic, tr, keys, whole in rows:
        print(f"{'WHOLE ' if whole else 'PART  '} {ref:12} {f.name}:{n + 1}")
        print("   now: " + tr)
        print("   YA : " + " ".join(YA[k] for k in keys))
elif MODE == "apply":
    import hashlib
    sys.path.insert(0, str(REPO / "tools/build"))
    import article
    EXC = {(e["ref"], e["key"]): e for e in json.loads((Path(__file__).parent / "yusufali-excerpts.json").read_text(encoding="utf-8"))}

    def tidy(t):
        """Display fixes only: his dash marks become dashes, a colon before a quote gets its space, and a
        missing opening quote in 5:116 is restored. His words are not changed."""
        t = re.sub(r"\s+", " ", t).strip()
        t = t.replace(':"', ': "')
        t = re.sub(r"(?<=[,;:?!.)\"'])-\s*", "\u2014", t)
        t = re.sub(r"(?<=\w)- ", "\u2014", t)
        t = t.replace("say unto men, worship me and my mother", "say unto men, 'worship me and my mother")
        return t

    def cut(text, start, end):
        i = text.find(start); j = text.find(end, i)
        if i < 0 or j < 0:
            sys.exit(f"cut point missing: {start!r} .. {end!r}")
        return text[i:j + len(end)]

    def new_text(ref, arabic, keys, whole):
        full = tidy(" ".join(YA[k] for k in keys))
        if whole:
            out, tail = full, ""
        else:
            e = EXC.get((ref, hashlib.sha1(letters(arabic).encode()).hexdigest()[:10]))
            if not e:
                sys.exit(f"no excerpt defined for {ref}")
            starts = e["start"] if isinstance(e["start"], list) else [e["start"]]
            ends = e["end"] if isinstance(e["end"], list) else [e["end"]]
            out, tail = " \u2026 ".join(cut(full, tidy(a), tidy(b)) for a, b in zip(starts, ends)), e["tail"]
        out = re.sub(r"[;:,\u2014\s-]+$", "", out)
        return out + tail

    sums = {}
    changed = 0
    for f in sorted({r[0] for r in rows}):
        text = f.read_text(encoding="utf-8")
        head = dict(re.findall(r"^(type|slug): (.+)$", text, flags=re.M))
        jf = REPO / "content" / ("pages" if head["type"] == "page" else "posts") / f"{head['slug']}.json"
        data = json.loads(jf.read_text(encoding="utf-8"))
        lines = text.splitlines()
        for ff, n, ref, arabic, tr, keys, whole in rows:
            if ff != f:
                continue
            new = new_text(ref, arabic, keys, whole)
            old_html = '<p class="ayah-translation">\u201c' + article.inline(tr) + '\u201d</p>'
            new_html = '<p class="ayah-translation">\u201c' + article.inline(new) + '\u201d</p>'
            if old_html not in data["content"]:
                sys.exit(f"{jf.name}: card for {ref} not found in the published content")
            data["content"] = data["content"].replace(old_html, new_html, 1)
            lines[n] = f"::ayah {ref} | {arabic} | {new}"
            changed += 1
        f.write_text("\n".join(lines) + "\n", encoding="utf-8")
        jf.write_text(json.dumps(data, ensure_ascii=False, indent=1), encoding="utf-8")
        sums[str(jf.relative_to(REPO / "content"))] = hashlib.md5(jf.read_bytes()).hexdigest()
    mf = REPO / "content/manifest.json"
    m = json.loads(mf.read_text(encoding="utf-8"))
    m["checksums"].update(sums)
    mf.write_text(json.dumps(m, indent=1), encoding="utf-8")
    print("rewrote", changed, "verse cards in", len(sums), "files")
