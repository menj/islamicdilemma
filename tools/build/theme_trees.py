"""Block trees for the theme's parts, templates and patterns.

Writes one JSON file per output into the directory given as argv[1]:
  part--header.json, template--single.json, pattern--hero-argument.json ...
"""
import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from blocks import (AYAH, B, BUTTON, BUTTONS, COL, COLS, FLEX, G, H, LIST, P, QUOTE, REF, SC, WRAP)

OUT = Path(sys.argv[1])
OUT.mkdir(parents=True, exist_ok=True)
THEME = "__THEME_URI__"  # replaced with get_stylesheet_directory_uri() in PHP patterns


def emit(name, *blocks):
    (OUT / f"{name}.json").write_text(json.dumps(list(blocks), ensure_ascii=False, indent=1), encoding="utf-8")


NAV = [("Home", "/"), ("Islamic Dilemma", "/islamic-dilemma/"), ("Muslim Response", "/muslim-response/"),
       ("Enrichment", "/enrichment/"), ("Glossary", "/glossary/"), ("Resources", "/resources/"), ("About", "/about/")]

# ------------------------------------------------------------------ parts
emit("part--header",
     WRAP("sitehead",
          G("head-row",
            G("brand", B("site-title", {"level": 0}), B("site-tagline", {}))),
          FLEX("nav-row",
               B("navigation", {"overlayMenu": "never", "className": "main-nav", "ariaLabel": "Main",
                                "layout": {"type": "flex", "flexWrap": "wrap", "justifyContent": "left"}},
                 *[B("navigation-link", {"label": label, "url": url, "kind": "custom", "isTopLevelLink": True})
                   for label, url in NAV]),
               B("islamicdilemma/search-toggle", {}),
               justify="space-between", wrap=False, vertical="center")))

def FOOT_COL(title, links):
    return G("foot-col", H(2, title, "foot-title"), LIST(links, c="foot-links"))


emit("part--footer",
     WRAP("sitefoot",
          G("foot-top",
            G("foot-about",
              G("foot-id", B("islamicdilemma/brandmark", {"size": 52}), B("site-title", {"level": 0, "className": "foot-brand"}),
                layout={"type": "flex", "flexWrap": "nowrap", "verticalAlignment": "center"}),
              B("site-tagline", {"className": "foot-tagline"})),
            G("foot-nav",
              FOOT_COL("Read", ['<a href="/islamic-dilemma/">The argument</a>', '<a href="/muslim-response/">The Muslim response</a>',
                               '<a href="/errors-in-case-for-dilemma/">Errors in the argument</a>', '<a href="/enrichment/">Enrichment</a>']),
              FOOT_COL("Learn", ['<a href="/glossary/">Glossary</a>', '<a href="/reading-list/">Reading list</a>', '<a href="/faq/">FAQ</a>',
                                '<a href="/resources/">Resources</a>', '<a href="/verse-index/">Verse index</a>']),
              FOOT_COL("Site", ['<a href="/about/">About and corrections</a>', '<a href="/contact/">Contact</a>',
                               '<a href="/?s=">Search</a>', '<a href="/sitemap/">Sitemap</a>']))),
          G("foot-verse",
            P("قُلْ هَاتُوا۟ بُرْهَـٰنَكُمْ إِن كُنتُمْ صَـٰدِقِينَ", "is-style-arabic foot-arabic"),
            P("“Say: bring your proof, if you are truthful.” Q 2:111", "foot-translation")),
          G("foot-base",
            P("Qur’an verse cards use A. Yusuf Ali’s translation (1934–37), with only punctuation adjusted; phrases quoted within the text are the site’s own renderings. Bible quotations are from the ESV. "
              "Photographs via Pexels and Wikimedia Commons; credits are listed on the Resources page.", "legal"),
            P('Written by <a href="/about/#author">Mohd Elfie Nieshaem Juferi</a>', "legal foot-byline"),
            layout={"type": "flex", "flexWrap": "wrap", "justifyContent": "space-between"})))

HEADER = B("template-part", {"slug": "header", "tagName": "header", "area": "header"})
FOOTER = B("template-part", {"slug": "footer", "tagName": "footer", "area": "footer"})


def main(*inner):
    return G("site-main", *inner, tag="main")


def pagehero(*inner):
    return WRAP("pagehero", *inner)


