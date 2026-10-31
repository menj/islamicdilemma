"""Contact sheets of the picked photographs, for checking each pick against its subject.

  python3 -I tools/images/sheet.py <outdir> [articles per sheet]
"""
import json, sys, textwrap
from pathlib import Path
from PIL import Image, ImageDraw

HERE = Path(__file__).parent
REPO = HERE.parents[1]
out = Path(sys.argv[1]); out.mkdir(parents=True, exist_ok=True)
per = int(sys.argv[2]) if len(sys.argv) > 2 else 3
picks = json.loads((HERE / "picks.json").read_text(encoding="utf-8"))
W, H, GAP, LAB = 330, 190, 10, 46
arts = list(picks)
for s in range(0, len(arts), per):
    group = arts[s:s + per]
    sheet = Image.new("RGB", (4 * (W + GAP) + GAP, len(group) * (H + LAB + GAP) + GAP), "white")
    d = ImageDraw.Draw(sheet)
    for r, art in enumerate(group):
        for c, p in enumerate(picks[art]):
            x, y = GAP + c * (W + GAP), GAP + r * (H + LAB + GAP)
            f = REPO / "assets/img" / f"{p['name']}.webp"
            if p["name"] and f.exists():
                im = Image.open(f).convert("RGB"); im.thumbnail((W * 2, H * 2))
                scale = max(W / im.width, H / im.height)
                im = im.resize((round(im.width * scale), round(im.height * scale)))
                l, t = (im.width - W) // 2, (im.height - H) // 2
                sheet.paste(im.crop((l, t, l + W, t + H)), (x, y))
            d.text((x, y + H + 2), f"{art} #{c + 1}", fill="black")
            d.text((x, y + H + 14), "\n".join(textwrap.wrap(p["subject"], 52)[:2]), fill=(60, 60, 60))
    sheet.save(out / f"sheet-{s // per + 1:02d}.png")
print("sheets:", len(range(0, len(arts), per)))
