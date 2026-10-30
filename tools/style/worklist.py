#!/usr/bin/env python3
"""Print every sentence flagged N (reframe), whole, for rewriting. Usage: worklist.py [start] [count]"""
import re, sys
sys.path.insert(0, str(__import__("pathlib").Path(__file__).parent))
import lint, glob
from pathlib import Path
items = []
for f in sorted(glob.glob(str(lint.ROOT / "build/content/*.txt"))):
    lines = Path(f).read_text().splitlines()
    for ln, r, msg in lint.check(f, {"N"}):
        line = lines[ln - 1]
        ss = lint.sentences(re.sub(r"^(#+ |- |\d+\. |!lead |!callout )", "", line))
        frag = msg.split(": ", 1)[1].strip()
        hit = next((s for s in ss if frag[:12] in lint.mask(s)), ss[0] if ss else line)
        items.append((Path(f).name, ln, hit))
seen, out = set(), []
for it in items:
    if (it[0], it[2]) not in seen:
        seen.add((it[0], it[2])); out.append(it)
a = int(sys.argv[1]) if len(sys.argv) > 1 else 0
n = int(sys.argv[2]) if len(sys.argv) > 2 else len(out)
for i, (f, ln, s) in enumerate(out[a:a + n], a):
    print(f"[{i}] {f}:{ln}\n    {s}\n")
print(len(out), "sentences", file=sys.stderr)
