"""Serialize an article's block tree to WordPress block markup without a running WordPress.

serialize.js does this with the real block editor, which needs a live site. This module writes the same
markup for the few core blocks the articles use (paragraph, heading, image, group, quote, list, list-item, table),
and tools/build/check_serializer.py proves it by regenerating every article in content/ and comparing the
result with the stored markup. Use serialize.js when a block type outside this set is introduced.

  python3 tools/build/serialize_py.py <article.txt> ...   print each article's markup
"""
import html
import json
import re
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import article

# Attribute order inside the block comment follows each block's definition in WordPress.
COMMENT_ORDER = {
    "core/paragraph": ["className"],
    "core/heading": ["level", "anchor", "className"],
    "core/image": ["aspectRatio", "scale", "sizeSlug", "align", "className"],
    "core/group": ["className", "layout"],
    "core/quote": ["className"],
    "core/list": ["ordered", "className"],
    "core/list-item": [],
    "core/table": ["className"],
}


def comment(name, attrs):
    short = name.split("/")[1]
    picked = {k: attrs[k] for k in COMMENT_ORDER[name] if k in attrs}
    if name == "core/list" and not picked.get("ordered"):
        picked.pop("ordered", None)
    if name == "core/heading" and picked.get("level") == 2:
        del picked["level"]
    return json.dumps(picked, ensure_ascii=False, separators=(",", ":")) if picked else ""


def wrap(name, attrs, inner):
    short = name.split("/")[1]
    c = comment(name, attrs)
    open_ = f"<!-- wp:{short} {c} -->" if c else f"<!-- wp:{short} -->"
    return f"{open_}\n{inner}\n<!-- /wp:{short} -->"


def esc(value):
    return html.escape(value, quote=True).replace("&#x27;", "&#039;") if value else ""


BACKLINK = re.compile(r'<a href="(#r\d+)" id="(n\d+)" aria-label="Back to text">')


def fix(text):
    """The markup the editor stores: back-links list aria-label first, and a bare & written as &amp;."""
    text = re.sub(r"&(?!#?\w+;)", "&amp;", text)
    return BACKLINK.sub(r'<a aria-label="Back to text" href="\1" id="\2">', text)


def block(b):
    name, attrs = b["name"], b.get("attributes", {})
    inner_blocks = b.get("innerBlocks", [])
    if name == "core/paragraph":
        cls = attrs.get("className")
        tag = f'<p class="{cls}">' if cls else "<p>"
        return wrap(name, attrs, f'{tag}{attrs["content"]}</p>')
    if name == "core/heading":
        level = attrs.get("level", 2)
        classes = "wp-block-heading" + (f' {attrs["className"]}' if attrs.get("className") else "")
        ident = f' id="{attrs["anchor"]}"' if attrs.get("anchor") else ""
        return wrap(name, attrs, f'<h{level}{ident} class="{classes}">{attrs["content"]}</h{level}>')
    if name == "core/image":
        align = attrs.get("align")
        classes = "wp-block-image" + (f" align{align}" if align else "") + f' size-{attrs.get("sizeSlug", "full")}'
        if attrs.get("className"):
            classes += " " + attrs["className"]
        style = ""
        if attrs.get("aspectRatio"):
            style = f' style="aspect-ratio:{attrs["aspectRatio"]}' + (f';object-fit:{attrs["scale"]}' if attrs.get("scale") else "") + '"'
        cap = f'<figcaption class="wp-element-caption">{attrs["caption"]}</figcaption>' if attrs.get("caption") else ""
        return wrap(name, attrs, f'<figure class="{classes}"><img src="{attrs["url"]}" alt="{esc(attrs.get("alt", ""))}"{style}/>{cap}</figure>')
    if name == "core/group":
        cls = "wp-block-group" + (f' {attrs["className"]}' if attrs.get("className") else "")
        return wrap(name, attrs, f'<div class="{cls}">' + "\n\n".join(block(i) for i in inner_blocks) + "</div>")
    if name == "core/quote":
        cls = "wp-block-quote" + (f' {attrs["className"]}' if attrs.get("className") else "")
        cite = f'<cite>{attrs["citation"]}</cite>' if attrs.get("citation") else ""
        return wrap(name, attrs, f'<blockquote class="{cls}">' + "\n".join(block(i) for i in inner_blocks) + f"{cite}</blockquote>")
    if name == "core/list":
        tag = "ol" if attrs.get("ordered") else "ul"
        cls = "wp-block-list" + (f' {attrs["className"]}' if attrs.get("className") else "")
        return wrap(name, attrs, f'<{tag} class="{cls}">' + "\n\n".join(block(i) for i in inner_blocks) + f"</{tag}>")
    if name == "core/list-item":
        return wrap(name, attrs, f'<li>{attrs["content"]}</li>')
    if name == "core/table":
        cls = "wp-block-table" + (f' {attrs["className"]}' if attrs.get("className") else "")
        row = lambda cells: "<tr>" + "".join(f'<{c["tag"]}>{c["content"]}</{c["tag"]}>' for c in cells) + "</tr>"
        head = "".join(row(r["cells"]) for r in attrs.get("head", []))
        body = "".join(row(r["cells"]) for r in attrs.get("body", []))
        inner = (f"<thead>{head}</thead>" if head else "") + (f"<tbody>{body}</tbody>" if body else "")
        return wrap(name, attrs, f'<figure class="{cls}"><table class="has-fixed-layout">{inner}</table></figure>')
    raise ValueError(f"serialize_py does not handle {name}; use serialize.js")


def markup(blocks):
    return fix("\n\n".join(block(b) for b in blocks))


if __name__ == "__main__":
    for f in sys.argv[1:]:
        meta, blocks = article.build(Path(f))
        print(markup(blocks))
