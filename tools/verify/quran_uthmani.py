"""Convert and verify the site's Qur'an quotations in the Uthmani script.

The Qur'an font (KFGQPC Hafs Uthmanic Script) is designed for the Uthmani
text. This script finds each quotation by its reference, locates the quoted
words in the verse (matching on letters only, so it works from either
script), and replaces them with the exact Uthmani words from Quran.com.

  python3 -I quran_uthmani.py <repo> convert   rewrite the sources
  python3 -I quran_uthmani.py <repo> check     verify; exit 1 on any mismatch
"""
import json, re, sys, time, urllib.request
from pathlib import Path

REPO = Path(sys.argv[1]); MODE = sys.argv[2]
CACHE = Path(__file__).parent / "cache"; CACHE.mkdir(exist_ok=True)

def verse_words(key):
    f = CACHE / (key.replace(":", "_") + ".json")
    if not f.exists():
        url = f"https://api.quran.com/api/v4/verses/by_key/{key}?words=true&word_fields=text_imlaei,text_uthmani"
        f.write_text(urllib.request.urlopen(urllib.request.Request(url, headers={"User-Agent": "islamicdilemma-verify"}), timeout=30).read().decode())
        time.sleep(0.3)
    v = json.loads(f.read_text())["verse"]
    out = []
    for w in v["words"]:
        if w["char_type_name"] != "word":
            continue
        out.append((w["text_uthmani"], w["text_imlaei"]))
    return out

MARKS = re.compile(r"[ؐ-ًؚ-ٰٟۖ-ۭـ]")
def letters(s):
    s = MARKS.sub("", s)
    s = re.sub("[أإآٱ]", "ا", s).replace("ى", "ي").replace("ة", "ه").replace("ؤ", "و").replace("ئ", "ي").replace("ء", "")
    s = s.replace("ا", "")  # alif spelling differs most between the scripts
    return re.sub(r"[^ء-ي]", "", s)

def keys(ref):
    m = re.search(r"(\d+):(\d+)(?:[–-](\d+))?", ref)
    if m:
        s, a, b = int(m[1]), int(m[2]), int(m[3] or m[2])
    else:
        s, a, b = int(re.search(r"Q (\d+)", ref)[1]), 1, None
        if b is None:
            meta = json.loads(urllib.request.urlopen(f"https://api.quran.com/api/v4/chapters/{s}", timeout=30).read())
            b = meta["chapter"]["verses_count"]
    return [f"{s}:{i}" for i in range(a, b + 1)]

def to_uthmani(ref, arabic):
    """Return the Uthmani text for a quotation, or raise with a diagnosis."""
    verses = [verse_words(k) for k in keys(ref)]
    flat = [(vi, w) for vi, ws in enumerate(verses) for w in ws]
    targets = ([letters(w[1]) for _, w in flat], [letters(w[0]) for _, w in flat])
    parts = []
    for seg in [s for s in re.split(r"\s*(?:…|\.\.\.)\s*", arabic) if s.strip()]:
        tokens = [t for t in seg.replace("۝", " ").split() if letters(t)]
        want = "".join(letters(t) for t in tokens)
        found = None
        for target in targets:  # match from the simple script, or from the Uthmani itself
            for i in range(len(flat)):
                acc = ""
                for j in range(i, len(flat)):
                    acc += target[j]
                    if acc == want:
                        found = (i, j); break
                    if not want.startswith(acc):
                        break
                if found:
                    break
            if found:
                break
        if not found:
            raise ValueError(f"{ref}: cannot place «{seg}»")
        i, j = found
        words, prev = [], flat[i][0]
        for vi, w in flat[i:j + 1]:
            if vi != prev:
                words.append("۝")  # verse boundary inside a quotation
                prev = vi
            words.append(w[0])
        parts.append(" ".join(words))
    return " … ".join(parts)

def sources():
    """(path, pattern) pairs; each match yields (ref, arabic, span)."""
    for f in sorted((REPO / "build/content").glob("*.txt")):
        t = f.read_text()
        for m in re.finditer(r"^::ayah ([^|]+)\|([^|]+)\|", t, re.M):
            yield f, m.group(1).strip(), m.group(2).strip()
    tt = REPO / "build/theme_trees.py"
    t = tt.read_text()
    for m in re.finditer(r'AYAH\("([^"]+)",\s*"[^"]*",\s*"(Q [^"·]+)', t):
        yield tt, m.group(2).strip(), m.group(1)
    m = re.search(r'P\("([^"]+)", "is-style-arabic foot-arabic"', t)
    if m:
        yield tt, "Q 2:111", m.group(1)

bad = 0; n = 0; changes = {}
for f, ref, ar in sources():
    n += 1
    try:
        u = to_uthmani(ref, ar)
    except ValueError as e:
        bad += 1; print("MISMATCH", f.name, e); continue
    if MODE == "check" and u != ar:
        bad += 1; print("DIFFERS", f.name, ref, "\n  ours:", ar, "\n  uthm:", u)
    elif MODE == "convert" and u != ar:
        changes.setdefault(f, []).append((ar, u))
if MODE == "convert":
    for f, pairs in changes.items():
        t = f.read_text()
        for a, b in pairs:
            t = t.replace(a, b, 1)
        f.write_text(t)
    print(f"converted {sum(len(p) for p in changes.values())} of {n} quotations; {bad} could not be placed")
else:
    print(f"{n} Qur'an quotations checked against the Uthmani text, {bad} problems")
sys.exit(1 if bad else 0)