SIDEBAR_BROWSE = G("sidebar",
                   G("is-style-side-box", H(2, "Browse Enrichment", "side-title"),
                     B("categories", {"showPostCounts": True, "className": "side-links"})),
                   G("is-style-side-box", H(2, "Topics", "side-title"),
                     B("tag-cloud", {"smallestFontSize": "0.78rem", "largestFontSize": "0.78rem", "className": "tags"})))


def archive_list(no_results):
    return B("query", {"queryId": 1, "query": {"inherit": True, "perPage": 10, "postType": "post"}, "className": "archive-query"},
             B("post-template", {"className": "archive-list"},
               G("item",
                 FLEX("meta", B("post-terms", {"term": "category"}), B("post-date", {})),
                 B("post-title", {"isLink": True, "level": 2}),
                 B("post-excerpt", {"moreText": "Read article →", "showMoreOnNewLine": True}))),
             B("query-pagination", {"className": "pager", "layout": {"type": "flex", "justifyContent": "center"}},
               B("query-pagination-previous", {"label": "Newer"}), B("query-pagination-numbers", {}),
               B("query-pagination-next", {"label": "Older"})),
             B("query-no-results", {}, P(no_results)))


def archive_page(eyebrow, title_blocks, intro_blocks, no_results):
    return [HEADER,
            main(WRAP("archive-head", P(eyebrow, "is-style-eyebrow"), *title_blocks, *intro_blocks),
                 WRAP("archive-body",
                      COLS("archive-grid", COL("66%", archive_list(no_results)), COL("34%", SIDEBAR_BROWSE), align="wide"))),
            FOOTER]


# ------------------------------------------------------------------ templates
emit("template--front-page",
     HEADER,
     main(*[B("pattern", {"slug": f"islamicdilemma/{s}"}) for s in
            ("hero-argument", "begin-here", "two-alternatives", "bible-mirror", "recent-enrichment")]),
     FOOTER)

emit("template--page",
     HEADER,
     main(pagehero(SC("[islamicdilemma_breadcrumbs]"), B("post-title", {"level": 1}),
                   B("post-excerpt", {"className": "deck"}),
                   FLEX("meta", B("islamicdilemma/author-byline", {}), B("post-date", {}),
                        B("post-date", {"displayType": "modified", "className": "updated-date"}))),
          WRAP("page-body", B("post-content", {"className": "prose", "layout": {"type": "constrained", "contentSize": "1160px", "justifyContent": "left"}}),
               B("islamicdilemma/author-box", {}))),
     FOOTER)

emit("template--single",
     HEADER,
     main(pagehero(SC("[islamicdilemma_breadcrumbs]"),
                   B("post-terms", {"term": "category", "className": "is-style-eyebrow eyebrow-terms"}),
                   B("post-title", {"level": 1}), B("post-excerpt", {"className": "deck"}),
                   FLEX("meta", B("islamicdilemma/author-byline", {}), B("post-date", {}),
                        B("post-date", {"displayType": "modified", "className": "updated-date"}),
                        B("islamicdilemma/reading-time", {}))),
          WRAP("article-body",
               B("post-featured-image", {"align": "wide", "className": "plate wide", "aspectRatio": "16/6", "scale": "cover"}),
               COLS("article-grid",
                    COL("64%", B("post-content", {"className": "prose", "layout": {"type": "default"}}),
                        B("post-terms", {"term": "post_tag", "className": "tags", "separator": " "}),
                        B("islamicdilemma/author-box", {})),
                    COL("36%", G("sidebar", SC("[islamicdilemma_toc]"), SC("[islamicdilemma_related]"))),
                    align="wide"))),
     FOOTER)

ENRICHMENT_INTRO = [P("Commentary, close readings and replies on Qur’anic interpretation, biblical texts and the claims made "
                      "in Muslim–Christian apologetics.", "archive-intro")]
for name, eyebrow, title, intro in [
        ("home", "Enrichment · Blog", [H(1, "Enrichment")], ENRICHMENT_INTRO),
        ("index", "Enrichment · Blog", [H(1, "Enrichment")], ENRICHMENT_INTRO),
        ("category", "Enrichment · Category", [B("query-title", {"type": "archive", "showPrefix": False, "level": 1})],
         [B("term-description", {"className": "archive-intro"})]),
        ("tag", "Enrichment · Topic", [B("query-title", {"type": "archive", "showPrefix": False, "level": 1})],
         [B("term-description", {"className": "archive-intro"})]),
        ("archive", "Enrichment · Archive", [B("query-title", {"type": "archive", "showPrefix": False, "level": 1})], [])]:
    emit(f"template--{name}", *archive_page(eyebrow, title, intro, "Nothing has been published here yet."))

