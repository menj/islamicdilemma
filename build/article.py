"""Convert the plain-text article format into block trees.

File layout:
    key: value            front matter lines until the first blank line
    (blank line)
    body ...
    ===sidebar===         optional; everything after goes into sidebar boxes

Body syntax (one block per paragraph, blocks separated by blank lines):
    ## Heading {#anchor}           level-two heading (anchor optional)
    ### Heading                    level-three heading
    !lead Text                     lead paragraph
    !callout Text                  gold callout box
    ::ayah Q 5:47 | ARABIC | translation
    ::quran ARABIC                 standalone Qur'anic Arabic (KFGQPC font)
    ::hadith ARABIC                standalone hadith Arabic (Noto Naskh)
    ::arabic ARABIC                any other standalone Arabic (Arslan)
    «q|…» «h|…» «a|…»              inline Arabic of each kind, in guillemets
    > Quotation text -- Citation   scripture quotation
    - item / 1. item               list lines (consecutive lines form one list)
    ^ note text                    footnote lines (collected under "Notes")
    ::image file.jpg | alt | caption
    ::portrait file.jpg | alt | caption   (floated, graded head-and-shoulders photo)
    ::shahada [message]                   (the testimony-of-faith box)
    Plain text                     paragraph
Inline: {Q 5:47} becomes a gold verse reference; *text* becomes <em>.
Sidebar syntax: "# Box title" starts a box, then "Label: text" gloss lines or plain lines.
"""
import re
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from blocks import AYAH, B, COL, COLS, G, H, LIST, P, QUOTE, SC

IMG = "/wp-content/themes/islamicdilemma/assets/img/"


def smart(text):
    """Typographic quotes and apostrophes for plain text written with ' and "."""
    text = re.sub(r"(\w)'(\w)", "\\1’\\2", text)          # don't, Qur'an
    text = re.sub(r"(^|[\s(\[—])'", "\\1‘", text)
    text = text.replace("'", "’")
    text = re.sub(r'(^|[\s(\[—])"', "\\1“", text)
    text = text.replace('"', "”")
    return text


ARABIC_SPAN = {"q": "ar-q", "h": "ar-h", "a": "ar"}


def inline(text):
    text = smart(text)
    # «q|…» Qur'an, «h|…» hadith, «a|…» other Arabic, kept in guillemets
    text = re.sub(r"«([qha])\|([^»]+)»", lambda m: f'<span lang="ar" dir="rtl" class="{ARABIC_SPAN[m.group(1)]}">«{m.group(2)}»</span>', text)
    text = re.sub(r"\[([^\]]+)\]\(([^)\s]+)\)", r'<a href="\2">\1</a>', text)
    text = re.sub(r"\{([A-Z1-3][^{}#]*\d[^{}]*)\}", r'<span class="ref">\1</span>', text)
    text = re.sub(r"\*\*([^*\n]+)\*\*", r"<strong>\1</strong>", text)
    text = re.sub(r"(?<![\w*])\*([^*\n]+)\*(?![\w*])", r"<em>\1</em>", text)
    return text


def parse(path):
    raw = Path(path).read_text(encoding="utf-8")
    head, _, rest = raw.partition("\n\n")
    meta = {}
    for line in head.splitlines():
        k, _, v = line.partition(":")
        meta[k.strip()] = v.strip()
    body, _, side = rest.partition("===sidebar===")
    return meta, body.strip(), side.strip()


