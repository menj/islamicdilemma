"""Put the fetched photographs into the articles: a featured image where there is none, then three more
placed before the section headings nearest 25%, 50% and 75% of the way through, each with alt text and a caption
that carries the credit. Edits tools/build/content/post-*.txt and the published content/posts/*.json together.

  python3 -I tools/images/apply.py <repo>
"""
import hashlib, html, json, re, sys
from pathlib import Path

REPO = Path(sys.argv[1]).resolve()
sys.path.insert(0, str(REPO / "tools/build"))
import article

ONLY = sys.argv[2:]  # optional: only these article slugs
PICKS = {a: v for a, v in json.loads((REPO / "tools/images/picks.json").read_text(encoding="utf-8")).items() if not ONLY or a in ONLY}
CREDITS = json.loads((REPO / "assets/img/credits.json").read_text(encoding="utf-8"))
SLUG_OF = {}
for f in (REPO / "tools/build/content").glob("post-*.txt"):
    SLUG_OF[f.stem[5:]] = f


def smart_html(t):
    return "".join(x if x.startswith("<") else article.smart(x) for x in re.split(r"(<[^>]+>)", t))


def caption(p):
    c = CREDITS[p["name"] + ".webp"]
    credit = c["credit"]
    if credit.endswith("via Wikimedia Commons"):
        lic = credit[: -len(", via Wikimedia Commons")]
        if c["license"] and "," in lic:
            who, _, name = lic.rpartition(", ")
            lic = f'{who}, <a href="{c["license"]}">{name}</a>' if who else f'<a href="{c["license"]}">{name}</a>'
        credit = (lic + ", " if lic else "") + f'via <a href="{c["source"]}">Wikimedia Commons</a>'
        if c["credit"].lower().startswith("public domain"):
            credit = f'Public domain, via <a href="{c["source"]}">Wikimedia Commons</a>'
    else:
        site = "Pexels" if "Pexels" in credit else "Pixabay"
        credit = f'Photo: <a href="{c["source"]}">{c["creator"] or "Unknown"} / {site}</a>'
    return smart_html(f'{p["subject"]}. {credit}')


def block(p, wide=False):
    cap, alt = caption(p), article.smart(p["subject"]).replace('"', "&quot;")
    ratio, cls = ("16/6", "plate wide") if wide else ("16/7", "plate")
    attrs = {"aspectRatio": ratio, "scale": "cover", "sizeSlug": "full", **({"align": "wide"} if wide else {}), "className": cls}
    fig = ("wp-block-image alignwide size-full plate wide" if wide else "wp-block-image size-full plate")
    return (f"<!-- wp:image {json.dumps(attrs, separators=(',', ':'))} -->\n"
            f'<figure class="{fig}"><img src="{{{{theme_uri}}}}/assets/img/{p["name"]}.webp" alt="{alt}" '
            f'style="aspect-ratio:{ratio};object-fit:cover"/><figcaption class="wp-element-caption">{cap}</figcaption></figure>\n'
            "<!-- /wp:image -->\n\n"), cap


H2 = re.compile(r'<!-- wp:heading \{[^}]*\} -->\n<h2 id="[^"]*" class="wp-block-heading">')
sums = {}
for art, slots in PICKS.items():
    if not slots or not all(s["name"] for s in slots):
        print(f"{art}: skipped, not every image is in place yet")
        continue
    f = SLUG_OF[art]
    txt = f.read_text(encoding="utf-8")
    head = dict(re.findall(r"^(\w+): (.*)$", txt.split("\n\n", 1)[0], flags=re.M))
    jf = REPO / "content/posts" / f"{head['slug']}.json"
    data = json.loads(jf.read_text(encoding="utf-8"))
    body = data["content"]
    has_featured = body.lstrip().startswith('<!-- wp:image {"aspectRatio":"16/6"')
    if "::image " in txt:
        print(f"{art}: already placed")
        continue
    featured, inline = (None, slots) if has_featured else (slots[0], slots[1:])
    # section starts: headings in the JSON (the Notes heading is not a content section)
    starts = [m.start() for m in H2.finditer(body) if "notes-title" not in body[m.start(): m.start() + 200]]
    n = len(starts)
    total = len(body)
    chosen = []
    for frac in (0.25, 0.5, 0.75)[: len(inline)]:
        best = min((i for i in range(1, n - 1) if i not in chosen), key=lambda i: abs(starts[i] / total - frac), default=None)
        if best is not None:
            chosen.append(best)
    chosen.sort()
    # insert from the end so offsets stay valid
    lines = txt.split("\n")
    heads_txt = [i for i, l in enumerate(lines) if l.startswith("## ")]
    for idx, p in sorted(zip(chosen, inline), reverse=True):
        blk, cap = block(p)
        body = body[: starts[idx]] + blk + body[starts[idx]:]
        lines.insert(heads_txt[idx], f'::image {p["name"]}.webp | {p["subject"]} | {cap}\n')
    if featured:
        blk, cap = block(featured, wide=True)
        body = blk + body
        meta_end = lines.index("")
        lines.insert(meta_end, f'image: {featured["name"]}.webp | {featured["subject"]} | {cap}')
    data["content"] = body
    jf.write_text(json.dumps(data, ensure_ascii=False, indent=1), encoding="utf-8")
    f.write_text("\n".join(lines), encoding="utf-8")
    sums[str(jf.relative_to(REPO / "content"))] = hashlib.md5(jf.read_bytes()).hexdigest()
    print(f"{art}: {'featured + ' if featured else ''}{len(chosen)} inline, before sections {chosen} of {n}")
mf = REPO / "content/manifest.json"
m = json.loads(mf.read_text(encoding="utf-8")); m["checksums"].update(sums)
mf.write_text(json.dumps(m, indent=1), encoding="utf-8")