emit("template--search",
     HEADER,
     main(pagehero(SC("[islamicdilemma_breadcrumbs]"), P("Search", "is-style-eyebrow"),
                   B("query-title", {"type": "search", "level": 1, "showSearchTerm": True}),
                   B("search", {"label": "Search the site", "showLabel": False, "placeholder": "Search articles, arguments, and resources…",
                                "buttonText": "Search", "buttonPosition": "button-inside", "className": "search-page-form"})),
          WRAP("search-body",
               B("query", {"queryId": 2, "query": {"inherit": True, "perPage": 10}, "className": "search-query"},
                 B("post-template", {"className": "result-list"},
                   G("item result",
                     FLEX("meta", B("islamicdilemma/result-type", {}), B("post-date", {})),
                     B("post-title", {"isLink": True, "level": 2}),
                     B("post-excerpt", {"moreText": "Read →", "showMoreOnNewLine": True}))),
                 B("query-pagination", {"className": "pager", "layout": {"type": "flex", "justifyContent": "center"}},
                   B("query-pagination-previous", {"label": "Previous"}), B("query-pagination-numbers", {}),
                   B("query-pagination-next", {"label": "Next"})),
                 B("query-no-results", {},
                   G("no-results is-style-card",
                     P("No results", "is-style-eyebrow"),
                     H(2, "Nothing matched that search"),
                     P("Try a shorter or different phrase, a verse reference such as “5:47”, or one of these topics:"),
                     B("buttons", {"className": "topic-pills"},
                       *[BUTTON(t, f"/?s={q}", "is-style-outline") for t, q in
                         [("Injil", "Injil"), ("Q 5:47", "5%3A47"), ("Preservation", "preservation"), ("Taḥrīf", "tahrif"), ("Q 10:94", "10%3A94")]]),
                     P('Or start from the <a href="/muslim-response/">full Muslim response</a> or the '
                       '<a href="/enrichment/">Enrichment archive</a>.')))))),
     FOOTER)

emit("template--404",
     HEADER,
     main(pagehero(P("Page not found", "is-style-eyebrow"), H(1, "This page is not here"),
                   P("The link may be out of date, or the page may have moved. Search the site or start from one of the main sections.", "deck"),
                   B("search", {"label": "Search the site", "showLabel": False, "placeholder": "Search articles, arguments, and resources…",
                                "buttonText": "Search", "buttonPosition": "button-inside", "className": "search-page-form"})),
          WRAP("page-body",
               B("buttons", {"className": "topic-pills"},
                 BUTTON("The argument", "/islamic-dilemma/", "is-style-outline"),
                 BUTTON("The Muslim response", "/muslim-response/", "is-style-outline"),
                 BUTTON("Enrichment", "/enrichment/", "is-style-outline")))),
     FOOTER)

# ------------------------------------------------------------------ patterns
VERDICTS = [
    ("granted", "P1", "The Qur’an says the Torah and the Gospel were revealed by God.",
     f"Agreed. God revealed the Tawrat to Moses and the Injil to Jesus {REF('Q 3:3')}. The question is what those words refer to."),
    ("equivocates", "P2", "“The Gospel” in the Qur’an means the Bible Christians held in the 7th century.",
     f"The Qur’an speaks of one Injil <em>given to</em> Jesus. The New Testament is twenty-seven books written <em>about</em> him by others. "
     f'The argument rests on treating these as the same thing. <a href="/muslim-response/#injil">Read §2</a>'),
    ("does-not-follow", "P3", "The Qur’an tells Christians to judge by their scripture, so it must have been intact.",
     f"“Judge by what God revealed <em>therein</em>” {REF('Q 5:47')} points to revelation still present in what they held. It does not "
     f"certify every word. Jesus told his hearers to obey the scribes while condemning their additions {REF('Matt 23:2–3')}. "
     f'<a href="/muslim-response/#judge">Read §3</a>'),
    ("false", "P4", "If the Bible was corrupted, God failed to protect His word.",
     f"Earlier books were <em>entrusted to</em> their scholars {REF('Q 5:44')}. Only of the Qur’an does God say “We will guard it” "
     f"{REF('Q 15:9')}. A trust given to people can be broken by people. <a href=\"/muslim-response/#trust\">Read §4</a>"),
    ("unsupported", "C", "Therefore Islam is false.",
     f"Without P2 and P4 the dilemma has nothing left. The Qur’an’s own position is a third option: it confirms what remains true in "
     f"earlier scripture and judges it {REF('Q 5:48')}. <a href=\"/muslim-response/#muhaymin\">Read §5</a>"),
]

