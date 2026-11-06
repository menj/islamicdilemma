"""Pull the word-by-word morphology of chosen verses from the Quranic Arabic Corpus.

The Corpus (corpus.quran.com, University of Leeds, GNU GPL) tags every word of the Qur'an with its
root, lemma, part of speech and grammatical features. Its "Java API" (JQuranTree) is a Java library
that carries only the Tanzil text; the morphology is served as HTML on the word-by-word pages, which
this script reads. The output is a compact JSON file, one record per word, for the verses the site's
articles discuss, so the grammar quoted on the site can be checked against the Corpus at any time.

  python3 -I tools/verify/corpus_morphology.py <repo> fetch 5:48 5:47 ...   write tools/verify/corpus-morphology.json
  python3 -I tools/verify/corpus_morphology.py <repo> show 5:48             print one verse from the stored file

Each word record: location "5:48:7", transliteration, gloss, dictionary root (Buckwalter, as the
Corpus writes it), and the list of morphological segments, each with its tag and description.
"""
import html, json, re, sys, time, urllib.parse, urllib.request
from pathlib import Path

REPO = Path(sys.argv[1]); MODE = sys.argv[2]
OUT = REPO / "tools/verify/corpus-morphology.json"
CACHE = REPO / "tools/verify/cache"; CACHE.mkdir(parents=True, exist_ok=True)
UA = {"User-Agent": "islamicdilemma-verify"}

ROW = re.compile(r'<tr><td><span class="location">\((\d+):(\d+):(\d+)\)</span><br/>'
                 r'(?:<a href="/?qurandictionary\.jsp\?q=([^#"]*)#[^"]*">([^<]*)</a>|<span class="phonetic">([^<]*)</span>)<br/>(.*?)</td>'
                 r'<td class="ic">.*?</td><td class="col3">(.*?)</td></tr>', re.S)
SEG = re.compile(r'<b class="seg\w*">([A-Z]+)</b>\s*&ndash;\s*(.*?)(?=<b class="seg|<div class="arabicGrammar">|$)', re.S)


def fetch(key):
    s, a = key.split(":")
    f = CACHE / f"corpus-{s}_{a}.html"
    if not f.exists():
        url = f"https://corpus.quran.com/wordbyword.jsp?chapter={s}&verse={a}"
        f.write_bytes(urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=60).read())
        time.sleep(1)  # be polite to a volunteer-run site
    return f.read_text(encoding="utf-8")


def clean(fragment):
    return html.unescape(re.sub(r"<[^>]+>", "", fragment)).strip()


def words(key):
    out = []
    for s, a, w, root, translit, plain, gloss, col3 in ROW.findall(fetch(key)):
        if f"{s}:{a}" != key:
            continue
        translit = translit or plain  # particles have no dictionary entry, only a phonetic span
        arabic_grammar = re.search(r'<div class="arabicGrammar">(.*?)</div>', col3, re.S)
        segs = [{"tag": tag, "description": clean(desc)} for tag, desc in SEG.findall(col3.split('<div class="arabicGrammar">')[0])]
        out.append({"location": f"{s}:{a}:{w}", "transliteration": clean(translit), "gloss": clean(gloss),
                    "dictionary": urllib.parse.unquote(html.unescape(root)), "segments": segs,
                    "arabic_grammar": clean(arabic_grammar.group(1).replace("<br/>", " | ")) if arabic_grammar else ""})
    # Every word the page lists must have parsed, and the word numbers must run 1..N without gaps;
    # otherwise a layout change would silently store a partial verse as if it were complete.
    page = fetch(key)
    listed = sorted({int(w) for s, a, w in re.findall(r'<span class="location">\((\d+):(\d+):(\d+)\)</span>', page) if f"{s}:{a}" == key})
    parsed = [int(w["location"].rsplit(":", 1)[1]) for w in out]
    expected = list(range(1, (max(listed) if listed else 0) + 1))
    if not out or parsed != expected:
        missing = sorted(set(expected) - set(parsed))
        raise SystemExit(f"{key}: parsed {len(parsed)} of {len(expected)} words (missing {missing or 'none'}); "
                         "the Corpus page layout may have changed")
    return out


def sort_key(k):
    return [int(x) for x in k.split(":")]


if MODE == "fetch":
    data = json.loads(OUT.read_text(encoding="utf-8"))["verses"] if OUT.exists() else {}
    for key in sys.argv[3:]:
        data[key] = words(key)
        print(key, len(data[key]), "words")
    data = {k: data[k] for k in sorted(data, key=sort_key)}
    OUT.write_text(json.dumps({"source": "Quranic Arabic Corpus, corpus.quran.com (University of Leeds), GNU GPL",
                               "fetched": time.strftime("%Y-%m-%d"), "verses": data}, ensure_ascii=False, indent=1) + "\n",
                   encoding="utf-8")
elif MODE == "show":
    data = json.loads(OUT.read_text(encoding="utf-8"))["verses"]
    for w in data[sys.argv[3]]:
        print(f'{w["location"]:>10}  {w["transliteration"]:<16} {w["gloss"]:<28} [{w["dictionary"]}]')
        for sgm in w["segments"]:
            print(f'{"":>12}{sgm["tag"]:<6} {sgm["description"]}')
