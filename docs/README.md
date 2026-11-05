# Islamic Dilemma: technical reference

The Twenty Twenty-Five child theme behind islamicdilemma.info. This file is for developers, hosts and anyone who builds, tests or deploys the theme. The site owner's guide is `docs/readme.txt`, the fixed facts and rules are in `docs/SSOT.md`, and the release history is in `docs/CHANGELOG.md`. `docs/UPGRADING.md` has the notes for each update and the roadmap.

## At a glance

| Item | Value |
|---|---|
| Version | 1.4.6 (`style.css`; `ISLAMICDILEMMA_VERSION` reads it) |
| Parent | Twenty Twenty-Five, which must stay installed |
| WordPress | 6.7 or later, tested on 7.1 |
| PHP | 7.4 or later; CI runs 7.4, 8.4 and 8.5 |
| Web server | Apache 2.2 or 2.4 gets its rules written by the theme. Nginx needs the rules below in the server block |
| Multisite | Works. The `.htaccess` sections and the error pages stay on a theme switch, and only the main site writes the error pages |
| Build step | None. No Tailwind, no bundler; the PHP, CSS and JS run as shipped |

## Install

1. Install Twenty Twenty-Five (Appearance, Themes).
2. Upload `islamicdilemma.zip` at Appearance, Themes, Add New, Upload Theme, or copy the folder to `wp-content/themes/islamicdilemma` over FTP with hidden files shown.
3. Activate it.

The folder name is `islamicdilemma`, all lower case. `docs/readme.txt` walks the owner through the FTP route, with the temporary folder and the rename.

## Build, test and release

The repository root is the theme. `git archive` leaves out the development files marked `export-ignore` in `.gitattributes` (`tools/`, `tests/`, `.github/`, `.claude/`, `docs/README.md`, the brand source and the git files):

```bash
git archive --format=zip --prefix=islamicdilemma/ -o islamicdilemma.zip HEAD
```

The checks, all run from the repository root:

```bash
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l
php tests/content-sync.php
php tests/content-sync.php --no-trash
php tests/error-pages-test.php
php tests/htaccess-files-test.php
php tests/live-logs-test.php
php tools/build/error_pages.php --check
find assets blocks tools tests -name '*.js' -print0 | xargs -0 -n1 node --check
node tests/search-overlay.js        # needs Playwright and Chromium
python3 tools/style/lint.py         # must report 0 findings
python3 tools/build/check_serializer.py
python3 -I tools/verify/yusufali.py . report
```

GitHub Actions (`.github/workflows/checks.yml`) runs the PHP syntax check, the importer and error page checks and the web server rules on PHP 7.4, 8.4 and 8.5. It also runs the JavaScript syntax check and the search dialog in Chromium at phone and desktop widths, with and without reduced motion. A last job installs a disposable WordPress, activates the theme and runs `tests/wordpress.php` against a real database. `tests/live-logs-test.php` is not in CI yet; run it by hand.

Content is authored as plain text in `tools/build/content/`. `python3 tools/build/build_content.py refresh . <post-slug> ...` writes the post JSON, `content/terms.json`, the manifest and its checksums. Pages with columns or patterns still go through `tools/build/serialize.js`, which uses WordPress's own serializer.

To release: bump the version in `style.css` and `docs/readme.txt`, add the entry to `docs/CHANGELOG.md` and its short form to the readme, run the checks above, and build the zip. `docs/SSOT.md` has the full checklist.

## Architecture

### Boot sequence

1. `functions.php` defines `ISLAMICDILEMMA_VERSION` (from `style.css`) and `ISLAMICDILEMMA_PAPER_URL`.
2. It lists every file it loads, checks that each one is readable, and only then loads them inside a `try`. A missing or half-written file sends visitors to `islamicdilemma_incomplete_theme()` (the upload guard, below).
3. `after_setup_theme` (priority 11) adds editor styles and page excerpts, and removes post formats and core's bundled patterns.
4. `init` registers the eight blocks and the pattern category, and drops the parent's patterns. At priority 30 `islamicdilemma_maybe_sync()` brings the content up to date when a content file changed; at 40 `islamicdilemma_maybe_attach_scan()` queues posts that still hold a theme image address.
5. `admin_init` writes or repairs the error pages (`islamicdilemma_maybe_install_dropins()`) and the `.htaccess` sections (`islamicdilemma_sec_sync_htaccess()`, at most once an hour).
6. `after_switch_theme` runs the first import (`islamicdilemma_on_activation()`), writes the error pages and forces the `.htaccess` sync. `switch_theme` removes the theme's error pages and `.htaccess` sections on a single site.

