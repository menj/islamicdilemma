# Islamic Dilemma: single source of truth

Version 1.4.8. This file holds the fixed facts of the theme and the rules every change follows. On a matter of fact it wins over every other file in the repository. `.claude/FARUQI.md` holds the principle these facts serve; if the two ever pull apart, stop and ask the owner.

## Identity

| Item | Value |
|---|---|
| Site name | Islamic Dilemma Is No Dilemma (`content/settings.json`, `blogname`) |
| Tagline | A Muslim reply to the "Islamic Dilemma" argument (`blogdescription`) |
| Domain | https://islamicdilemma.info |
| Theme name | Islamic Dilemma (`style.css`) |
| Theme folder | `islamicdilemma`, all lower case, on the live server and in the zip |
| Text domain | `islamicdilemma` |
| Parent theme | Twenty Twenty-Five (`Template: twentytwentyfive`) |
| Requirements | WordPress 6.7 or later (tested on 7.1), PHP 7.4 or later |
| Author for schema | Mohd Elfie Nieshaem Juferi, https://menj.bio (`author.json`) |
| Backbone | The paper *Reconsidering the "Islamic Dilemma"* (2026), `ISLAMICDILEMMA_PAPER_URL` in `functions.php` |
| Licence | GNU GPL version 3 or later (owner decision, 5 November 2026); the full text is in `LICENSE` at the theme root |

## Purpose and position

The site answers the "Islamic Dilemma" argument premise by premise. It holds that the dilemma is a false one, because it leaves out the Qur'an's own position on the earlier scriptures (Q 5:48).

It honours the revelations and examines the historical record around them, as `.claude/FARUQI.md` sets out. It names its sources and labels each claim for what it is.

## Owner decisions

Copied word for word from `.claude/CLAUDE.md`. The repository history starts on 2026-11-02 with all three already in place, so each is dated by that first record.

- Recorded by 2026-11-02. The site's author is Mohd Elfie Nieshaem Juferi (author.json). The paper *Reconsidering the "Islamic Dilemma"* (2026) is the site's backbone.
- Recorded by 2026-11-02. People, publications and organisations named in that paper may be named on the site as the paper names them. Do not name others who present the argument.
- Recorded by 2026-11-02. Keep the existing site structure: navigation, pages, their order and their existing sections stay. The paper is worked in through new enrichment articles in the existing categories and through added sections and citations within existing pages, never by restructuring them.

The same file sets these rules, also word for word:

- Photographs come only from Wikimedia Commons, Pexels and Pixabay, plus the owner's own supplied images. API keys for these services are never written into code or committed; read them from the environment.
- Qur'an English translations are A. Yusuf Ali's (1934–37 original wording). Do not substitute another translation or the site's own wording. Quote his exact words; run `python3 -I tools/verify/yusufali.py . report` to compare verse cards with his text. His translation is verbatim: change punctuation only, and say so in `docs/CREDITS.md`.
- Grammar quoted from the Quranic Arabic Corpus is pulled with `python3 -I tools/verify/corpus_morphology.py . fetch <sura:verse> ...` into `tools/verify/corpus-morphology.json`, and `show <sura:verse>` prints a verse's tags. The Corpus's "Java API" is a text-only library; the tags come from its word-by-word pages.
- Images are AVIF or WebP only. Convert anything else with `tools/images/convert.py` before it is committed.
- New or changed posts can be built without a running WordPress: `python3 tools/build/build_content.py refresh . <post-slug> ...` writes the post JSON, `terms.json`, the manifest and its checksums. `python3 tools/build/check_serializer.py` proves the serializer against every stored post. Pages (columns, patterns) still go through `serialize.js`.

## Contributor rules

### Writing

All English text follows `.claude/skills/anti-ai-writing-style/SKILL.md`. That covers pages, articles, theme strings, the readme, the README and the changelog. `python3 tools/style/lint.py` must report no findings before a commit.

In short: no contractions (possessives and direct quotations are exempt), no em dashes, none of the banned words, phrases or transitions, and no negative parallelisms. Paragraphs run one or two sentences, three at most. Headings are in sentence case, bullets carry no bold lead-in, and lists hold no padding triads.

The two parts of the dilemma are its two "claims".

### Search

`.claude/SEO.md` applies to every page, template, article and design change. Its fixed points:

