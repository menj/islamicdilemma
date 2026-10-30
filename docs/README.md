# Islamic Dilemma: Twenty Twenty-Five child theme

Child theme for islamicdilemma.info. Requires the Twenty Twenty-Five parent theme (bundled with WordPress), WordPress 6.7 or later (tested on 7.1.4) and PHP 7.4 or later.

## Install

1. Make sure Twenty Twenty-Five is installed (Appearance → Themes).
2. Upload `islamicdilemma.zip` at Appearance → Themes → Add New → Upload Theme.
3. Activate it.

## What happens on activation

The site's content ships with the theme as JSON files in `content/`. On the **first** activation the theme:

- imports every page and Enrichment article, with categories and tags;
- moves every other post and page on the site to the **Trash** (not permanently deleted; restore from Posts → Trash or Pages → Trash within 30 days);
- sets Home as the front page, Enrichment as the posts page, pretty permalinks if they are off, 10 posts per page, and the site title and tagline.

A notice in the dashboard reports exactly what was added and what was moved to the Trash. If WordPress Trash is disabled (`EMPTY_TRASH_DAYS` is zero), cleanup reports an error and keeps the content; it never permanently deletes posts.

Moving other content to the Trash is **on by default**. To keep existing posts and pages, switch it off before activating, either with `wp option update islamicdilemma_remove_other_content 0` or with the filter `add_filter( 'islamicdilemma_remove_other_content', '__return_false' );`. The setting also appears under Appearance → Theme content.

Images in the theme content are copied into the **Media Library** on import, so WordPress serves them at responsive sizes (`srcset`).

## Theme updates

When a new theme version contains new or changed content, it syncs automatically on the next page load. The importer validates the manifest, every JSON file and its checksum before changing any posts. Deploy the complete `content/` folder together; after authoring changes, regenerate its manifest checksums with the build pipeline. Incomplete packages report an error without changing content. Failed writes remain pending for retry.

During a successful sync:

- new pages and articles are added;
- theme items you have **not** edited are updated to the new version;
- theme items removed from the theme are moved to the Trash, unless you edited them.

**Your changes are protected.** A theme item you have edited by hand, whether its text or its settings (status, slug, date, order, parent, template, comments, categories, tags or featured image), is never overwritten or removed, and one you deleted is never recreated. Articles you write yourself are never touched by the theme after the first activation.

To deliberately replace your edits with the theme's version, go to **Appearance → Theme content**, tick the items and choose "Restore the theme version of the selected items". With WP-CLI:

```
wp islamicdilemma content status
wp islamicdilemma content sync
wp islamicdilemma content restore post:injil-where-is-it page:about
```

## Design

Light only. The parent theme's style variations (including its dark ones) are hidden from the Site Editor. Colours: midnight navy `#18283C`, antique gold `#B18B54`, parchment `#F7F4ED`, defined as theme.json presets. Fonts are served from the theme, all as WOFF2, never from Google: Marcellus (headings), Sabon Next LT with Literata for missing transliteration letters (reading), IBM Plex Sans (interface), KFGQPC Hafs Uthmanic Script (Qur'anic Arabic only), Noto Naskh Arabic (hadith), Arslan Wessam (all other Arabic). Qur'an quotations are in the Uthmani script, checked word for word with `tools/verify/quran_uthmani.py`. No green, no Tailwind, no build step.

## What the theme contains

| Path | Contents |
|---|---|
| `theme.json` | Palette, fonts, sizes, layout widths, element styles |
| `templates/` | front-page, page, single, home (Enrichment), category, tag, archive, index, search, 404 |
| `parts/` | header (with the search overlay) and footer |
| `patterns/` | Homepage sections, Arabic verse card, premise card, article with sidebar |
| `blocks/` | Search overlay, reading time and result-type blocks |
| `inc/` | PHP modules grouped into `content/`, `editor/`, `identity/`, `presentation/` and `seo/` |
| `content/` | Pages, posts, categories and settings as JSON |
| `assets/` | Stylesheet, scripts, fonts and images |

## Block styles for editors

- Details: Verdict (Granted, Equivocates, Does not follow, False, Unsupported), for premise cards
- Paragraph: Arabic verse, Lead, Eyebrow label, Verse reference
- Group: Verse card, Gold callout, Sidebar box, Card
- Quote: Scripture quotation
- List: Footnotes
- Button: Ghost on navy
- Text toolbar: "Qur'an reference" tags a reference such as Q 5:47 in gold

## Images

