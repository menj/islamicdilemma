#!/usr/bin/env python3
"""Lint the authoring files in build/content against docs/anti-ai-writing-style.md.

Usage: python3 tools/style/lint.py [files...] [--rule C,D,W,P,N,H,L,T,K] [-q]
Direct quotations, Arabic, verse text and footnote citations are exempt where the rules exempt them.
"""
import re, sys, glob
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
BANNED_WORDS = """delve realm harness unlock tapestry paradigm cutting-edge revolutionize landscape intricate intricacies showcasing crucial pivotal surpass meticulously vibrant vivid unparalleled underscore underscores underscored underscoring leverage synergy innovative game-changer testament commendable meticulous highlight highlights highlighted highlighting emphasize emphasizes emphasized emphasise emphasises emphasised boast groundbreaking align aligns aligned foster showcase enhance holistic garner accentuate pioneering trailblazing unleash versatile transformative redefine seamless optimize scalable robust breakthrough empower streamline frictionless elevate adaptive effortless data-driven insightful proactive mission-critical visionary disruptive reimagine unprecedented intuitive leading-edge synergize democratize accelerate state-of-the-art dynamic immersive predictive transparent proprietary integrated plug-and-play turnkey future-proof supercharge enduring interplay valuable captivate genuinely practical navigate navigating bolstered nestled bustling unveiling depicted demystify insurmountable poised unravel entanglement eerie beacon enrich multifaceted tailored elegant ever-evolving dazzle utilize utilise delightful hone discover transform architect grappling weighing journey adventure embark navigation straightforward furthermore additionally moreover""".split()
BANNED_PHRASES = ["in today", "it is important to note", "it is worth noting", "worth noting", "in order to", "at the end of the day", "moving forward", "in other words", "it goes without saying", "nobody", "challenges and future", "great question", "the power of", "a new era", "new era", "digital age", "deep dive", "imagine a world", "picture this", "that said", "that being said", "with that in mind", "on top of that", "in summary", "in conclusion", "overall", "all in all", "let that sink in", "this changes everything", "serves as", "stands as", "marks a", "represents a", "boasts a", "hope this helps", "to put it simply", "to clarify", "put differently", "as i mentioned", "as mentioned earlier", "rich tapestry", "diverse array", "focal point", "deeply rooted", "in the heart of"]
CONTR = re.compile(r"\b(?:[A-Za-z]+n['’]t|(?:it|that|there|here|what|who|let|he|she|how|where|when)['’]s|[Ii]['’](?:m|ve|ll|d)|(?:you|we|they|who)['’](?:re|ve|ll|d)|(?:gonna|wanna|kinda|ain['’]t|y['’]all))\b", re.I)
SKIP_PREFIX = ("::ayah", "::hadith", "::arabic", "::quran", "> ", "^ ", "::shortcode", "type:", "slug:", "tags:", "category:", "date:", "order:", "image:")

def units(path):
    """Yield (lineno, kind, text) for prose lines worth checking."""
    raw = Path(path).read_text(encoding="utf-8").splitlines()
    for i, line in enumerate(raw, 1):
        s = line.strip()
        if not s or s.startswith(SKIP_PREFIX) or s == "===sidebar===":
            continue
        if s.startswith(("title:", "excerpt:")):
            yield i, "front", s.split(":", 1)[1].strip(); continue
        if s.startswith("|"):
            yield i, "table", s; continue
        yield i, "prose", s

def mask(text):
    """Blank out quotations, refs and Arabic so they are not judged."""
    t = re.sub(r"“[^”]*”|\"[^\"]*\"", lambda m: " " * len(m.group(0)), text)
    t = re.sub(r"\{[^}]*\}", lambda m: " " * len(m.group(0)), t)
    t = re.sub(r"«[^»]*»", lambda m: " " * len(m.group(0)), t)
    t = re.sub(r"\]\([^)]*\)", "]", t)  # link targets
    return t

def sentences(text):
    t = re.sub(r"\{[^}]*\}", "REF", text)
    t = re.sub(r"\b(?:Q|Matt|Mark|Luke|John|Rom|Cor|Gal|Phil|Col|Heb|Rev|Gen|Exod|Lev|Deut|Ps|Isa|Jer|Ezek|Dan|Hos|Jas|Pet|Tim|Thess|vol|ch|chs|no|ed|eds|op|cit|c|d|al|ibid|vs|St|Dr|Mr)\.", lambda m: m.group(0)[:-1], t)
    t = re.sub(r"\b([A-Z])\.", r"\1", t)
    return [x for x in re.split(r"(?<=[.!?])\s+(?=[A-Z“\"*(\[«])", t) if x.strip()]

# Reviewed against the content test in section 4B: each item is distinct and load-bearing.
ALLOW_T = ("what they forgot, wrote by hand, and invented", "agree, differ, or disputed", "confirming, and muhaymin", "of the Psalms, of the Injīl, and of", "In Q 43:19")

