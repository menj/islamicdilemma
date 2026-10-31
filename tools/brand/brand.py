"""Brandmark: the owner's raised fist, with the nail mark in rubric, on a parchment disc inside an eight-pointed star (khatam).
Run from the theme root: python3 tools/brand/brand.py assets/brand"""
import math, sys, json
FIST = json.load(open('tools/brand/fist.json'))
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.boundsPen import BoundsPen
from fontTools.pens.transformPen import TransformPen
OUT = sys.argv[1]
f = TTFont('assets/fonts/marcellus-normal-400-latin.woff2'); gs = f.getGlyphSet(); cmap = f.getBestCmap()
NAVY, GOLD, GOLD_L, PARCH, RUBRIC = "#18283C", "#B18B54", "#D9BC86", "#F7F4ED", "#D2452F"

def word(text, cx, cy, height, track=0.0):
    """Path data for text set in Marcellus, centred on (cx, cy), cap height = height."""
    names = [cmap[ord(c)] for c in text]
    capH = f['OS/2'].sCapHeight or 700
    s = height / capH
    xs, x = [], 0
    for n in names: xs.append(x); x += gs[n].width + track * capH
    total = x - track * capH
    # ink bounds for exact centring
    bp = BoundsPen(gs)
    for n, ox in zip(names, xs): gs[n].draw(TransformPen(bp, (1, 0, 0, 1, ox, 0)))
    x0, y0, x1, y1 = bp.bounds
    pen = SVGPathPen(gs)
    tx = cx - (x0 + x1) / 2 * s; ty = cy + (y0 + y1) / 2 * s
    for n, ox in zip(names, xs):
        gs[n].draw(TransformPen(pen, (s, 0, 0, -s, tx + ox * s, ty)))
    return pen.getCommands()

def star(cx, cy, r):
    """Eight-pointed star outline (two overlapping squares) as one polygon."""
    pts = []
    for i in range(16):
        a = math.pi / 8 * i - math.pi / 2
        rr = r if i % 2 == 0 else r * math.cos(math.pi / 4) / math.cos(math.pi / 8)
        pts.append((cx + rr * math.cos(a), cy + rr * math.sin(a)))
    return "M" + " L".join(f"{x:.2f} {y:.2f}" for x, y in pts) + " Z"

def fist(cx, cy, height, clip_r, clip_id):
    """The traced fist, scaled to height and centred; the nail mark drawn again in rubric.
    Clipped to the disc so the wrist runs off its lower edge."""
    x0, y0, x1, y1 = FIST["bounds"]; s = height / (y1 - y0)
    tx = cx - (x0 + x1) / 2 * s; ty = cy - (y0 + y1) / 2 * s
    return (f'<clipPath id="{clip_id}"><circle cx="{cx:.1f}" cy="{CY:.1f}" r="{clip_r:.1f}"/></clipPath>'
            f'<g clip-path="url(#{clip_id})"><g transform="translate({tx:.2f} {ty:.2f}) scale({s:.5f})">'
            f'<path d="{FIST["fist"]}" fill="{NAVY}" fill-rule="evenodd"/>'
            f'<path d="{FIST["nail"]}" fill="{RUBRIC}"/></g></g>')

def mark(size=512, detail=True, bg=True):
    global CY
    c = size / 2; CY = c; out = [f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {size} {size}" width="{size}" height="{size}">']
    if detail: out.append('<title>Islamic Dilemma Is No Dilemma</title>')
    if bg: out.append(f'<rect width="{size}" height="{size}" rx="{size*.22:.1f}" fill="{NAVY}"/>')
    if detail:
        out.append(f'<rect x="{size*.035:.1f}" y="{size*.035:.1f}" width="{size*.93:.1f}" height="{size*.93:.1f}" rx="{size*.19:.1f}" fill="none" stroke="{GOLD}" stroke-opacity=".55" stroke-width="{size*.008:.1f}"/>')
        out.append(f'<path d="{star(c, c, size*.43)}" fill="{GOLD}"/>')
        out.append(f'<path d="{star(c, c, size*.395)}" fill="none" stroke="{NAVY}" stroke-width="{size*.012:.1f}"/>')
        out.append(f'<circle cx="{c}" cy="{c}" r="{size*.27:.1f}" fill="{PARCH}"/>')
        out.append(f'<circle cx="{c}" cy="{c}" r="{size*.248:.1f}" fill="none" stroke="{GOLD}" stroke-width="{size*.006:.1f}"/>')
        out.append(fist(c, c + size * .07, size * .5, size * .248, 'fd'))
    else:  # simplified for 16-32px: solid star, bold monogram
        out.append(f'<path d="{star(c, c, size*.5)}" fill="{GOLD}"/>')
        out.append(f'<circle cx="{c}" cy="{c}" r="{size*.35:.1f}" fill="{PARCH}"/>')
        out.append(fist(c, c + size * .1, size * .66, size * .35, 'fs'))
    out.append('</svg>'); return "\n".join(out)

open(f"{OUT}/brandmark.svg", "w").write(mark(512, True))
open(f"{OUT}/favicon.svg", "w").write(mark(64, False))
# Full-bleed square for iOS and maskable icons (the platform rounds the corners).
sq = mark(512, True).replace(f'rx="{512*.22:.1f}" fill="{NAVY}"', f'rx="0" fill="{NAVY}"')
open(f"{OUT}/brandmark-square.svg", "w").write(sq)