### File map

| Path | Contents |
|---|---|
| `style.css` | Theme header only |
| `functions.php` | Boot, the upload guard, setup, assets, blocks, patterns, excerpts |
| `theme.json` | Palette, fonts, sizes, layout widths, element styles |
| `templates/` | front-page, page, single, home (Enrichment), category, tag, archive, index, search, 404 |
| `parts/` | header (with the search overlay) and footer |
| `patterns/` | The five homepage sections, the Arabic verse card, the premise card and the article with sidebar |
| `blocks/` | author-box, author-byline, brandmark, reading-time, result-type, search-toggle, shahada, term-entry |
| `inc/content/` | Content sync and its admin screen and WP-CLI command, shortcodes, glossary entries and their images, the reader tools (glossary, reading list, verse index, contact form, old-address redirects) |
| `inc/editor/` | Block styles |
| `inc/identity/` | `author.php`, `book.php` and `brand.php`: they read `author.json` and `book.json` at the theme root and print the icons from `assets/brand/` |
| `inc/presentation/` | Light-only mode, render filters, the login screen, the error pages and their HTML |
| `inc/security/` | `hardening.php`: REST users, XML-RPC and the `.htaccess` sections |
| `inc/seo/` | Titles, descriptions, Open Graph, the JSON-LD graph, robots.txt and `/llms.txt` |
| `content/` | Pages, posts, terms, settings and the reading list as JSON, with `manifest.json` |
| `assets/` | `css/`, `js/`, `fonts/`, `img/`, `brand/` and `downloads/` (the quick reference PDF) |
| `errors/` | Static 403, 500, 502, 503 and 504 pages for a site at the domain root |
| `author.json`, `book.json` | The author and the companion book |
| `docs/` | This README, the readme, the changelog, the single source of truth, the upgrade notes and the credits |
| `tools/` | Build pipeline (`build/`), brand generators (`brand/`), images (`images/`), the PDF (`pdf/`), the style linter (`style/`) and source checks (`verify/`) |
| `tests/` | PHP and browser checks; older interactive checks in `tests/browser/manual/` |
| `.claude/` | Contributor rules: `CLAUDE.md`, `FARUQI.md`, `SEO.md` and the skills. Never shipped |

### Homepage

`templates/front-page.html` renders five patterns in an order that tells one story. Keep it when adding to the homepage, and remove no section.

| Order | Pattern | Label | Job in the story |
|---|---|---|---|
| 1 | `hero-argument` | A critical Muslim examination | The claim: the dilemma is a false one, with the argument judged premise by premise |
| 2 | `two-alternatives` | First, the shape of the argument | The problem: the two alternatives and the Qur'an's own position they leave out (Q 5:48) |
| 3 | `bible-mirror` | Then, the same test applied to the Bible | The evidence: Jeremiah 8:8 and Matthew 23:2–3 hold the same position |
| 4 | `begin-here` | Now, read the case in full | The next step: the claim, the response and the sources, in that order |
| 5 | `recent-enrichment` | Keep reading in Enrichment | Where the case continues |

Each section's opening paragraph begins with a line that leads on from the section before. `tools/build/theme_trees.py` lists the same order.

### Blocks, block styles and shortcodes

Block styles for editors:

- Details: Verdict (Granted, Equivocates, Does not follow, False, Unsupported), for premise cards
- Paragraph: Arabic verse, Lead, Eyebrow label, Verse reference
- Group: Verse card, Gold callout, Sidebar box, Card
- Quote: Scripture quotation
- List: Footnotes
- Button: Ghost on navy
- Text toolbar: "Qur'an reference" tags a reference such as Q 5:47 in gold

Shortcodes: `islamicdilemma_glossary`, `islamicdilemma_toc`, `islamicdilemma_reading_time`, `islamicdilemma_book`, `islamicdilemma_reading_list`, `islamicdilemma_related`, `islamicdilemma_result_type`, `islamicdilemma_sitemap`, `islamicdilemma_verse_index`, `islamicdilemma_contact` and `islamicdilemma_breadcrumbs`.

The contact form posts to `admin-post.php` (`islamicdilemma_contact`). It uses a hidden trap field, a signed timestamp and a rate limit, and mails the message to the admin address.

### Design

Light only. The parent theme's style variations, its dark ones among them, are hidden from the Site Editor.

