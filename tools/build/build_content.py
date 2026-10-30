"""Build the theme's JSON content files.

Step 1 (trees):   python3 build_content.py trees <trees-dir>
Step 2 (serialize with WordPress): node serialize.js <trees-dir> <markup-dir>
Step 3 (json):    python3 build_content.py json <markup-dir> <theme-dir>
"""
import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import article

HERE = Path(__file__).parent
CONTENT = HERE / "content"

CATEGORIES = [
    {"slug": "quranic-studies", "name": "Qur'anic Studies",
     "description": "Close readings of the Qur'anic passages cited in the Islamic Dilemma debate, in Arabic, in context and with the classical commentaries."},
    {"slug": "biblical-texts", "name": "Biblical Texts",
     "description": "The Gospel, the New Testament and the history of the biblical text, read with Christian scholarship."},
    {"slug": "replies-and-rejoinders", "name": "Replies and Rejoinders",
     "description": "Answers to the follow-up claims made alongside the Islamic Dilemma, about Islam, sin, salvation and the nature of God."},
]
CAT_BY_NAME = {c["name"]: c["slug"] for c in CATEGORIES}

# Articles are dropped from URL slugs.
STOPWORDS = {"a", "an", "the"}


def slugify(name):
    import re, unicodedata
    plain = unicodedata.normalize("NFKD", name).encode("ascii", "ignore").decode().lower().replace("'", "")
    words = [w for w in re.split(r"[^a-z0-9]+", plain) if w and w not in STOPWORDS]
    return "-".join(words)

# Pages whose content comes from their template, not from an article file.
STATIC_PAGES = [
    {"type": "page", "slug": "home", "title": "Home", "excerpt": "", "order": 0, "blocks": []},
    {"type": "page", "slug": "enrichment", "title": "Enrichment",
     "excerpt": "Commentary, close readings and replies.", "order": 4, "blocks": []},
]


def items():
    for s in STATIC_PAGES:
        yield dict(s)
    for f in sorted(CONTENT.glob("*.txt")):
        meta, blocks = article.build(f)
        meta["blocks"] = blocks
        meta["words"] = article.word_count(f)
        yield meta


def cmd_trees(out):
    out = Path(out)
    out.mkdir(parents=True, exist_ok=True)
    for it in items():
        name = f'{it["type"]}--{it["slug"]}.json'
        (out / name).write_text(json.dumps(it["blocks"], ensure_ascii=False), encoding="utf-8")
    print("trees written to", out)


def cmd_json(markup_dir, theme_dir):
    markup_dir, theme = Path(markup_dir), Path(theme_dir)
    dest = theme / "content"
    for sub in ("pages", "posts"):
        (dest / sub).mkdir(parents=True, exist_ok=True)
        for old in (dest / sub).glob("*.json"):
            old.unlink()
    manifest = {"version": 1, "pages": [], "posts": []}
    tags = {}
    for it in items():
        html = (markup_dir / f'{it["type"]}--{it["slug"]}.html').read_text(encoding="utf-8").strip()
        # Theme and site URLs are filled in at import time, so the content works on any domain or subfolder.
        html = html.replace("/wp-content/themes/islamicdilemma/", "{{theme_uri}}/")
        html = html.replace('href="/', 'href="{{home_url}}/')
        record = {
            "slug": it["slug"],
            "title": article.smart(it["title"]).strip("“”") if it["title"].startswith('"') is False else article.smart(it["title"]),
            "excerpt": article.smart(it.get("excerpt", "")),
            "status": "publish",
            "content": html,
        }
        if it["type"] == "page":
            record["menu_order"] = int(it.get("order", 0))
            path = dest / "pages" / f'{it["slug"]}.json'
            manifest["pages"].append(f'pages/{it["slug"]}.json')
        else:
            # "date: 2026-10-30" publishes at 09:00; "date: 2026-10-30 00:30" sets the time.
            date = it.get("date", "")
            record["date"] = (date + ":00" if " " in date else date + " 09:00:00") if date else ""
            if record["date"] and record["date"] > __import__("datetime").datetime.now().strftime("%Y-%m-%d %H:%M:%S"):
                print(f'WARNING: {it["slug"]} is dated {record["date"]}, in the future; WordPress will schedule it instead of publishing it')
            record["category"] = CAT_BY_NAME[it["category"]]
            record["tags"] = [article.smart(t.strip()) for t in it.get("tags", "").split(",") if t.strip()]
            for name in record["tags"]:
                tags[name] = slugify(name)
            path = dest / "posts" / f'{it["slug"]}.json'
            manifest["posts"].append(f'posts/{it["slug"]}.json')
        path.write_text(json.dumps(record, ensure_ascii=False, indent=1), encoding="utf-8")
    (dest / "terms.json").write_text(json.dumps({"categories": [dict(c, name=article.smart(c["name"]), description=article.smart(c["description"])) for c in CATEGORIES],
                                                 "tags": [{"slug": s, "name": n} for n, s in sorted(tags.items())]},
                                                ensure_ascii=False, indent=1), encoding="utf-8")
    (dest / "settings.json").write_text(json.dumps({"front_page": "home", "posts_page": "enrichment", "permalink_structure": "/%postname%/",
                                                    "blogname": "Islamic Dilemma Is No Dilemma", "blogdescription": "A Muslim reply to the “Islamic Dilemma” argument",
                                                    "posts_per_page": 10}, indent=1), encoding="utf-8")
    manifest["terms"] = "terms.json"
    manifest["settings"] = "settings.json"
    # Per-file checksums: any content change changes the manifest, which is what the theme checks first.
    import hashlib
    manifest["checksums"] = {f: hashlib.md5((dest / f).read_bytes()).hexdigest()
                             for f in manifest["pages"] + manifest["posts"] + ["terms.json", "settings.json"]}
    (dest / "manifest.json").write_text(json.dumps(manifest, indent=1), encoding="utf-8")
    print("content json:", len(manifest["pages"]), "pages,", len(manifest["posts"]), "posts")


if __name__ == "__main__":
    if sys.argv[1] == "trees":
        cmd_trees(sys.argv[2])
    else:
        cmd_json(sys.argv[2], sys.argv[3])