emit("pattern--hero-argument",
     WRAP("hero",
          COLS("hero-grid",
               COL("54%",
                   P("A critical Muslim examination", "is-style-eyebrow"),
                   H(1, "The “Islamic Dilemma” is a <em><span class=\"rubric\">false</span> dilemma.</em>", "hero-title"),
                   P("A popular missionary argument says the Qur’an traps itself: either the Bible is preserved and refutes Islam, or it "
                     "is corrupted and the Qur’an was wrong to affirm it. The Qur’an holds neither position. This site reads the verses "
                     "the argument depends on, in Arabic and in context, alongside the classical scholars who answered it long ago.", "lede"),
                   BUTTONS(BUTTON("Examine the argument →", "/islamic-dilemma/"),
                           BUTTON("Read the response", "/muslim-response/", "is-style-ghost"))),
               COL("46%",
                   G("argument",
                     H(2, "The argument, premise by premise", "argument-title"),
                     P("As its advocates commonly present it. Open a line to read the reply.", "src"),
                     *[B("details", {"summary": f"<strong>{num}</strong>{claim}", "className": f"is-style-verdict-{v}"},
                         P(reply, "reply")) for v, num, claim, reply in VERDICTS],
                     P("Verdicts are this site’s assessment of each premise.", "hint"))),
               align="wide")))

emit("pattern--begin-here",
     WRAP("section",
          G("section-head", P("Begin here", "is-style-eyebrow"), H(2, "Three steps, in order"),
            P("Read the argument as its advocates make it, then the reply, then check the sources yourself.")),
          COLS("cards3",
               *[COL(None, G("is-style-card lift", P(step, "step"), H(3, f'<a href="{url}">{title}</a>'), P(text),
                             P(f'<a class="textlink" href="{url}">{link}</a>')))
                 for step, title, text, url, link in [
                     ("Step 1 · The claim", "What is the Islamic Dilemma?",
                      "The argument stated fairly, with the verses its advocates cite: Q 3:3, 5:47, 5:68, 10:94, 18:27 and 29:46.",
                      "/islamic-dilemma/", "Read the claim →"),
                     ("Step 2 · The response", "Where the premises fail",
                      "A premise-by-premise reply grounded in the Arabic text and the classical commentaries.",
                      "/muslim-response/", "Read the response →"),
                     ("Step 3 · The evidence", "Sources and scholarship",
                      "Qur’an texts, tafsir, manuscript projects and Christian scholarship, linked so you can verify every claim.",
                      "/resources/", "Browse resources →")]],
               align="wide")))

emit("pattern--two-alternatives",
     WRAP("band",
          G("section-head", P("The shape of the argument", "is-style-eyebrow"), H(2, "Two alternatives, and the one left out"),
            P("A dilemma only works when its two alternatives are the only ones available. Here they are not.")),
          COLS("choices",
               COL("47%", G("choice", P("Alternative one", "is-style-eyebrow"), H(3, "The Bible is fully preserved"),
                            P("Then it contradicts the Qur’an on the crucifixion, the Trinity and the sonship of Jesus, and Islam is false."))),
               COL("6%", P("or", "or")),
               COL("47%", G("choice", P("Alternative two", "is-style-eyebrow"), H(3, "The Bible is fully corrupted"),
                            P("Then the Qur’an was wrong to call it guidance and light and to tell Christians to judge by it, and Islam is false."))),
               align="wide"),
          G("third alignwide",
            COLS("third-grid",
                 COL("50%", P("What the Qur’an actually says", "is-style-eyebrow"), H(3, "Confirmed in part, and judged by the Qur’an"),
                     P(f"The Qur’an says earlier scripture contains real revelation, and also that some “distort words from their places” and "
                       f"“forgot a portion” {REF('Q 5:13')}. So it confirms what agrees with it and corrects what does not. The dilemma "
                       f"never addresses this position.")),
                 COL("50%", AYAH("وَأَنزَلْنَآ إِلَيْكَ ٱلْكِتَـٰبَ بِٱلْحَقِّ مُصَدِّقًۭا لِّمَا بَيْنَ يَدَيْهِ مِنَ ٱلْكِتَـٰبِ وَمُهَيْمِنًا عَلَيْهِ ۖ",
                                 "We have sent down to you the Book in truth, confirming the scripture that came before it and as a guardian over it.",
                                 "Q 5:48 · al-Mā’idah"))))))