The palette and the fonts are listed in `docs/SSOT.md`; every font is a WOFF2 file served from the theme. Qur'an quotations are in the Uthmani script, checked word for word with `tools/verify/quran_uthmani.py`.

## Content sync

The site's content ships with the theme as JSON in `content/`. On the first activation the theme:

- imports every page and Enrichment article, with categories and tags;
- moves every other post and page on the site to the Trash (restore from Posts, Trash or Pages, Trash within 30 days);
- sets Home as the front page and Enrichment as the posts page, turns on pretty permalinks if they are off, and sets 10 posts per page, the site title and the tagline.

A notice in the dashboard reports what was added and what was moved to the Trash. If the Trash is off (`EMPTY_TRASH_DAYS` is zero), cleanup reports an error and keeps the content. The theme never deletes a post outright.

Moving other content to the Trash is on by default. Switch it off before activating, with `wp option update islamicdilemma_remove_other_content 0`, with `add_filter( 'islamicdilemma_remove_other_content', '__return_false' );`, or at Appearance, Theme content, Settings.

When a new version holds new or changed content, it syncs on the next page load. The importer checks the manifest, every JSON file and its checksum before it changes any post. An incomplete package reports an error and changes nothing, and a failed write stays pending for the next try.

During a sync, new items are added, unedited theme items take the new version, and items the theme dropped go to the Trash unless edited. An item edited by hand (its text, or its status, slug, date, order, parent, template, comments, categories, tags or featured image) is never overwritten or removed, with one exception: the tag clean-up below. One the owner deleted is never recreated.

Each sync also deletes every tag whose slug and name are not in `content/terms.json` (`islamicdilemma_remove_other_tags`, on by default). `wp_delete_term()` removes it from every post, the owner's own posts too. Switch the setting off to keep such tags.

Images in the content are copied into the Media Library after the import, so WordPress serves them at responsive sizes. The import queues the posts, and a cron batch (`islamicdilemma_attach_batch`) adds at most 3 images, each started within the batch's first 8 seconds, then schedules the next batch while any are left. A post edited during a batch is left as the editor saved it.

```bash
wp islamicdilemma content status
wp islamicdilemma content sync
wp islamicdilemma content restore post:injil-where-is-it page:about
```

## Settings

Appearance, Theme content (`themes.php?page=islamicdilemma-content`, capability `edit_theme_options`). The code is in `inc/content/content-admin.php`, and the Error pages tab is drawn by `inc/presentation/error-pages.php`.

| Tab | What it holds | Stored in |
|---|---|---|
| Content (`tab=content`) | Every theme item with its state, Sync now, and Restore the theme version of the selected items | `islamicdilemma_content_state`, `islamicdilemma_content_registry` |
| Settings (`tab=settings`) | First activation: move other content to the Trash. Tags: delete tags that are not part of the theme, from every post | `islamicdilemma_remove_other_content`, `islamicdilemma_remove_other_tags` (both `1` by default) |
| Error pages (`tab=errors`) | The state of each error page, the Install error pages button and the `ErrorDocument` lines for this site | `islamicdilemma_dropins_status` |

`author.json` and `book.json` are edited in the theme folder; they have no screen.

## Hooks, filters and constants

| Filter | Default | Effect |
|---|---|---|
| `islamicdilemma_remove_other_content` | the option, `1` | First activation moves other posts and pages to the Trash |
| `islamicdilemma_remove_other_tags` | the option, `1` | Each sync deletes tags not in `content/terms.json` |
| `islamicdilemma_seo_enabled` | true, false with an SEO plugin active | The theme's title format, meta tags and JSON-LD |
| `islamicdilemma_llms_txt_enabled` | true | Serves `/llms.txt` |
| `islamicdilemma_contact_recipient` | `admin_email` | Where the contact form mails |
| `islamicdilemma_author` | `author.json` | The author's details |
| `islamicdilemma_book` | `book.json` | The companion book's details |

| Constant | Where | Effect |
|---|---|---|
| `ISLAMICDILEMMA_VERSION` | `functions.php` | The theme version, from `style.css` |
| `ISLAMICDILEMMA_PAPER_URL` | `functions.php` | The study's address |
| `ISLAMICDILEMMA_ALLOW_XMLRPC` | `wp-config.php` | True keeps XML-RPC on and leaves out the "Block xmlrpc.php" section |
| `ISLAMICDILEMMA_BLOCK_DIRECT_FILES` | `wp-config.php` | False leaves out the "block direct file access" section |
| `ISLAMICDILEMMA_SEC_FILES_MARKER` | `inc/security/hardening.php` | The marker of that section |
| `ISLAMICDILEMMA_STATIC_ERRORS` | `inc/presentation/error-pages.php` | `islamicdilemma-errors`, the folder in wp-content for the static pages |

