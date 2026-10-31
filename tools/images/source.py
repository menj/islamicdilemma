"""Find, vet and fetch photographs for the site's articles.

Sources: Wikimedia Commons (no key), Pexels and Pixabay (keys come from the environment, never from
files: PEXELS_KEY and PIXABAY_KEY). Every candidate shows its licence; only public domain, CC0 and CC BY /
CC BY-SA files from Commons, and the Pexels and Pixabay licences, are accepted.

  python3 -I tools/images/source.py search commons "codex sinaiticus" [n]
  python3 -I tools/images/source.py search pexels  "old library" [n]
  python3 -I tools/images/source.py search pixabay "mosque dome" [n]
  python3 -I tools/images/source.py fetch commons "File:Codex Sinaiticus.jpg" codex-sinaiticus-leaf
  python3 -I tools/images/source.py fetch pexels 1234567 library-shelves
  python3 -I tools/images/source.py fetch pixabay 987654 mosque-dome-light

`fetch` downloads, resizes to 1400 px wide, saves WebP in assets/img/ and records the credit in
assets/img/credits.json (creator, credit line, licence, source, subject).
"""
import io, json, os, re, sys, time, urllib.error, urllib.parse, urllib.request
from pathlib import Path
from PIL import Image

REPO = Path(__file__).resolve().parents[2]
IMG = REPO / "assets/img"
UA = {"User-Agent": "islamicdilemma-images/1.0 (https://islamicdilemma.info; contact@menj.org)"}
OK_LICENCE = re.compile(r"^(public domain|pd[- ]|cc0|cc[- ]by(?!-nc)(?!-nd)|cc[- ]by-sa|attribution)", re.I)
MAX_W = 1280  # a standard Wikimedia thumbnail width; other sizes are throttled harder


_last = {}
GAP = {"wikimedia.org": 9.0}  # seconds between requests to a host; Wikimedia throttles shared connections hard


def get(url, headers=None, binary=False):
    """GET with polite pacing (9 s between Wikimedia requests, 1.2 s otherwise), waiting out any Retry-After (HTTP 429)."""
    host = urllib.parse.urlparse(url).netloc
    gap = next((g for k, g in GAP.items() if host.endswith(k)), 1.2)
    key = next((k for k in GAP if host.endswith(k)), host)
    for attempt in range(8):
        time.sleep(max(0, gap - (time.time() - _last.get(key, 0))))
        _last[key] = time.time()
        req = urllib.request.Request(url, headers={**UA, **(headers or {})})
        try:
            data = urllib.request.urlopen(req, timeout=90).read()
            return data if binary else json.loads(data)
        except urllib.error.HTTPError as e:
            if e.code != 429 or attempt == 7:
                raise
            time.sleep(min(90, int(e.headers.get("Retry-After", "20") or 20) + 5 * (attempt + 1)))


def strip(html):
    return re.sub(r"\s+", " ", re.sub(r"<[^>]+>", "", html or "")).strip()


# ---------------------------------------------------------------- Wikimedia Commons
def clean_creator(name):
    """A short, plain credit name, or nothing when Commons gives only 'unknown' or a long free-text field."""
    name = re.sub(r"(Unknown (?:author|artist)s?)+", "", name, flags=re.I).strip(" ,;:-")
    return "" if len(name) > 70 or re.search(r"[\[\]{}]|\bsee\b|http", name, re.I) else name


def _commons_rows(pages):
    out = []
    for p in sorted(pages, key=lambda p: p.get("index", 0)):
        ii = (p.get("imageinfo") or [None])[0]
        if not ii or not ii["mime"].startswith("image/") or ii["mime"] == "image/svg+xml":
            continue
        m = ii["extmetadata"]
        lic = strip(m.get("LicenseShortName", {}).get("value", ""))
        out.append({
            "id": p["title"], "title": strip(m.get("ObjectName", {}).get("value", p["title"])),
            "desc": strip(m.get("ImageDescription", {}).get("value", ""))[:200],
            "creator": clean_creator(strip(m.get("Artist", {}).get("value", ""))), "licence": lic,
            "licence_url": strip(m.get("LicenseUrl", {}).get("value", "")), "ok": bool(OK_LICENCE.match(lic)),
            "w": ii["width"], "h": ii["height"], "download": ii.get("thumburl") or ii["url"],
            "page": ii["descriptionurl"], "credit": strip(m.get("Credit", {}).get("value", ""))[:160]})
    return out


_COMMONS = {"action": "query", "format": "json", "prop": "imageinfo", "iiprop": "url|size|mime|extmetadata", "iiurlwidth": MAX_W}


def commons_info(titles):
    d = get("https://commons.wikimedia.org/w/api.php?" + urllib.parse.urlencode({**_COMMONS, "titles": titles}))
    return _commons_rows(d["query"]["pages"].values())


def commons_search(q, n):
    """Search and read the image details in one request (generator=search)."""
    d = get("https://commons.wikimedia.org/w/api.php?" + urllib.parse.urlencode({
        **_COMMONS, "generator": "search", "gsrsearch": q + " filetype:bitmap", "gsrnamespace": 6, "gsrlimit": n}))
    return _commons_rows(d.get("query", {}).get("pages", {}).values())


