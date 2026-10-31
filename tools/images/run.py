"""Pick and fetch the planned photographs for every article (resumable).

  PEXELS_KEY=... PIXABAY_KEY=... python3 -I tools/images/run.py [slug ...]

Reads plan.py, writes assets/img/*.webp and credits.json, and records each choice in tools/images/picks.json
so a second run only fills what is missing.
"""
import json, re, sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import source as S
from plan import PLAN

PICKS = Path(__file__).parent / "picks.json"
picks = json.loads(PICKS.read_text(encoding="utf-8")) if PICKS.exists() else {}
used = {v["page"] for slot in picks.values() for v in slot}
used |= {c["source"] for c in json.loads((S.IMG / "credits.json").read_text(encoding="utf-8")).values()}
SEARCH = {"commons": S.commons_search, "pexels": S.pexels_search, "pixabay": S.pixabay_search}


def slug(text):
    t = re.sub(r"[^a-z0-9]+", "-", re.sub(r"^(a|an|the) ", "", text.lower().replace("’", "").replace("'", ""))).strip("-")
    return "-".join(t.split("-")[:7])


def choose(source, query):
    cands = [c for c in SEARCH[source](query, 14) if c["ok"] and c["w"] >= 1000 and c["w"] <= c["h"] * 3 and c["page"] not in used]
    land = [c for c in cands if c["w"] >= c["h"] * 1.1]
    return (land or [c for c in cands if c["w"] >= c["h"] * 0.65] or [None])[0]


for art in sys.argv[1:] or PLAN:
    done = picks.setdefault(art, [])
    for n, (source, query, subject) in enumerate(PLAN[art]):
        if n < len(done):
            continue
        c = choose(source, query)
        if not c and source != "commons":
            c = choose("commons", query)
            source = "commons" if c else source
        if not c:
            print(f"!! {art} #{n + 1}: nothing suitable for {query!r}")
            done.append({"source": source, "query": query, "subject": subject, "page": "", "name": ""})
            continue
        name = slug(subject)
        k, base = 2, name
        while (S.IMG / f"{name}.webp").exists():
            name = f"{base}-{k}"; k += 1
        S.save_candidate(source, c, name, subject)
        used.add(c["page"])
        done.append({"source": source, "query": query, "subject": subject, "page": c["page"], "name": name,
                     "title": c["title"], "licence": c["licence"], "creator": c["creator"][:60]})
        PICKS.write_text(json.dumps(picks, ensure_ascii=False, indent=1), encoding="utf-8")
        print(f"{art} #{n + 1}: {name}.webp  [{c['licence']}]  {c['title'][:70]}", flush=True)