The theme defines no `*_KEEP_*` constant, since it has no production clean-up of development files (see the roadmap).

## Security

### The upload guard

The owner uploads over FTP, one file at a time, so for a while a file can be missing or half written. `functions.php` checks every file it loads before any loads, and loads them inside a `try`. Either fault gives visitors a 503 "Back shortly" page with `Retry-After: 300`, `no-store` and `noindex`, from `islamicdilemma_incomplete_theme()`.

The error log gets one line per problem every ten minutes. The login screen (known by its path), AJAX, cron and WP-CLI go on, and the admin sees the page only when a file broke part-way through loading. `functions.php` is parsed before any of it runs, so it cannot guard itself.

### Accounts and XML-RPC

- Visitors who are not logged in get no `/wp/v2/users` routes. Logged-in editors keep them, since the block editor reads them.
- Author archives and `?author=N` go to the About page (`#author`) at `template_redirect` priority 1, before `redirect_canonical()` could name the account. Usernames stay out of the sitemap and the feeds.
- XML-RPC is off: no methods, no pingback header, no RSD link, and `xmlrpc.php` answers 403. Define `ISLAMICDILEMMA_ALLOW_XMLRPC` as true for a tool that needs it.

### The theme folder's own rules

`.htaccess` in the theme folder is read by Apache before WordPress runs. It refuses:

- every dot entry (`.git`, `.github`, `.claude`, `.env` and the like), with `.well-known` left open;
- every PHP file in the theme;
- every Markdown file, by name;
- the `tests`, `tools` and `docs` folders.

Each rule is there with mod_alias and again with mod_rewrite, and an Apache 2.4 `<If>` refuses dot folders with neither module. `tests/`, `tools/` and `docs/` each carry a deny-all `.htaccess` of their own, and their PHP scripts exit unless run from the command line. `assets/downloads/` stays open for the quick reference PDF.

### The sections in the site's .htaccess

On Apache, `inc/security/hardening.php` keeps two marked sections in the site's `.htaccess`. They are written on an administrator's visit (at most once an hour) and on activation, and taken out when another theme is activated.

On a network every site shares one `.htaccess` and another site may still run the theme, so there they stay. No section is written where the file cannot be written or the server is not Apache.

For a site at the domain root they read:

```apache
# BEGIN Block xmlrpc.php
<Files xmlrpc.php>
<IfModule mod_authz_core.c>
Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
Order Deny,Allow
Deny from all
</IfModule>
</Files>
# END Block xmlrpc.php
# BEGIN Islamic Dilemma: block direct file access
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule (^|/)\.(git|claude) - [F]
RewriteRule ^wp-includes/(?!(js/tinymce/wp-tinymce|ms-files)\.php$).+\.php(/|$) - [F]
RewriteRule ^wp-admin/includes/.+\.php(/|$) - [F]
RewriteRule ^(wp-config\.php|wp-settings\.php|wp-config-sample\.php|wp-admin/install\.php|readme\.html)(/|$) - [F]
RewriteRule ^wp-content/themes/.+\.php(/|$) - [F]
RewriteRule ^wp-content/themes/islamicdilemma/(tests|tools|docs)(/|$) - [F]
</IfModule>
# END Islamic Dilemma: block direct file access
```

The second section also holds an `<IfModule !mod_rewrite.c>` block. In it an Apache 2.4 `<If>` tests the requested file's full path on disk against the same files, so it carries the server's own folder names (`islamicdilemma_sec_files_if_pattern()`).

"Block xmlrpc.php" is written only while XML-RPC is off. Other themes the owner runs use the same marker, so a switch between them never writes it twice. Define `ISLAMICDILEMMA_BLOCK_DIRECT_FILES` as false to leave the second section out.

The rules carry the site's own folder prefixes, so a site in a subfolder or WordPress in its own folder is covered.

WordPress also reads `wp-config.php` from the folder above its own. The section covers that copy only when the `.htaccess` sits above the core folder. With the `.htaccess` in the core folder itself (a site in `/blog/` with `wp-config.php` in the web root), deny that file in the server configuration or the web root's own `.htaccess`.

`tests/htaccess-files-test.php` checks the lines and runs them over the request paths, at the domain root and in a subfolder.

### Nginx

