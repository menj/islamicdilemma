"""Re-encode heavy WebP images in assets/img/ to a size budget (default 150 KB), by lowering quality and then width.

  python3 -I tools/images/slim.py <repo> [max_kb]

Images already under the budget are left alone. Portraits and other small files are unaffected.
"""
import sys
from pathlib import Path
from PIL import Image

REPO = Path(sys.argv[1]).resolve()
BUDGET = int(sys.argv[2]) * 1024 if len(sys.argv) > 2 else 150 * 1024
before = after = 0
for f in sorted((REPO / "assets/img").glob("*.webp")):
    size = f.stat().st_size
    before += size
    if size <= BUDGET:
        after += size
        continue
    im = Image.open(f).convert("RGB")
    done = False
    for width in (1280, 1200, 1100, 1000):
        cur = im if im.width <= width else im.resize((width, round(im.height * width / im.width)), Image.LANCZOS)
        for q in (72, 66, 60, 54):
            cur.save(f, "WEBP", quality=q, method=6)
            if f.stat().st_size <= BUDGET:
                done = True
                break
        if done:
            break
    after += f.stat().st_size
    print(f"{f.name:60} {size // 1024:4} KB -> {f.stat().st_size // 1024:4} KB ({cur.width}px, q{q})")
print(f"total {before / 1e6:.1f} MB -> {after / 1e6:.1f} MB")