# ---------------------------------------------------------------- Pexels / Pixabay
def pexels_search(q, n):
    key = os.environ.get("PEXELS_KEY") or sys.exit("PEXELS_KEY is not set")
    d = get("https://api.pexels.com/v1/search?" + urllib.parse.urlencode({"query": q, "per_page": n, "orientation": "landscape"}),
            {"Authorization": key})
    return [{"id": str(p["id"]), "title": p.get("alt") or q, "desc": p.get("alt", ""), "creator": p["photographer"],
             "licence": "Pexels licence", "licence_url": "https://www.pexels.com/license/", "ok": True, "w": p["width"], "h": p["height"],
             "download": p["src"]["large"], "page": p["url"], "credit": ""} for p in d["photos"]]


def pixabay_search(q, n):
    key = os.environ.get("PIXABAY_KEY") or sys.exit("PIXABAY_KEY is not set")
    d = get("https://pixabay.com/api/?" + urllib.parse.urlencode({"key": key, "q": q, "per_page": max(3, n), "orientation": "horizontal",
                                                                    "image_type": "photo", "safesearch": "true"}))
    return [{"id": str(p["id"]), "title": p.get("tags", q), "desc": p.get("tags", ""), "creator": p["user"],
             "licence": "Pixabay Content Licence", "licence_url": "https://pixabay.com/service/license-summary/", "ok": True,
             "w": p["imageWidth"], "h": p["imageHeight"], "download": p["largeImageURL"], "page": p["pageURL"], "credit": ""}
            for p in d["hits"][:n]]


def find(source, ident):
    if source == "commons":
        r = commons_info(ident)
    elif source == "pexels":
        key = os.environ.get("PEXELS_KEY") or sys.exit("PEXELS_KEY is not set")
        p = get(f"https://api.pexels.com/v1/photos/{ident}", {"Authorization": key})
        r = [{"id": str(p["id"]), "title": p.get("alt", ""), "creator": p["photographer"], "licence": "Pexels licence",
              "licence_url": "https://www.pexels.com/license/", "ok": True, "w": p["width"], "h": p["height"],
              "download": p["src"]["large"], "page": p["url"], "desc": p.get("alt", "")}]
    else:
        key = os.environ.get("PIXABAY_KEY") or sys.exit("PIXABAY_KEY is not set")
        p = get("https://pixabay.com/api/?" + urllib.parse.urlencode({"key": key, "id": ident}))["hits"][0]
        r = [{"id": str(p["id"]), "title": p.get("tags", ""), "creator": p["user"], "licence": "Pixabay Content Licence",
              "licence_url": "https://pixabay.com/service/license-summary/", "ok": True, "w": p["imageWidth"], "h": p["imageHeight"],
              "download": p["largeImageURL"], "page": p["pageURL"], "desc": p.get("tags", "")}]
    return r[0] if r else sys.exit("not found: " + ident)


def save_candidate(source, c, name, subject=""):
    """Download a vetted candidate, save it as WebP and record its credit."""
    if not c["ok"]:
        sys.exit(f"licence not accepted: {c['licence']}")
    im = Image.open(io.BytesIO(get(c["download"], binary=True))).convert("RGB")
    if im.width > MAX_W:
        im = im.resize((MAX_W, round(im.height * MAX_W / im.width)), Image.LANCZOS)
    path = IMG / f"{name}.webp"
    im.save(path, "WEBP", quality=80, method=6)
    creds = json.loads((IMG / "credits.json").read_text(encoding="utf-8"))
    who = c["creator"]
    line = {"commons": "via Wikimedia Commons", "pexels": "via Pexels", "pixabay": "via Pixabay"}[source]
    pd = c["licence"].lower().startswith(("public domain", "pd"))
    creds[f"{name}.webp"] = {
        "creator": "" if pd and not who else who,
        "credit": (f"{who}, " if who and not pd else "") + (c["licence"] + ", " if source == "commons" else "") + line,
        "license": c["licence_url"] or ("https://creativecommons.org/publicdomain/mark/1.0/" if pd else ""),
        "source": c["page"], "subject": subject or c["title"]}
    (IMG / "credits.json").write_text(json.dumps(creds, ensure_ascii=False, indent=1), encoding="utf-8")
    return path, im


def fetch(source, ident, name, subject=""):
    c = find(source, ident)
    path, im = save_candidate(source, c, name, subject)
    print(f"saved {path.relative_to(REPO)} ({path.stat().st_size // 1024} KB, {im.width}x{im.height}) {c['licence']} | {c['creator'] or 'no named creator'}")


if __name__ == "__main__":
    cmd, source = sys.argv[1], sys.argv[2]
    if cmd == "search":
        q, n = sys.argv[3], int(sys.argv[4]) if len(sys.argv) > 4 else 6
        for c in {"commons": commons_search, "pexels": pexels_search, "pixabay": pixabay_search}[source](q, n):
            print(f"{'OK ' if c['ok'] else 'NO '} {c['id']}  {c['w']}x{c['h']}  [{c['licence']}]  {c['creator'][:40]}\n     {c['title'][:110]}\n     {c['page']}")
    elif cmd == "fetch":
        fetch(source, sys.argv[3], sys.argv[4], sys.argv[5] if len(sys.argv) > 5 else "")
