"""Check ESV quotations in the article sources against esv.org chapter text.

Usage: python3 -I esv.py <content-dir>
Finds block quotes ending "-- <ref> · ESV" and checks the quoted words appear
in that chapter (punctuation, case and quote marks ignored; "..." splits).
"""
import html, re, subprocess, sys, unicodedata
from pathlib import Path

CACHE = Path(__file__).parent / "esv"
CACHE.mkdir(exist_ok=True)

def chapter_text(book, ch):
    f = CACHE / f"{book.replace(' ', '_')}_{ch}.txt"
    if not f.exists():
        url = "https://www.esv.org/" + book.replace(" ", "+") + f"+{ch}/"
        raw = subprocess.run(["curl", "-sS", "-m", "30", url], capture_output=True, text=True).stdout
        raw = re.sub(r"(?is)<(script|style)\b.*?</\1>", "", raw)
        # drop footnote and cross-reference markers before stripping tags
        raw = re.sub(r'(?is)<sup[^>]*class="[^"]*(crossref|footnote)[^"]*"[^>]*>.*?</sup>', " ", raw)
        raw = re.sub(r'(?is)<sup[^>]*>.*?</sup>', " ", raw)
        t = html.unescape(re.sub(r"<[^>]+>", " ", raw))
        f.write_text(t)
    return f.read_text()

def norm(s):
    s = unicodedata.normalize("NFKD", s).lower()
    s = s.replace("’", "'").replace("‘", "'")
    s = re.sub(r"(?<![a-z])'|'(?![a-z])", " ", s)  # quote marks, not apostrophes
    s = re.sub(r"(?<![a-z'])[b-hj-np-z](?![a-z'])", " ", s)  # stray cross-reference letters
    s = re.sub(r"\d+", " ", s)
    s = re.sub(r"[^a-z' ]+", " ", s)
    return re.sub(r"\s+", " ", s).strip()

def check(quote, ref):
    m = re.match(r"((?:\d )?[A-Za-z]+(?: of [A-Za-z]+)?) (\d+)", ref.strip())
    if not m:
        return None, "unparsed ref"
    book, ch = m.group(1), m.group(2)
    text = norm(chapter_text(book, ch))
    missing = []
    for part in re.split(r"\.\.\.|…", quote):
        p = norm(part)
        if len(p) > 3 and p not in text:
            missing.append(part.strip())
    return not missing, missing

def main(d):
    bad = 0; n = 0
    for f in sorted(Path(d).glob("*.txt")):
        for line in f.read_text().splitlines():
            m = re.match(r'> "(.*)" -- (.+?) · ESV\s*$', line)
            if not m:
                continue
            n += 1
            ok, info = check(m.group(1), m.group(2))
            if not ok:
                bad += 1
                print(f"{f.name}: {m.group(2)} :: {info}")
    print(f"{n} ESV block quotes checked, {bad} with differences")

if __name__ == "__main__":
    main(sys.argv[1])