Nginx reads no `.htaccess`. Put these in the server block, above the `location ~ \.php$` block that hands PHP to PHP-FPM, so they match first:

```nginx
location ~ /\.(git|claude) { return 403; }
location ~ ^/wp-content/themes/islamicdilemma/(.+/)?\.(?!well-known(/|$)) { return 403; }
location ~ ^/wp-includes/(?!js/tinymce/wp-tinymce\.php$|ms-files\.php$).+\.php(/|$) { return 403; }
location ~ ^/wp-admin/includes/.+\.php(/|$) { return 403; }
location ~ ^/(wp-config\.php|wp-settings\.php|wp-config-sample\.php|wp-admin/install\.php|readme\.html)(/|$) { return 403; }
location ~ ^/wp-content/themes/.+\.php(/|$) { return 403; }
location ~ ^/wp-content/themes/islamicdilemma/(tests|tools|docs)(/|$) { return 403; }
location ~* ^/wp-content/themes/islamicdilemma/.+\.md$ { return 403; }
location = /xmlrpc.php { return 403; }
```

The first line refuses `.git` and `.claude` anywhere on the site, as the section in the site's `.htaccess` does. The second refuses every other dot file and folder (`.env`, `.htaccess` and the like) inside the theme folder only, with `.well-known` left open, as the theme folder's `.htaccess` does. Neither touches a dot path another application on the host may serve.

The `(/|$)` endings also catch a PHP file asked for with path info after it, as the Apache rules do.

Leave out the last line while `ISLAMICDILEMMA_ALLOW_XMLRPC` is true. For WordPress in a subfolder, put its path in front of every rule, as in `^/blog/wp-includes/`. WordPress also reads `wp-config.php` from the folder above its own, so refuse that copy as well: `location ~ ^/wp-config\.php(/|$) { return 403; }` beside `location ~ ^/blog/wp-config\.php(/|$) { return 403; }`.

### The installer

The theme cannot protect a site whose WordPress core sits on the server without `wp-config.php`: the installer then answers anyone. Upload `wp-config.php` first, or upload core to a folder the web server does not serve and rename it into place. `docs/readme.txt` says the same to the owner.

## Error pages

WordPress shows 3 files from `wp-content` when it cannot run the theme:

| File | Shown when | Status sent |
|---|---|---|
| `maintenance.php` | WordPress is updating | 503, `Retry-After: 300` |
| `db-error.php` | the database cannot be reached | 503, `Retry-After: 600` |
| `php-error.php` | a fatal PHP error | 500 |

The theme writes all 3 on activation, and again when an administrator loads an admin page and one is missing or out of date. Appearance, Theme content, Error pages shows each file's state and has an Install error pages button.

Each file starts with "Written by the Islamic Dilemma theme; safe to delete." A file another theme marked with that line in its header comment is replaced; the same words further down a file do not count. A file with no such line is left alone. Switching to another theme removes the theme's own files.

`php-error.php` answers a 503 with `Retry-After: 120` when the error looks like a half finished update: a missing file, a syntax error or an undefined function, raised in `wp-includes/`, `wp-admin/` or the theme. It tests the file the error names and the first line of the message, without its trailing ` in /path:line`. The stack trace is ignored, since it names `wp-includes/` for any fatal raised through a hook.

The pages are built by `inc/presentation/error-page-html.php`, with `assets/css/error-page.css` copied inline. Fonts come from the theme folder, and nothing is fetched from another host.

Some errors come from the web server before WordPress starts. The theme also writes the static pages for 403, 500, 502, 503 and 504 into `wp-content/islamicdilemma-errors/`, with this site's home address and theme folder, so they work for a site in a subfolder too.

The web server shows an ErrorDocument at the address that failed, so every link in them is absolute. The Error pages tab shows the lines with the site's real path. At the domain root they read:

```apache
# Islamic Dilemma: pages for errors the web server answers before WordPress runs
ErrorDocument 403 /wp-content/islamicdilemma-errors/403.html
ErrorDocument 500 /wp-content/islamicdilemma-errors/500.html
ErrorDocument 502 /wp-content/islamicdilemma-errors/502.html
ErrorDocument 503 /wp-content/islamicdilemma-errors/503.html
ErrorDocument 504 /wp-content/islamicdilemma-errors/504.html
```

On Nginx the same pages go in with `error_page 403 /wp-content/islamicdilemma-errors/403.html;` and so on for each code.

