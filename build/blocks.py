"""Tiny helpers for describing WordPress block trees as JSON.

serialize.js turns these trees into block markup using WordPress itself.
"""


def B(name, attrs=None, *inner):
    node = {"name": name if "/" in name else "core/" + name, "attributes": attrs or {}}
    kids = [i for i in inner if i]
    if kids:
        node["innerBlocks"] = kids
    return node


def cls(*names):
    return " ".join(n for n in names if n)


def P(text, c=None, **extra):
    attrs = {"content": text}
    if c:
        attrs["className"] = c
    attrs.update(extra)
    return B("paragraph", attrs)


def H(level, text, c=None, anchor=None):
    attrs = {"level": level, "content": text}
    if c:
        attrs["className"] = c
    if anchor:
        attrs["anchor"] = anchor
    return B("heading", attrs)


def G(c, *inner, tag="div", layout=None, **extra):
    attrs = {"tagName": tag, "layout": layout or {"type": "default"}}
    if c:
        attrs["className"] = c
    attrs.update(extra)
    return B("group", attrs, *inner)


def WRAP(c, *inner, tag="div", width="1160px"):
    """A full-width band whose content is centred at the site width."""
    return G(c, *inner, tag=tag, layout={"type": "constrained", "contentSize": width})


def FLEX(c, *inner, justify="left", wrap=True, vertical=None):
    layout = {"type": "flex", "flexWrap": "wrap" if wrap else "nowrap", "justifyContent": justify}
    if vertical:
        layout["verticalAlignment"] = vertical
    return G(c, *inner, layout=layout)


def COLS(c, *columns, align=None, stack=True):
    attrs = {"isStackedOnMobile": stack}
    if c:
        attrs["className"] = c
    if align:
        attrs["align"] = align
    return B("columns", attrs, *columns)


def COL(width=None, *inner, c=None):
    attrs = {}
    if width:
        attrs["width"] = width
    if c:
        attrs["className"] = c
    return B("column", attrs, *inner)


def LIST(items, ordered=False, c=None):
    attrs = {"ordered": ordered}
    if c:
        attrs["className"] = c
    return B("list", attrs, *[B("list-item", {"content": t}) for t in items])


def SC(code):
    return B("shortcode", {"text": code})


def AYAH(arabic, translation, ref):
    """Arabic verse card: Arabic, translation, reference."""
    return G("is-style-ayah", P(arabic, "is-style-arabic"), P("“" + translation + "”", "ayah-translation"),
             P(ref, "is-style-verse-ref"))


def QUOTE(text, cite, c="is-style-bible"):
    return B("quote", {"className": c, "citation": cite}, P(text))


def BUTTONS(*buttons):
    return B("buttons", {}, *buttons)


def BUTTON(text, url, c=None):
    attrs = {"text": text, "url": url}
    if c:
        attrs["className"] = c
    return B("button", attrs)


def REF(text):
    return '<span class="ref">' + text + "</span>"
