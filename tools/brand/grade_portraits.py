"""House portrait grade: crop 4:5, clean, upscale, neutralise cast, warm heritage tone, vignette, sharpen."""
import sys, numpy as np
from PIL import Image, ImageFilter
import os
S = os.environ.get("PORTRAIT_SRC", "./") # folder holding the original photos
JOBS = {  # name: (source, crop box l,t,r,b, denoise)
    "gary-miller": (S+"photos-in/gary-miller.jpg", (360, 0, 660, 375), 2),
    "said-abdul-latif-hilarion-heagy": (S+"photos-in/hillarion-heagy.jpg", (318, 14, 518, 264), 1),
    "murad-wilfried-hofmann": (S+"commons-dl/people/murad-hofmann.jpg", (60, 0, 900, 1050), 0),
    "yusuf-islam-cat-stevens": (S+"commons-dl/people/yusuf-islam.jpg", (70, 0, 390, 400), 1),
    "jeffrey-lang": (S+"lang-in/jeffrey-lang.webp", (360, 50, 800, 600), 1),
    "yusuf-estes": (S+"commons-dl/people/yusuf-estes.jpg", (60, 60, 900, 1110), 0),
}
W, H = 640, 800
NAVY = np.array([24, 40, 60]) / 255; PARCH = np.array([247, 236, 214]) / 255
def grade(src, box, dn):
    im = Image.open(src).convert("RGB")
    if box is None:
        w, h = im.size; cw = min(w, int(h * .8)); ch = int(cw * 1.25); im = im.crop(((w - cw) // 2, 0, (w + cw) // 2, ch))
    else: im = im.crop(box)
    for _ in range(dn): im = im.filter(ImageFilter.MedianFilter(3))
    im = im.resize((W, H), Image.LANCZOS)
    a = np.asarray(im).astype(float) / 255
    # gray-world white balance removes casts (green VHS tint, fluorescent)
    m = a.reshape(-1, 3).mean(0); a = np.clip(a * (1 + .6 * (m.mean() / m - 1)), 0, 1)
    # auto levels at 0.5% / 99.5%
    lo, hi = np.percentile(a, .5), np.percentile(a, 99.5); a = np.clip((a - lo) / (hi - lo), 0, 1)
    # gentle S-curve
    a = a + .18 * a * (1 - a) * (2 * a - 1) * -1 * -1
    a = np.clip(.5 + (a - .5) * 1.08, 0, 1)
    # heritage split-tone: luminance mapped navy→parchment, blended with reduced-saturation colour
    L = (a @ np.array([.2126, .7152, .0722]))[..., None]
    tone = NAVY + (PARCH - NAVY) * L ** .9
    desat = L + (a - L) * .55
    a = desat * .55 + tone * .45
    # soft oval vignette
    y, x = np.mgrid[0:H, 0:W]; r = np.sqrt(((x - W / 2) / (W * .62)) ** 2 + ((y - H * .42) / (H * .62)) ** 2)
    a = a * (1 - .32 * np.clip(r - .55, 0, 1) ** 1.4)[..., None]
    im = Image.fromarray((np.clip(a, 0, 1) * 255 + .5).astype("uint8"))
    return im.filter(ImageFilter.UnsharpMask(radius=1.6, percent=70 if dn else 50, threshold=2))
import os
out = sys.argv[1]; os.makedirs(out, exist_ok=True)
for name, (src, box, dn) in JOBS.items():
    if not os.path.exists(src): print("missing", src); continue
    grade(src, box, dn).save(f"{out}/{name}.webp", "WEBP", quality=82, method=6)
    print("ok", name)
