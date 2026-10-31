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


def jblocks(c):
    """Offsets of the top-level blocks in published markup."""
    out, depth = [], 0
    for m in re.finditer(r"<!-- (/?)wp:([a-z0-9/-]+)[^>]*?(/?)-->", c):
        if m.group(1):
            depth -= 1
            continue
        if depth == 0:
            out.append(m.start())
        if not m.group(3):
            depth += 1
    return out


def chunks_of(body):
    """(offset, text) of each blank-line-separated chunk of an article source, footnotes left out."""
    out, pos = [], 0
    for part in re.split(r"(\n\s*\n)", body):
        if part.strip() and not part.lstrip().startswith("^ "):
            out.append((pos, part))
        pos += len(part)
    return out


def choose_positions(chunks, n):
    """Chunk indexes to put the photographs before: nearest 25/50/75% of the text, a heading when one is close,
    never straight after a heading (which would split it from its first paragraph), and well apart."""
    total = sum(len(t) for _, t in chunks)
    cum, run = [], 0
    for _, t in chunks:
        cum.append(run / total)
        run += len(t)
    heading = [t.lstrip().startswith("## ") for _, t in chunks]
    plain = [bool(re.match(r"[A-Za-z\"“*\[(]", t.lstrip())) and not t.lstrip().startswith(("::", "!", "|", "- ", "> ")) and not re.match(r"\d+\. ", t.lstrip()) for _, t in chunks]
    ok = [k for k in range(2, len(chunks) - 2) if (heading[k] or (plain[k] and not heading[k - 1])) and not chunks[k - 1][1].lstrip().startswith("::image")]
    chosen = []
    for target in (0.25, 0.5, 0.75)[:n]:
        pool = [k for k in ok if all(abs(cum[k] - cum[c]) >= 0.14 for c in chosen)]
        if not pool:
            pool = [k for k in ok if k not in chosen]
        chosen.append(min(pool, key=lambda k: abs(cum[k] - target) - (0.05 if heading[k] else 0)))
    return sorted(chosen)


sums = {}
for art, slots in PICKS.items():
    if not slots or not all(s["name"] for s in slots):
        print(f"{art}: skipped, not every image is in place yet")
        continue
    f = SLUG_OF[art]
    txt = f.read_text(encoding="utf-8")
    if "::image " in txt:
        print(f"{art}: already placed")
        continue
    head_txt, body_txt = txt.split("\n\n", 1)
    head = dict(re.findall(r"^(\w+): (.*)$", head_txt, flags=re.M))
    jf = REPO / "content/posts" / f"{head['slug']}.json"
    data = json.loads(jf.read_text(encoding="utf-8"))
    body = data["content"]
    has_featured = body.lstrip().startswith('<!-- wp:image {"aspectRatio":"16/6"')
    featured, inline = (None, slots) if has_featured else (slots[0], slots[1:])
    main_txt = body_txt.split("\n===sidebar===")[0]
    chunks = chunks_of(main_txt)
    jb = jblocks(body)
    offset = 1 if has_featured else 0
    chosen = choose_positions(chunks, len(inline))
    for k, p in sorted(zip(chosen, inline), reverse=True):
        blk, cap = block(p)
        body = body[: jb[k + offset]] + blk + body[jb[k + offset]:]
        pos = chunks[k][0]
        main_txt = main_txt[:pos] + f'::image {p["name"]}.webp | {p["subject"]} | {cap}\n\n' + main_txt[pos:]
    body_txt = main_txt + body_txt[len(body_txt.split("\n===sidebar===")[0]):]
    if featured:
        blk, cap = block(featured, wide=True)
        body = blk + body
        head_txt += f'\nimage: {featured["name"]}.webp | {featured["subject"]} | {cap}'
    data["content"] = body
    jf.write_text(json.dumps(data, ensure_ascii=False, indent=1), encoding="utf-8")
    f.write_text(head_txt + "\n\n" + body_txt, encoding="utf-8")
    sums[str(jf.relative_to(REPO / "content"))] = hashlib.md5(jf.read_bytes()).hexdigest()
    pct = [round(100 * sum(len(t) for _, t in chunks[:k]) / sum(len(t) for _, t in chunks)) for k in chosen]
    print(f"{art}: {'featured + ' if featured else ''}{len(chosen)} inline at about {pct}% of the text")
mf = REPO / "content/manifest.json"
m = json.loads(mf.read_text(encoding="utf-8")); m["checksums"].update(sums)
mf.write_text(json.dumps(m, indent=1), encoding="utf-8")