def check(path, rules):
    out = []
    prev_neg = None
    for ln, kind, text in units(path):
        m = mask(text)
        plain = re.sub(r"\*+|\[|\]\([^)]*\)", "", m)
        if "C" in rules:
            for c in CONTR.finditer(plain):
                out.append((ln, "C", f"contraction: {c.group(0)}"))
        if "D" in rules and "—" in m:
            out.append((ln, "D", "em dash"))
        if "W" in rules:
            low = re.sub(r"\b(?:New|Old|Hebrew|Greek|Christian) Testament\b|\bTestaments?\b", "", plain).lower()
            for w in BANNED_WORDS:
                if re.search(rf"(?<![\w-]){re.escape(w)}(?![\w-])", low):
                    out.append((ln, "W", f"banned word: {w}"))
            for ph in BANNED_PHRASES:
                if ph in low:
                    out.append((ln, "W", f"banned phrase: {ph}"))
        if kind == "prose":
            body = re.sub(r"^(#+ |- |\d+\. |!lead |!callout )", "", text)
            if "L" in rules and re.match(r"^(- |\d+\. )\*\*[^*]+\*\*", text):
                out.append((ln, "L", "bold lead-in bullet"))
            if "K" in rules and re.match(r"!callout \*\*(?:In short|Conclusion)", text):
                out.append((ln, "K", "summary callout"))
            if "H" in rules and text.startswith("##"):
                h = re.sub(r"\{#[^}]*\}|\{[^}]*\}|\"[^\"]*\"|\d+:\d+", "", text.lstrip("# ")).strip()
                words = re.findall(r"[A-Za-z’']+", h)
                caps = [w for w in words[1:] if w[0].isupper() and w.lower() not in ("i",)]
                if len(words) >= 4 and len(caps) >= 3:
                    out.append((ln, "H", f"title case? {h}"))
            if not text.startswith(("#", "- ", "|", "::", "!callout")) and not re.match(r"^\d+\. ", text):
                ss = sentences(body)
                if "P" in rules and len(ss) > 3:
                    out.append((ln, "P", f"{len(ss)} sentences"))
            if "N" in rules:
                bm = mask(body)
                pats = [r"\bnot (?:only|just|merely|simply)\b", r"\bnot [^.;:]{1,60}?,? but\b", r"\b(?:is|are|was|were|does|do|did|has|have|can|could|will|would|should|must)(?: not|n['’]t) [^.]{1,80}?[;,] (?:it|they|he|she|the|this|that|but|rather)\b", r", not (?!a )\w", r"\bno [^.,;]{1,40}, no [^.]{0,40}, (?:just|only)\b", r"\bnever [^.]{1,60}; (?:it|they|he|she)\b"]
                for p in pats:
                    for x in re.finditer(p, bm, re.I):
                        out.append((ln, "N", "reframe: " + bm[max(0, x.start() - 15): x.end() + 25].strip()))
                        break
    if "T" in rules:
        text = " ".join(" ".join(re.sub(r"\*+", "", mask(t)) for _, k, t in units(path) if k == "prose" and not t.startswith(("#", "- ", "|"))).split())
        sents = sentences(text)
        key = lambda s: tuple(w.lower() for w in re.findall(r"[A-Za-z’]+", s)[:2])
        i = 0
        while i < len(sents):
            run = 1
            while i + run < len(sents) and len(key(sents[i])) == 2 and key(sents[i]) == key(sents[i + run]):
                run += 1
            if run >= 3 and not any(a in sents[i] for a in ALLOW_T):
                out.append((0, "T", "anaphora: " + " / ".join(sents[i:i + run])[:110])); i += run
            else:
                i += 1
        for mt in re.finditer(r"([a-z][\w’\- ]{2,38}), ([\w’\- ]{2,38}), (?:and|or) ([\w’\- ]{2,42})", text):
            if all(len(g.split()) <= 3 for g in mt.groups()) and not any(a in mt.group(0) for a in ALLOW_T):
                out.append((0, "T", "triad: " + mt.group(0)[:80]))
    return out

def main():
    args = [a for a in sys.argv[1:] if not a.startswith("-")]
    rules = set("CDWPNHLTK")
    if "--rule" in sys.argv:
        rules = set(sys.argv[sys.argv.index("--rule") + 1].replace(",", ""))
        args = [a for a in args if a != sys.argv[sys.argv.index("--rule") + 1]]
    files = args or sorted(glob.glob(str(ROOT / "build/content/*.txt")))
    total, per = 0, {}
    for f in files:
        hits = check(f, rules)
        for ln, r, msg in hits:
            per[r] = per.get(r, 0) + 1
            if "-q" not in sys.argv:
                print(f"{Path(f).name}:{ln}: [{r}] {msg}")
        total += len(hits)
    print(f"\n{total} findings", {k: per[k] for k in sorted(per)}, file=sys.stderr)
    sys.exit(1 if total else 0)

if __name__ == "__main__":
    main()
