"""Replace the site's own renderings of Qur'an verses quoted inside articles with Yusuf Ali's words.

The worklist is tools/verify/yusufali-inline.json: for each quotation, the file, the old wording and the new
wording. Every new wording must appear word for word in his verse text (display punctuation aside); the run
stops otherwise. It edits tools/build/content/*.txt and the published content/ JSON together.

  python3 -I tools/verify/yusufali_inline.py <repo> check    verify only
  python3 -I tools/verify/yusufali_inline.py <repo> apply
  python3 -I tools/verify/yusufali_inline.py <repo> fixes [file]   sentence-level fixes (default yusufali-fixes.json) after apply
"""
import hashlib, json, re, sys
from pathlib import Path

REPO = Path(sys.argv[1]).resolve(); RUN = sys.argv[2]
FIXES = sys.argv[3] if len(sys.argv) > 3 else "tools/verify/yusufali-fixes.json"
sys.argv = ["x", str(REPO), "none"]
exec((REPO / "tools/verify/yusufali.py").read_text(encoding="utf-8").split("rows = []")[0])
sys.path.insert(0, str(REPO / "tools/build"))
import article


def tidy(t):
    t = re.sub(r"\s+", " ", t).strip().replace(':"', ': "')
    t = re.sub(r"(?<=[,;:?!.)\"'])-\s*", "—", t)
    t = re.sub(r"(?<=\w)- ", "—", t)
    t = t.replace("disdaineth nor to serve", "disdaineth not to serve").replace("say unto men, worship me", "say unto men, 'worship me")
    return t.replace('"', "'")


work = json.loads((REPO / "tools/verify/yusufali-inline.json").read_text(encoding="utf-8"))
bad = []
for w in work:
    ks = []
    for part in re.split(r"[,;]", w["ref"]):
        part = part.strip().split(" ")[0]
        if re.match(r"\d+:\d+", part):
            ks += refs("Q " + part)
    if tidy(w["new"].split(">>")[-1].strip()) not in tidy(" ".join(YA.get(k, "") for k in ks)):
        bad.append((w["file"], w["line"], w["new"][:60]))
if bad:
    sys.exit("not found in his text: " + json.dumps(bad, ensure_ascii=False))
print(len(work), "replacements verified against his text")
if RUN == "apply":
    sums = {}
    for fname in sorted({w["file"] for w in work}):
        txt = (REPO / "tools/build/content" / fname).read_text(encoding="utf-8")
        head = dict(re.findall(r"^(type|slug): (.+)$", txt, flags=re.M))
        jf = REPO / "content" / ("pages" if head["type"] == "page" else "posts") / f"{head['slug']}.json"
        data = json.loads(jf.read_text(encoding="utf-8"))
        lines = txt.split("\n")
        for w in [x for x in work if x["file"] == fname]:
            new = w["new"]
            prefix = ""
            if ">>" in new:
                prefix, new = [x.strip() for x in new.split(">>", 1)]
                prefix += " "
            new = new.replace('"', "'")
            ln = lines[w["line"] - 1]
            m = re.search(r'(["“])' + re.escape(w["old"]) + r'(["”])', ln)
            if not m:
                sys.exit(f"{fname}:{w['line']} old wording not found: {w['old'][:50]}")
            lines[w["line"] - 1] = ln[: m.start()] + prefix + '"' + new + '"' + ln[m.end():]
            old_html, new_html = article.inline('"' + w["old"] + '"'), article.inline(prefix + '"' + new + '"')
            if old_html not in data["content"]:
                sys.exit(f"{jf.name}: published text not found for {w['old'][:50]!r}")
            data["content"] = data["content"].replace(old_html, new_html, 1)
        (REPO / "tools/build/content" / fname).write_text("\n".join(lines), encoding="utf-8")
        jf.write_text(json.dumps(data, ensure_ascii=False, indent=1), encoding="utf-8")
        sums[str(jf.relative_to(REPO / "content"))] = hashlib.md5(jf.read_bytes()).hexdigest()
    mf = REPO / "content/manifest.json"
    m = json.loads(mf.read_text(encoding="utf-8")); m["checksums"].update(sums)
    mf.write_text(json.dumps(m, indent=1), encoding="utf-8")
    print("applied in", len(sums), "files")

if RUN == "fixes":
    fixes = json.loads((REPO / FIXES).read_text(encoding="utf-8"))
    sums = {}
    for fname in sorted({f[0] for f in fixes}):
        txt = (REPO / "tools/build/content" / fname).read_text(encoding="utf-8")
        head = dict(re.findall(r"^(type|slug): (.+)$", txt, flags=re.M))
        jf = REPO / "content" / ("pages" if head["type"] == "page" else "posts") / f"{head['slug']}.json"
        data = json.loads(jf.read_text(encoding="utf-8"))
        for _, find, rep in [f for f in fixes if f[0] == fname]:
            if find not in txt:
                if rep in txt:
                    continue  # already applied
                sys.exit(f"{fname}: not found in the source: {find[:60]!r}")
            txt = txt.replace(find, rep, 1)
            fh, rh = article.inline(find), article.inline(rep)
            if fh in data["content"]:
                data["content"] = data["content"].replace(fh, rh, 1)
            else:
                print(f"note: {fname} fragment is in the source only (margin notes are not published): {find[:50]!r}")
        (REPO / "tools/build/content" / fname).write_text(txt, encoding="utf-8")
        jf.write_text(json.dumps(data, ensure_ascii=False, indent=1), encoding="utf-8")
        sums[str(jf.relative_to(REPO / "content"))] = hashlib.md5(jf.read_bytes()).hexdigest()
    mf = REPO / "content/manifest.json"
    m = json.loads(mf.read_text(encoding="utf-8")); m["checksums"].update(sums)
    mf.write_text(json.dumps(m, indent=1), encoding="utf-8")
    print(len(fixes), "sentence fixes applied in", len(sums), "files")