def body_blocks(body):
    blocks, notes = [], []
    first_para = True
    for chunk in re.split(r"\n\s*\n", body):
        chunk = chunk.strip()
        if not chunk:
            continue
        lines = chunk.splitlines()
        if chunk.startswith("## "):
            m = re.match(r"## (.+?)(?:\s*\{#([\w-]+)\})?$", chunk)
            blocks.append(H(2, inline(m.group(1)), anchor=m.group(2)))
        elif chunk.startswith("### "):
            blocks.append(H(3, inline(chunk[4:])))
        elif chunk.startswith("!lead "):
            blocks.append(P(inline(chunk[6:]), "is-style-lead"))
        elif chunk.startswith("!callout "):
            blocks.append(G("is-style-callout", P(inline(chunk[9:]))))
        elif chunk.startswith("::ayah "):
            ref, arabic, tr = [s.strip() for s in chunk[7:].split("|", 2)]
            blocks.append(AYAH(arabic, inline(tr), ref))
        elif chunk.startswith("::shortcode "):
            blocks.append(SC(chunk[12:].strip()))
        elif chunk.startswith("::shahada"):
            message = chunk[9:].strip() or "default"
            blocks.append(B("islamicdilemma/shahada", {} if message == "default" else {"message": message}))
        elif chunk.strip() == "::author":
            blocks.append(B("islamicdilemma/author-box", {"variant": "full"}))
        elif chunk.startswith("::quran "):
            blocks.append(P(chunk[8:].strip(), "is-style-quran"))
        elif chunk.startswith("::hadith "):
            blocks.append(P(chunk[9:].strip(), "is-style-hadith"))
        elif chunk.startswith("::arabic "):
            blocks.append(P(chunk[9:].strip(), "is-style-arabic"))
        elif chunk.startswith("::portrait "):
            file, alt, caption = [s.strip() for s in chunk[11:].split("|", 2)]
            blocks.append(B("image", {"url": IMG + file, "alt": alt, "caption": caption, "className": "portrait",
                                      "align": "right", "sizeSlug": "full"}))
        elif chunk.startswith("::image "):
            file, alt, caption = [s.strip() for s in chunk[8:].split("|", 2)]
            blocks.append(B("image", {"url": IMG + file, "alt": alt, "caption": caption, "className": "plate",
                                      "aspectRatio": "16/7", "scale": "cover", "sizeSlug": "full"}))
        elif chunk.startswith("> "):
            text, _, cite = " ".join(l[2:] if l.startswith("> ") else l for l in lines).rpartition(" -- ")
            # Bible quotations get the Bible style and fonts; anything else (Qur'an translations, creeds, historians) does not.
            bible = not cite.startswith("Q ") and re.match(r"(?:[123] )?[A-Z][a-z]+(?: of [A-Z][a-z]+)? \d+:", cite)
            blocks.append(QUOTE(inline(text), cite, "is-style-bible" if bible else "is-style-source"))
        elif all(re.match(r"(- |\d+\. )", l) for l in lines):
            ordered = lines[0][0].isdigit()
            blocks.append(LIST([inline(re.sub(r"^(- |\d+\. )", "", l)) for l in lines], ordered=ordered))
        elif chunk.startswith("^ "):
            notes += [inline(l[2:]) for l in lines]
        else:
            text = inline(" ".join(lines))
            blocks.append(P(text, "has-drop-cap" if first_para and len(text) > 200 and not blocks else None))
        first_para = False
    if notes:
        # Footnote markers in the body are written as [1], [2] ...
        blocks.append(H(2, "Notes", "notes-title"))
        blocks.append(LIST([f'{n} <a href="#r{i}" id="n{i}" aria-label="Back to text">↩</a>' for i, n in enumerate(notes, 1)],
                           ordered=True, c="is-style-notes"))
    return blocks


def sidebar_blocks(side):
    boxes, current = [], None
    for line in side.splitlines():
        line = line.strip()
        if not line:
            continue
        if line.startswith("# "):
            current = [H(2, line[2:], "side-title")]
            boxes.append(current)
        else:
            label, sep, text = line.partition(": ")
            if sep and len(label) < 24 and "<" not in label:
                current.append(P(f"<strong>{label}</strong> {inline(text)}", "gloss"))
            else:
                current.append(P(inline(line), "gloss"))
    return [G("is-style-side-box", *box) for box in boxes]


def footnote_refs(blocks):
    """Turn [1] markers into linked superscripts, in order of appearance."""
    import json
    text = json.dumps(blocks, ensure_ascii=False)
    text = re.sub(r"\[(\d+)\]", lambda m: f'<sup class=\\"n\\"><a href=\\"#n{m.group(1)}\\" id=\\"r{m.group(1)}\\">{m.group(1)}</a></sup>', text)
    return json.loads(text)


def build(path):
    meta, body, side = parse(path)
    main = footnote_refs(body_blocks(body))
    blocks = []
    if meta.get("image"):
        file, alt, caption = [s.strip() for s in meta["image"].split("|", 2)]
        blocks.append(B("image", {"url": IMG + file, "alt": alt, "caption": caption, "align": "wide", "className": "plate wide",
                                  "aspectRatio": "16/6", "scale": "cover", "sizeSlug": "full"}))
    if meta.get("type") == "page":
        blocks.append(COLS("article-grid", COL("64%", *main),
                           COL("36%", G("sidebar", SC("[islamicdilemma_toc]"), *sidebar_blocks(side))), align="wide"))
    else:
        blocks += main  # posts: the single template supplies the sidebar
    return meta, blocks


def word_count(path):
    """English words in the body (Arabic lines and front matter excluded)."""
    _, body, _ = parse(path)
    text = re.sub(r"::ayah [^|]*\|[^|]*\|", "", body)
    text = re.sub(r"::(arabic|quran|hadith) .*", "", text)
    text = re.sub(r"[؀-ۿ]+", "", text)
    text = re.sub(r"<[^>]+>|\{|\}|[#>*^!]|::\w+|--", " ", text)
    return len(re.findall(r"[A-Za-zÀ-ž’'ʿʾḥṣṭẓḍāīū]+(?:[-’'][A-Za-zÀ-ž]+)*|\d+(?::\d+)?", text))