emit("pattern--bible-mirror",
     WRAP("section",
          G("section-head", P("The same test, applied consistently", "is-style-eyebrow"),
            H(2, "The Bible holds the position it calls incoherent"),
            P("Scripture can be affirmed as God’s word while its keepers are accused of altering it. The Bible does exactly this.")),
          B("image", {"url": f"{THEME}/assets/img/torah-yad.webp", "alt": "A silver yad pointing to Hebrew text in a Torah scroll",
                      "caption": 'A yad, the reader’s pointer, on a Torah scroll. Photo: <a href="https://www.pexels.com/photo/silver-and-gold-pen-on-white-paper-5986498/">cottonbro studio / Pexels</a>',
                      "className": "plate wide", "align": "wide", "aspectRatio": "16/6", "scale": "cover", "sizeSlug": "full"}),
          COLS("mirror",
               COL(None, G("mirror-card", P("The Hebrew Bible", "mirror-label"),
                           QUOTE("“How can you say, ‘We are wise, and the law of the LORD is with us’? But behold, the lying pen of the scribes has made it into a lie.”",
                                 "Jeremiah 8:8 · ESV"),
                           P("Jeremiah affirms the Law and accuses the scribes of falsifying it in the same breath.", "mirror-comment"))),
               COL(None, G("mirror-card", P("The Gospels", "mirror-label"),
                           QUOTE("“The scribes and the Pharisees sit on Moses’ seat, so do and observe whatever they tell you, but not the works they do.”",
                                 "Matthew 23:2–3 · ESV"),
                           P("Jesus sends his hearers to the teachers of the Law while condemning the traditions they added.", "mirror-comment"))),
               align="wide"),
          P("If affirming a scripture while charging its custodians with corruption is a contradiction, the Bible commits it first. "
            "If it is not, the Islamic Dilemma has no force.", "mirror-verdict")))

emit("pattern--recent-enrichment",
     WRAP("section recent",
          G("section-head", P("From Enrichment", "is-style-eyebrow"), H(2, "Recent commentary and analysis")),
          B("query", {"queryId": 3, "query": {"perPage": 3, "postType": "post", "order": "desc", "orderBy": "date", "inherit": False,
                                              "sticky": "exclude"}, "align": "wide"},
            B("post-template", {"layout": {"type": "grid", "columnCount": 3}, "className": "cards3 post-cards"},
              G("post-card lift",
                FLEX("meta", B("post-terms", {"term": "category"}), B("islamicdilemma/reading-time", {})),
                B("post-title", {"isLink": True, "level": 3}),
                B("post-excerpt", {"moreText": "Read commentary →", "showMoreOnNewLine": True})))),
          P('<a class="textlink" href="/enrichment/">All Enrichment articles →</a>', "more-link")))

emit("pattern--ayah",
     AYAH("وَأَنزَلْنَآ إِلَيْكَ ٱلْكِتَـٰبَ بِٱلْحَقِّ مُصَدِّقًۭا لِّمَا بَيْنَ يَدَيْهِ مِنَ ٱلْكِتَـٰبِ وَمُهَيْمِنًا عَلَيْهِ ۖ",
          "We have sent down to you the Book in truth, confirming the scripture that came before it and as a guardian over it.",
          "Q 5:48"))

emit("pattern--article-with-sidebar",
     COLS("article-grid",
          COL("64%", P("Write the article here. Use level-two headings for its sections; they appear in the “On this page” list automatically.")),
          COL("36%", G("sidebar", SC("[islamicdilemma_toc]"),
                       G("is-style-side-box", H(2, "Margin notes", "side-title"),
                         P("<strong>Term</strong> A short note that sits beside the article on wide screens.", "gloss")))),
          align="wide"))

emit("pattern--premise-card",
     B("details", {"summary": "<strong>P1</strong>State the premise in one sentence.", "className": "is-style-verdict-granted"},
       P("Reply to the premise in two or three sentences, with its verse reference.", "reply")))

print("trees:", len(list(OUT.glob("*.json"))))