- Every title reads `<page title, keyword or description> | Islamic Dilemma Is No Dilemma`, with the page number inside the first part ("Glossary (Page 2)"). The front page uses the tagline as its first part. `islamicdilemma_title_parts()` in `inc/seo/seo.php` builds it, and no other title filter may be added.
- Every title tag stays under 60 characters, site name included, which leaves 27 for the first part. A part that would run over takes its short form from `inc/data/seo-titles.json` (keys `page:<slug>`, `post:<slug>`, `category:<slug>`, `tag:<slug>` and `front_page`); anything not listed is cut at a word (`islamicdilemma_title_cut()`). The front page uses the short form of the tagline, "Muslim reply to the dilemma". Visible headings keep their full titles. `tests/seo-titles-test.php` fails when a shipped item needs a short part and has none.
- Every page and article has its own written excerpt, which is its meta description. The front page is the one exception: its excerpt is empty, and `islamicdilemma_meta_description()` builds its description from the tagline and a fixed sentence. A written excerpt on the Home page takes its place.
- Slugs drop only the words "a", "an" and "the". Each document has one URL.
- Structured data uses only the types on Google's supported list, and only for what the page shows. `.claude/SEO.md` rules out Organization, FAQPage and the site search box.
- No Organization node is printed (since 1.4.8). The author, as a Person, is the WebSite's publisher, author and copyright holder, and the Article's author and publisher. `tests/wordpress.php` checks both.

### Images

Images live in `assets/img/` with short descriptive file names, AVIF or WebP, at most 1600 pixels wide. Each one has its credit and licence in `assets/img/credits.json` and `docs/CREDITS.md`. The icons in `assets/brand/` stay PNG, ICO and SVG, since browsers and the web app manifest need them.

### Public wording

The site never says that it is related to any other site. Links to other sites are made as ordinary links.

### Release checklist

1. The version is the same in `style.css`, `docs/readme.txt` (Version, Stable tag and changelog) and the top entry of `docs/CHANGELOG.md`. `ISLAMICDILEMMA_VERSION` reads it from `style.css`.
2. `docs/CHANGELOG.md` has the new entry, and `docs/readme.txt` has the short form.
3. `docs/UPGRADING.md` has a note if the site owner must do anything, and its roadmap drops what shipped.
4. `docs/README.md` covers any new hook, filter, constant, setting, file or rule.
5. This file covers any new fixed fact or owner decision.
6. Every path named in a document exists.
7. The checks in README.md pass, and `python3 tools/style/lint.py` reports no findings.

## Design tokens

Light only. The parent's style variations are hidden from the Site Editor, and no dark scheme is added.

| Slug | Colour | Name |
|---|---|---|
| `navy` | #18283C | Midnight navy |
| `navy-2` | #223650 | Navy, lighter |
| `gold` | #B18B54 | Antique gold |
| `gold-text` | #7D5C2A | Gold for text |
| `paper` | #F7F4ED | Parchment |
| `surface` | #FFFDF8 | Card |
| `surface-2` | #EEE9DE | Parchment, deeper |
| `ink` | #202936 | Ink |
| `muted` | #5E6670 | Muted ink |
| `line` | #DCD8CE | Rule |
| `madder` | #813E46 | Madder |
| `on-navy` | #F3EFE6 | Text on navy |
| `on-navy-muted` | #A9B4C2 | Muted text on navy |
| `on-navy-gold` | #C9A66E | Gold on navy |
| `on-navy-red` | #E59AA2 | Red on navy |
| `rubric` | #D2452F | Rubric red |
| `rubric-on-navy` | #EB6650 | Rubric red on navy (small text) |

Content width 700px, wide width 1160px. No green and no Tailwind.

| Font slug | Face | Used for |
|---|---|---|
| `marcellus` | Marcellus | Headings |
| `sabon` | Sabon Next LT, with Literata for missing transliteration letters | Reading text |
| `ibm-plex-sans` | IBM Plex Sans | Interface |
| `quran` | KFGQPC HAFS Uthmanic Script | Qur'anic Arabic only |
| `hadith` | Noto Naskh Arabic | Hadith |
| `arabic`, `arabic-alt` | Arslan Wessam A and B | All other Arabic |
| `bible` | SBL BibLit, SBL Greek, SBL Hebrew | Bible quotations |

Every font is a WOFF2 file in `assets/fonts/`, served from the theme. Qur'an quotations are in the Uthmani script, checked word for word with `tools/verify/quran_uthmani.py`.

## Data files

