# Working on this repository

Read `docs/FARUQI.md` first. It sets out the Faruqian core, al-tawḥīd as Ismāʿīl Rājī al-Fārūqī expounded it in *Al Tawhid: Its Implications for Thought and Life*, which runs through the themes and content of this site and its eight sister sites. Do not deviate from it.

Also read `docs/SEO.md` and apply it to every page, template, article and design change. The owner asked for these rules, from Google's SEO Starter Guide and its list of supported structured data, to apply at all times.

## Owner decisions

- The site's author is Mohd Elfie Nieshaem Juferi (author.json). The paper *Reconsidering the "Islamic Dilemma"* (2026) is the site's backbone.
- People, publications and organisations named in that paper may be named on the site as the paper names them. Do not name others who present the argument.
- Keep the existing site structure: navigation, pages, their order and their existing sections stay. The paper is worked in through new enrichment articles in the existing categories and through added sections and citations within existing pages, never by restructuring them.

## Writing style

Read `docs/anti-ai-writing-style.md` and apply it to all English text on the site: pages, articles, theme strings, README and changelog. The owner asked for strict enforcement. In short: no contractions (possessives and direct quotations are exempt), no em dashes, none of the banned words, phrases or transitions, no negative parallelisms, short paragraphs (one or two sentences, three at most), sentence-case headings, no bold lead-in bullets, no padding triads. Run `python3 tools/style/lint.py` before committing; it must report no violations.

- Photographs come only from Wikimedia Commons, Pexels and Pixabay, plus the owner's own supplied images. API keys for these services are never written into code or committed; read them from the environment.
