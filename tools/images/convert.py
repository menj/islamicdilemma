"""Convert the site's JPEG and PNG content images in assets/img/ to WebP and update every reference.

  python3 -I tools/images/convert.py <repo>

Photographs are saved at their current size (never upscaled, at most 1600 px wide) as WebP quality 82.
AVIF files are left alone. Favicons and app icons in assets/brand/ stay PNG, ICO and SVG: browsers and
web app manifests require those formats. References in content, patterns, templates, PHP, CSS and the
credits file are rewritten, and the content manifest checksums are refreshed.
"""
import hashlib, json, re, sys
from pathlib import Path
from PIL import Image

REPO = Path(sys.argv[1]).resolve()
IMG = REPO / "assets/img"
MAX_W = 1600
names = {}
for f in sorted(IMG.iterdir()):
    if f.suffix.lower() in (".jpg", ".jpeg", ".png"):
        im = Image.open(f)
        im = im.convert("RGBA" if im.mode in ("RGBA", "LA", "P") and f.suffix.lower() == ".png" else "RGB")
        if im.width > MAX_W:
            im = im.resize((MAX_W, round(im.height * MAX_W / im.width)), Image.LANCZOS)
        out = f.with_suffix(".webp")
        im.save(out, "WEBP", quality=82, method=6)
        names[f.name] = out.name
        print(f"{f.name:40} {f.stat().st_size // 1024:5} KB -> {out.name:40} {out.stat().st_size // 1024:4} KB")

skip = {".git", "node_modules", "design", "cache"}
targets = [p for p in REPO.rglob("*") if p.is_file() and not (set(p.relative_to(REPO).parts) & skip)
           and p.suffix.lower() in (".txt", ".json", ".php", ".py", ".html", ".css", ".js", ".md") and p.stat().st_size < 3_000_000]
changed = {}
for p in targets:
    try:
        s = p.read_text(encoding="utf-8")
    except UnicodeDecodeError:
        continue
    new = s
    for old, nw in names.items():
        new = re.sub(r"(?<![A-Za-z0-9_])" + re.escape(old) + r"(?![A-Za-z0-9])", nw, new)
    if new != s:
        p.write_text(new, encoding="utf-8")
        changed[str(p.relative_to(REPO))] = 1
for old in names:
    (IMG / old).unlink()
m = json.loads((REPO / "content/manifest.json").read_text(encoding="utf-8"))
for f in list(m["checksums"]):
    m["checksums"][f] = hashlib.md5((REPO / "content" / f).read_bytes()).hexdigest()
(REPO / "content/manifest.json").write_text(json.dumps(m, indent=1), encoding="utf-8")
print(f"\n{len(names)} images converted; references updated in {len(changed)} files:")
for k in sorted(changed):
    print("  ", k)