The theme's `errors/` folder holds the same pages built for a site at the domain root, for a server where WordPress has not run yet. After changing the error page text or `error-page.css`, rebuild them with `php tools/build/error_pages.php`; `--check` fails when one is stale.

On a network every site shares `wp-content`. Only the main site writes the files, a subsite's tab says so, and a theme switch leaves them in place.

## SEO and structured data

Edit `author.json` in the theme folder to identify the site's author:

| Field | Used for |
|---|---|
| `name` | Byline, author box, `Person` schema, meta author. Nothing author-related shows until it is set |
| `job_title`, `bio`, `bio_long` | Author box (short bio after articles; long bio on the About page) and schema `jobTitle` and `description` |
| `photo`, `photo_alt` | Author photo, theme-relative (`assets/img/author.avif`) or a full URL |
| `same_as` | Profile URLs, shown on the About page and as schema `sameAs` |
| `knows_about`, `knows_language` | Schema expertise and languages |
| `honorific_prefix`, `alternate_name`, `nationality`, `home_location`, `email` | Optional schema fields. Leave empty to omit |

Every page gets a title in the form `<page> | Islamic Dilemma Is No Dilemma` (`islamicdilemma_title_parts()`), Open Graph and Twitter tags, and one JSON-LD graph.

The meta description comes from the excerpt (`islamicdilemma_meta_description()`). The shipped Home page has none, so the front page takes the tagline and a fixed sentence, and an article without one takes its first 32 words.

The JSON-LD graph holds:

- `WebSite`;
- `Person` (the author);
- `WebPage` (`ProfilePage` on About, `CollectionPage` on archives);
- `Article` or `BlogPosting`;
- `BreadcrumbList`;
- `ImageObject` with the credit and licence from `assets/img/credits.json`.

Search results are `noindex, follow`, archives carry a canonical link, and `/llms.txt` lists the site for AI assistants. With Yoast, Rank Math, AIOSEO, SEOPress or The SEO Framework active, the theme prints none of this (`islamicdilemma_seo_enabled`). `.claude/SEO.md` holds the rules every change must meet.

Old addresses that crawlers still ask for redirect with a 301 (`islamicdilemma_moved_address()` in `inc/content/tools.php`). These are the verse tags without separators (`/tag/q-568/` to `/tag/q-5-68/`) and dated addresses such as `/2026/10/27/is-the-god-of-islam-distant/`.

## Companion book

The site can serve as the companion website to a book without changing its structure. Fill in `book.json` in the theme folder:

| Field | Used for |
|---|---|
| `title`, `subtitle`, `status` | The Book page (`/book/`). Until `title` is set, the page returns 404 and stays out of the sitemaps, the Sitemap page and llms.txt |
| `description`, `cover`, `cover_alt`, `publisher_note`, `isbn` | Shown on the Book page. `cover` is theme-relative (for example `assets/img/book-cover.webp`) or a full URL |
| `buy_links` | Buttons on the Book page: `[ { "label": "Order the paperback", "url": "https://…" } ]` |
| `chapters` | `[ { "number": 1, "title": "…", "pages": [ "muslim-response", "judge-by-it-uphold-it" ] } ]`. Each listed article ends with a note naming the chapter |
| `errata` | Corrections to the printed book: `[ { "page": "p. 42", "reads": "…", "should": "…" } ]` |

Article URLs stay stable, so the book can print them.

## Images

Images are stored in the theme and credited in `docs/CREDITS.md` and `assets/img/credits.json`. Content images are copied into the Media Library by the cron batches; images used by patterns and templates stay theme assets.

They are AVIF or WebP only (`tools/images/convert.py`). No API key is stored in the theme; `tools/images/source.py` reads `PEXELS_KEY` and `PIXABAY_KEY` from the environment.

## Performance

- One stylesheet on the front end, `assets/css/theme.css`, versioned by its file time so edits bust caches.
- Fonts are WOFF2 files from the theme, so there is no third-party request.
- Phones get a smaller hero photo, and content images reach visitors at responsive sizes once copied into the Media Library.
- Image copies run in cron batches of at most 3, so no visitor's request waits on a download.
- The parent's patterns and core's remote pattern suggestions are not loaded.

## Troubleshooting

- "Back shortly" on every page: a theme file is missing or half written. The error log names it. Upload the file or the whole folder again.
- A sync reports an error and changes nothing: the `content/` folder is incomplete or a checksum does not match. Upload the whole folder.
- The error pages tab says another file is there: a plugin or the host wrote it, and the theme leaves it alone. Delete it by hand to let the theme write its own.