Images are stored in the theme and credited in `docs/CREDITS.md`. Content images are copied into the Media Library during import; images used directly by patterns and templates remain theme assets. No API keys are stored in the theme.

## Author, search and AI metadata

Edit `author.json` in the theme folder to identify the site's author:

| Field | Used for |
|---|---|
| `name` | Byline, author box, `Person` schema, meta author. **Required**: nothing author-related shows until it is set. |
| `job_title`, `bio`, `bio_long` | Author box (short bio after articles; long bio on the About page) and schema `jobTitle` and `description`. |
| `photo`, `photo_alt` | Author photo, theme-relative (`assets/img/author.avif`; AVIF preferred) or a full URL. |
| `same_as` | Profile URLs (social, academic, video channel). They appear as links on the About page and as schema `sameAs`. |
| `knows_about`, `knows_language` | Schema expertise and languages. |
| `honorific_prefix`, `alternate_name`, `nationality`, `home_location`, `email` | Optional schema fields. Leave empty to omit. |

Every page gets a meta description (from its excerpt), Open Graph and Twitter tags, and a JSON-LD graph:

- `WebSite`;
- `Person` (the author);
- `WebPage` (`ProfilePage` on About, `CollectionPage` on archives);
- `Article` or `BlogPosting`;
- `BreadcrumbList`;
- `ImageObject` with the credit and licence from `assets/img/credits.json`.

`/llms.txt` lists the site for AI assistants.

If Yoast, Rank Math, AIOSEO, SEOPress or The SEO Framework is active, the theme prints none of this. You can also switch it off with `add_filter( 'islamicdilemma_seo_enabled', '__return_false' );`.

## Companion book

The site can serve as the companion website to a book without changing its structure. Fill in `book.json` in the theme folder:

| Field | Used for |
|---|---|
| `title`, `subtitle`, `status` | The Book page (`/book/`). Until `title` is set, the page returns 404 and stays out of the sitemaps, the site map page and llms.txt. |
| `description`, `cover`, `cover_alt`, `publisher_note`, `isbn` | Shown on the Book page. `cover` is theme-relative (for example `assets/img/book-cover.jpg`) or a full URL. |
| `buy_links` | Buttons on the Book page: `[ { "label": "Order the paperback", "url": "https://…" } ]`. |
| `chapters` | `[ { "number": 1, "title": "…", "pages": [ "muslim-response", "judge-by-it-uphold-it" ] } ]`. The Book page lists each chapter with its articles, and each listed article ends with a note: "This article accompanies *Title*, chapter 1: …". |
| `errata` | Corrections to the printed book: `[ { "page": "p. 42", "reads": "…", "should": "…" } ]`. |

Article URLs stay stable, so the book can print them.

## Repository layout

The repository root is the theme: clone or download it into `wp-content/themes/islamicdilemma`. The development folders below are excluded from `git archive` exports (see `.gitattributes`), so this produces a clean, installable theme zip:

```bash
git archive --format=zip --prefix=islamicdilemma/ -o islamicdilemma.zip HEAD
```

| Folder | Contents |
|---|---|
| `tools/build/` | Authoring pipeline. Articles are plain text in `tools/build/content/`; `build_content.py` and `serialize.js` turn them into block markup, using WordPress's own serializer, and then into the JSON in `content/`. |
| `tools/` | Content generation in `build/`, brand generators in `brand/`, source verification in `verify/`. |
| `tests/` | Automated PHP and browser regressions; historical interactive browser checks in `browser/manual/`. |
| `docs/` | Review notes, the verification report, claim coverage and screenshots. |
| `design/` | The agreed HTML design (final candidate) that the theme was built from. |
| `docs/FARUQI.md`, `docs/SEO-RULES.md`, `docs/CLAUDE.md` | Editorial and search rules every change must follow. |

## Development checks

GitHub Actions checks PHP syntax and importer failure paths on PHP 7.4, 8.4 and 8.5, JavaScript syntax, and native search-dialog behavior in Chromium at phone and desktop widths with and without reduced motion. Run the standalone importer checks with `php tests/content-sync.php` and `php tests/content-sync.php --no-trash`. The test doubles cover import failures and retries; they do not replace installation and editor checks against a real WordPress database.

WordPress entry points (`style.css`, `functions.php`, `theme.json`, `templates/`, `parts/`, `patterns/` and `blocks/`) remain at the theme root, as required for installation. Runtime PHP is grouped by responsibility under `inc/`. Development tooling and tests are excluded from the installable archive.
