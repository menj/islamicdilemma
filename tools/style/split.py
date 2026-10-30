#!/usr/bin/env python3
"""Split long prose paragraphs in build/content/*.txt into paragraphs of two sentences (three at most)."""
import re, glob, sys
from pathlib import Path
ROOT = Path(__file__).resolve().parents[2]
ABBR = {"Dr", "Mr", "St", "vs", "ed", "eds", "no", "vol", "ch", "ca", "cf", "op", "cit", "al", "c", "d", "b", "ibid", "Fr"}
BOUNDARY = re.compile(r'([.!?]["”’)]*(?:\[\d+\])*)\s+(?=[A-Z“"*(\[«‘])')

def split_sentences(text):
    out, start = [], 0
    for m in BOUNDARY.finditer(text):
        before = text[start:m.end(1)]
        tail = re.search(r"([\w’]+)[.!?]\S*$", before)
        word = tail.group(1) if tail else ""
        so_far = text[:m.end(1)]
        in_quote = so_far.count('"') % 2 == 1 or so_far.count("“") > so_far.count("”")
        if in_quote or word in ABBR or (len(word) == 1 and word.isupper()) or re.search(r"\{[^}]*$", before):
            continue
        out.append(text[start:m.end(1)]); start = m.end()
    out.append(text[start:])
    return [s for s in out if s.strip()]

def group(n):
    if n <= 3:
        return [n]
    g = [2] * (n // 2)
    if n % 2:
        g[-1] = 3
    return g

SKIP = ("#", "- ", "|", "::", "> ", "^ ", "===", "type:", "slug:", "title:", "excerpt:", "category:", "tags:", "date:", "order:", "image:", "!callout")
def process(path):
    raw = Path(path).read_text(encoding="utf-8")
    head, sep, rest = raw.partition("\n\n")
    body, ssep, side = rest.partition("===sidebar===")
    chunks = re.split(r"\n\s*\n", body.strip("\n"))
    out = []
    for ch in chunks:
        s = ch.strip()
        if "\n" in s or s.startswith(SKIP) or re.match(r"\d+\. ", s) or not s:
            out.append(ch); continue
        lead = ""
        if s.startswith("!lead "):
            lead, s = "!lead ", s[6:]
        ss = split_sentences(s)
        if len(ss) <= 3:
            out.append(ch); continue
        if lead:  # keep the lead to three sentences; the rest become ordinary paragraphs
            out.append(lead + " ".join(ss[:3])); rest_ss = ss[3:]
        else:
            rest_ss = ss
        i = 0
        for g in group(len(rest_ss)):
            out.append(" ".join(rest_ss[i:i + g])); i += g
    new = head + sep + "\n\n".join(out) + "\n" + (("\n" + ssep + side) if ssep else "")
    if new != raw:
        Path(path).write_text(new, encoding="utf-8")
        return True
    return False

if __name__ == "__main__":
    files = sys.argv[1:] or sorted(glob.glob(str(ROOT / "build/content/*.txt")))
    print(sum(process(f) for f in files), "files changed")
