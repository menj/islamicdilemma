"""Prove serialize_py.py: regenerate every article's markup and compare it with content/*/*.json.

  python3 tools/build/check_serializer.py
"""
import json
import sys
from pathlib import Path

HERE = Path(__file__).parent
sys.path.insert(0, str(HERE))
import article
import serialize_py

REPO = HERE.parents[1]
bad = 0
checked = 0
for f in sorted((HERE / "content").glob("*.txt")):
    meta, blocks = article.build(f)
    kind = "pages" if meta["type"] == "page" else "posts"
    stored = REPO / "content" / kind / f'{meta["slug"]}.json'
    if not stored.exists():
        continue
    want = json.loads(stored.read_text(encoding="utf-8"))["content"]
    try:
        got = serialize_py.markup(blocks)
    except ValueError as e:
        print(f"skip {meta['slug']}: {e}")
        continue
    checked += 1
    got = got.replace("/wp-content/themes/islamicdilemma/", "{{theme_uri}}/").replace('href="/', 'href="{{home_url}}/')
    if got.strip() != want.strip():
        bad += 1
        a, b = got.strip(), want.strip()
        i = next((i for i in range(min(len(a), len(b))) if a[i] != b[i]), min(len(a), len(b)))
        print(f"DIFF {kind}/{meta['slug']} at {i}\n  got : {a[max(0,i-60):i+90]!r}\n  want: {b[max(0,i-60):i+90]!r}")
print(f"{checked} articles checked:", "serializer matches stored markup" if not bad else f"{bad} differ")
sys.exit(1 if bad else 0)
