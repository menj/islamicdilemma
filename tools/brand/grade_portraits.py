#!/usr/bin/env python3
"""House grade for the testimony portraits.

Crops each source photo to 4:5, cleans it, corrects the colour cast, applies a warm navy-to-parchment
tone, a soft vignette and light sharpening, and writes a 640x800 WebP named after the person.

The source photos are not kept in the repository. Put them in one folder (the file names below) and run:

    python3 tools/brand/grade_portraits.py --src /path/to/originals --out assets/img [name ...]

Sources that are missing are skipped, so the script can regenerate any subset.
"""
import argparse
import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageFilter

# name: (source file inside --src, crop box left/top/right/bottom, denoise passes)
JOBS = {
    "gary-miller": ("gary-miller.jpg", (360, 0, 660, 375), 2),
    "said-abdul-latif-hilarion-heagy": ("hillarion-heagy.jpg", (318, 14, 518, 264), 1),
    "murad-wilfried-hofmann": ("murad-hofmann.jpg", (60, 0, 900, 1050), 0),
    "yusuf-islam-cat-stevens": ("yusuf-islam.jpg", (70, 0, 390, 400), 1),
    "jeffrey-lang": ("jeffrey-lang.webp", (360, 50, 800, 600), 1),
    "idris-tawfiq": ("idris-tawfiq.jpg", (65, 0, 465, 500), 0),
    "jerald-dirks": ("jerald-dirks.jpg", (215, 0, 1135, 1150), 0),
    "yusuf-estes": ("yusuf-estes.jpg", (60, 60, 900, 1110), 0),
}
WIDTH, HEIGHT = 640, 800
NAVY = np.array([24, 40, 60]) / 255
PARCHMENT = np.array([247, 236, 214]) / 255


def grade(src, box, denoise):
    image = Image.open(src).convert("RGB").crop(box)
    for _ in range(denoise):
        image = image.filter(ImageFilter.MedianFilter(3))
    pixels = np.asarray(image.resize((WIDTH, HEIGHT), Image.LANCZOS)).astype(float) / 255

    # Gray-world white balance, softened, removes casts such as a green video tint.
    means = pixels.reshape(-1, 3).mean(0)
    pixels = np.clip(pixels * (1 + 0.6 * (means.mean() / means - 1)), 0, 1)

    # Auto levels at the 0.5% and 99.5% percentiles, then a gentle S-curve.
    low, high = np.percentile(pixels, 0.5), np.percentile(pixels, 99.5)
    pixels = np.clip((pixels - low) / (high - low), 0, 1)
    pixels = pixels + 0.18 * pixels * (1 - pixels) * (2 * pixels - 1)
    pixels = np.clip(0.5 + (pixels - 0.5) * 1.08, 0, 1)

    # Split tone: luminance mapped from navy to parchment, blended with reduced-saturation colour.
    luma = (pixels @ np.array([0.2126, 0.7152, 0.0722]))[..., None]
    tone = NAVY + (PARCHMENT - NAVY) * luma ** 0.9
    pixels = (luma + (pixels - luma) * 0.55) * 0.55 + tone * 0.45

    # Soft oval vignette.
    y, x = np.mgrid[0:HEIGHT, 0:WIDTH]
    radius = np.sqrt(((x - WIDTH / 2) / (WIDTH * 0.62)) ** 2 + ((y - HEIGHT * 0.42) / (HEIGHT * 0.62)) ** 2)
    pixels = pixels * (1 - 0.32 * np.clip(radius - 0.55, 0, 1) ** 1.4)[..., None]

    graded = Image.fromarray((np.clip(pixels, 0, 1) * 255 + 0.5).astype("uint8"))
    return graded.filter(ImageFilter.UnsharpMask(radius=1.6, percent=70 if denoise else 50, threshold=2))


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("names", nargs="*", help="portraits to build (default: all)")
    parser.add_argument("--src", default=".", help="folder holding the original photos")
    parser.add_argument("--out", default="assets/img", help="folder for the graded WebP files")
    args = parser.parse_args()

    out = Path(args.out)
    out.mkdir(parents=True, exist_ok=True)
    unknown = [n for n in args.names if n not in JOBS]
    if unknown:
        sys.exit("unknown portrait: " + ", ".join(unknown))
    for name in args.names or JOBS:
        filename, box, denoise = JOBS[name]
        src = Path(args.src) / filename
        if not src.exists():
            print("missing", src)
            continue
        grade(src, box, denoise).save(out / f"{name}.webp", quality=82, method=6)
        print("ok", name)


if __name__ == "__main__":
    main()
