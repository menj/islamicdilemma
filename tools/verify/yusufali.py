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
    changed = 0
    for f in sorted({r[0] for r in rows}):
        lines = f.read_text(encoding="utf-8").splitlines()
        for ff, n, ref, arabic, tr, keys, whole in rows:
            if ff == f and whole:
                text = " ".join(YA[k] for k in keys)
                lines[n] = f"::ayah {ref} | {arabic} | {text}"
                changed += 1
        f.write_text("\n".join(lines) + "\n", encoding="utf-8")
    print("rewrote", changed, "whole-verse cards")