| File | Holds |
|---|---|
| `content/manifest.json` | Every content file with its checksum. The importer checks it and every file before it changes a post |
| `content/pages/*.json`, `content/posts/*.json` | The pages and Enrichment articles, as block markup |
| `content/terms.json` | Categories and tags. While the Tags setting is on, a sync deletes every tag whose slug and name are not here, and posts lose it |
| `content/settings.json` | Front page, posts page, permalinks, site title and tagline |
| `content/reading-list.json` | The reading list |
| `author.json` | The author box, byline and `Person` schema. Nothing author-related shows until `name` is set |
| `book.json` | The companion book. The Book page returns 404 until `title` is set |
| `assets/img/credits.json` | The credit and licence of every image |
| `tools/build/content/*.txt` | The article sources the build turns into `content/` |
| `inc/data/seo-titles.json` | Short first parts of title tags, 27 characters at most, for the items whose full title would take the tag to 60 characters or more |
| `inc/data/contextual-links.json` | The contextual links: each entry is a path on this site and the phrases that link to it. The first entry to name a phrase wins |

`content/**` is stored with exact bytes (`-text` in `.gitattributes`), since the checksums are byte-exact.

## Packaging

The documentation standard in `.claude/skills/theme-docs/SKILL.md` is a shared standard and says the docs ship in the zip. This theme keeps one exception, set in `.gitattributes` before the standard: `docs/README.md` is `export-ignore` and stays in the repository. The readme, the changelog, this file, the upgrade notes and the credits ship, and the theme `.htaccess` refuses every one of them on a live site.

## Names that must not change

These are stored in live databases or `.htaccess` files. A rename would lose data or leave rules behind.

| Kind | Names |
|---|---|
| Options | `islamicdilemma_content_state`, `islamicdilemma_content_registry`, `islamicdilemma_remove_other_content`, `islamicdilemma_remove_other_tags`, `islamicdilemma_initial_cleanup_done`, `islamicdilemma_attach_waiting`, `islamicdilemma_attach_scanned`, `islamicdilemma_dropins_status` |
| Post meta | `_islamicdilemma_key`, `_islamicdilemma_checksum`, `_islamicdilemma_source`, `_islamicdilemma_asset`, `_islamicdilemma_attach_pending`, `_islamicdilemma_key_points` |
| Filters | `islamicdilemma_remove_other_content`, `islamicdilemma_remove_other_tags`, `islamicdilemma_seo_enabled`, `islamicdilemma_llms_txt_enabled`, `islamicdilemma_contact_recipient`, `islamicdilemma_author`, `islamicdilemma_book`, `islamicdilemma_reading_path`, `islamicdilemma_next_steps`, `islamicdilemma_progress_enabled`, `islamicdilemma_contextual_links_map`, `islamicdilemma_contextual_links_enabled`, `islamicdilemma_contextual_links_max`, `islamicdilemma_seo_titles` |
| Blocks and styles | `islamicdilemma/answer-box`, `islamicdilemma/next-step`, the `faq` style of Details blocks, the `islamicdilemma/faq` pattern |
| Constants | `ISLAMICDILEMMA_ALLOW_XMLRPC`, `ISLAMICDILEMMA_BLOCK_DIRECT_FILES` |
| `.htaccess` markers | `Block xmlrpc.php`, `Islamic Dilemma: block direct file access` |
| Error page mark | "Written by the Islamic Dilemma theme; safe to delete." in each page's header comment |
| Folders in wp-content | `islamicdilemma-errors/` |
| Cron events | `islamicdilemma_attach_batch`, `islamicdilemma_attach_scan` |
| WP-CLI | `wp islamicdilemma content status`, `sync`, `restore` |

## Article page

The single and page templates keep this order: breadcrumbs, title, deck, meta row (byline, published date, "Updated" date only when it differs by a day or more, reading time), key points, text, tags, author box, next step. Articles keep the sidebar with "On this page" and "Related reading".

- The next step offers only keep reading, check the sources and ask a question. Never a price, a buy button or sign-up wording.
- FAQ-style questions show on the page only. No FAQPage markup, as `.claude/SEO.md` rules out.
- The author's name shows in the byline and the author box, as before.
- Contextual links stay on this site, and the site is never described in public as part of a network of sites.

## Homepage order

`templates/front-page.html` renders five patterns in this order, and none is removed: `hero-argument`, `two-alternatives`, `bible-mirror`, `begin-here`, `recent-enrichment`. Each section opens with a line that leads on from the one before. `tools/build/theme_trees.py` lists the same order.

## Credits that must stay

- The font credits and licences in `docs/CREDITS.md` and the Copyright section of `docs/readme.txt`. Sabon Next LT is used under the site owner's licence from Monotype, and the SBL fonts under the Society of Biblical Literature's licence.
- The credit beside each image and in `assets/img/credits.json`.
- The note in `docs/CREDITS.md` that Yusuf Ali's wording is verbatim, with punctuation changed only.
- The Quranic Arabic Corpus credit for quoted morphology tags.
