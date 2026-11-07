# Upgrading the Islamic Dilemma theme

How to bring a live site up to a new version, what each version asks of you, and where the theme is going next.

## Updating an existing site

1. Turn on hidden files in the FTP program (in FileZilla: Server, Force showing hidden files). The theme carries `.htaccess` files in its own folder and in `tests/`, `tools/` and `docs/`.
2. Upload the new `islamicdilemma` folder into `wp-content/themes/` as `islamicdilemma-new`. Send its `.htaccess` files first (the root one, then `docs/`, `tests/` and `tools/`), so on Apache their rules apply to each file as it arrives. Public assets stay readable, as on the live site, and Nginx relies on its server block rules instead.
3. Rename the live folder to `islamicdilemma-old` and at once `islamicdilemma-new` to `islamicdilemma`, with Appearance, Themes closed, at a quiet hour. A page asked for between the two renames finds no theme, and that screen switches the site to another theme if it finds the folder missing. If WordPress did switch to another theme, activate the theme again under Appearance, Themes. Delete the old one once the site looks right.
4. Open any admin page as an administrator. That writes the error pages and the `.htaccess` sections the new version wants.
5. Read the note below for every version you are passing.

You can skip versions. When the shipped content has changed, it syncs on the next page load after the upload, whatever version you came from, and it never overwrites an item edited by hand. A release whose content did not change, such as 1.4.6, 1.4.7, 1.4.8 or 1.4.9, syncs nothing.

An upload straight into the live folder is riskier. While one of the PHP files `functions.php` loads is missing, visitors see a 503 "Back shortly" page, and the login screen keeps working. Nothing checks `functions.php` itself, the templates, the styles, the images or `content/`, so the temporary folder and the rename stay the safe way.

Never upload WordPress core to a live folder without `wp-config.php` in place. The installer answers anyone in that state.

## Before the first activation

The first activation moves every other post and page to the Trash and deletes the tags the theme does not carry. It runs before Appearance, Theme content can be opened, so to keep them, switch both settings off first, with WP-CLI:

```bash
wp option update islamicdilemma_remove_other_content 0
wp option update islamicdilemma_remove_other_tags 0
```

## Notes by version

### To 1.4.11

Upload the whole theme folder. Then open Appearance, Theme content and press "Reset Updated dates set by image batches" once. It lists the items it changed, and it never touches an item you edited. On a site in a subfolder, also put a copy of `assets/brand/favicon.ico` in the domain root (or, with a Site Icon set, that icon converted to an ICO file named `favicon.ico`), since browsers ask for `/favicon.ico` there.


### To 1.4.10

Nothing to do. The new file is `assets/brand/favicon-32.png`. The first admin page writes the error pages again with the icon. If a `favicon.ico` file sits in the site root, the web server sends it and the theme never answers; delete it to use the theme's.

### To 1.4.9

Nothing to do. The first page load after the upload runs a one-time repair of the "Updated" dates.

- The repair looks at every theme item nobody has edited whose title, excerpt and text match the shipped files. If its modified date falls in or after the last sync made by an earlier version (with five minutes' grace), the date goes back to the date in its content file, or to its published date for a page. An item you edited keeps its date, and so does one whose date is older than the last sync.
- Appearance, Theme content lists the items it repaired under the last sync report. The option `islamicdilemma_dates_repaired` holds the version that ran it, so it runs once. To run it again, delete that option: `wp option delete islamicdilemma_dates_repaired`.
- From now on a sync keeps the modified date unless the text changed, and the image batches keep it too. Your own saves in the editor move it as before.
- Search engines read `dateModified` from the same date, so expect the older dates in the structured data after the next crawl.

### To 1.4.8

Nothing to do.

- Title tags stay under 60 characters. The front page and 43 pages and articles now take a short first part in the title tag only; their headings, addresses and the text are unchanged. Search Console may show the new titles after the next crawl.
- The structured data no longer names an Organization. The author is now the site's publisher, as `.claude/SEO.md` asks. Run the Rich Results Test on the front page and one article once the update is live.

### To 1.4.7

Nothing to do. The articles and pages gain their new parts on the next page load.

- The "Updated" date now shows the real date of the last change. Items the content sync wrote carry the date of that sync, so expect "Updated" with the day of the last sync on those.
- Key points stay empty until you type them in the Key points box on an edit screen, so no box shows on any page at first.
- If a page should not take contextual links, return false from the `islamicdilemma_contextual_links_enabled` filter for it, or remove its phrases from `inc/data/contextual-links.json`.
- The theme is now under the GNU GPL version 3 or later. The `LICENSE` file goes up with the folder.

### To 1.4.6

Nothing to do. The release adds documents and brings no new feature or content.

The routines every version change sets off still run once. The next admin page rewrites the error pages, which carry the version, and a cron scan a minute after the first page load queues any theme image still at its theme address; the image batches copy them into the Media Library afterwards, a few at a time.

### To 1.4.5

- Upload with hidden files shown, so the new `docs/.htaccess` goes up.
- On Apache, open an admin page: the theme writes "Block xmlrpc.php" and "Islamic Dilemma: block direct file access" into the site's `.htaccess`. Check the site afterwards. To leave the second section out, define `ISLAMICDILEMMA_BLOCK_DIRECT_FILES` as false in `wp-config.php` first.
- For a site in `/blog/` whose `wp-config.php` sits in the web root, the theme cannot refuse that file. Deny it in the web root's own `.htaccess` or the server configuration.
- On Nginx nothing is written. Ask the host to add these lines to the server block, above every other regex `location` (the PHP handler and any static-file block), since Nginx takes the first match. Leave out the last line while `ISLAMICDILEMMA_ALLOW_XMLRPC` is true, and, for a site in a subfolder, put the site's path in front of each rule but the first, which stays global, the theme-folder rules included. WordPress also reads `wp-config.php` from the folder above its own, so for a site in `/blog/` keep an unprefixed `location ~ ^/wp-config\.php(/|$) { return 403; }` beside the prefixed one.

  ```nginx
  location ~ /\.(git|claude) { return 403; }
  location ~ ^/wp-content/themes/islamicdilemma[^/]*/(.+/)?\.(?!well-known(/|$)) { return 403; }
  location ~ ^/wp-includes/(?!js/tinymce/wp-tinymce\.php$|ms-files\.php$).+\.php(/|$) { return 403; }
  location ~ ^/wp-admin/includes/.+\.php(/|$) { return 403; }
  location ~ ^/(wp-config\.php|wp-settings\.php|wp-config-sample\.php|wp-admin/install\.php|readme\.html)(/|$) { return 403; }
  location ~ ^/wp-content/themes/.+\.php(/|$) { return 403; }
  location ~ ^/wp-content/themes/islamicdilemma[^/]*/(tests|tools|docs)(/|$) { return 403; }
  location ~* ^/wp-content/themes/islamicdilemma[^/]*/.+\.md$ { return 403; }
  location = /xmlrpc.php { return 403; }
  ```

### To 1.4.4

- Upload with hidden files shown: the theme folder and `tests/` and `tools/` now carry an `.htaccess`.
- XML-RPC goes off. If a tool needs it (Jetpack, a mobile app), define `ISLAMICDILEMMA_ALLOW_XMLRPC` as true in `wp-config.php`.
- Images now reach the Media Library through WP-Cron. On a host where WP-Cron is switched off (`DISABLE_WP_CRON`), make sure a real cron job calls `wp-cron.php`, or the images stay at their theme addresses.

### To 1.4.0

Open any admin page once: the error pages are written into `wp-content`. To use the static pages for the errors Apache answers itself, paste the `ErrorDocument` lines from Appearance, Theme content, Error pages into the site's `.htaccess`.

### To 1.1.0

Fill in `author.json` in the theme folder. No byline, author box or author schema appears until `name` is set. `book.json` can stay empty until a book is announced.

### To 1.0.1

Slugs lose the words "a", "an" and "the". Addresses of the theme's own items change with the sync. Old addresses that crawlers still ask for are redirected from 1.4.4.

## Roadmap

Everything below is a plan, open to change. Each item names the evidence behind it. An item leaves this list when it ships, and goes into `docs/CHANGELOG.md`.

### Next

- Run `tests/live-logs-test.php` in CI. The other four test files run on every push, and this one does not (`.github/workflows/checks.yml`).
- Remove development files from a live site after an update: delete `.git`, `.claude` and `tests` on the first request after a version change. This theme only refuses them by `.htaccess`, which does nothing on Nginx, and the logs from 28 October to 4 November show 188 requests for `/.git/config`.
- Limit login attempts or offer a private login address. The logs from 28 October to 4 November show 2,655 login form posts, and 6 of them came from the owner.

- A mobile-first audit of the theme. `assets/css/theme.css` has 17 media queries. Eleven use `max-width`, none use `min-width`, and the rest test hover or reduced motion, so the base styles are written for desktop and then reduced for small screens. The audit covers overflow at 320 to 1280 px, tap targets, tables and embeds, forms, sticky elements, focus states and layout shift, and it applies low-risk CSS fixes first.
- A check of the live site for the book-cover details shipped after 1.4.11: the gold rule with a centre dot on every separator, and the gold top edge of the footer. They were tested on a mock-up page only. Look at a page with a separator and at the footer at 1440, 768 and 390 px.
- Record the work merged since 1.4.11 in `docs/CHANGELOG.md` under a new release number: the schema.org markup for every kind of content (`inc/seo/schema.php`, `tools/verify/schema_check.py`), the full 404 page, the fuller serializer, the book-cover details, and the one-time build of the site chrome on search pages. The `docs/readme.txt` changelog still carries the older block headed 1.1.0, which needs the same renumbering.
- Look at the live site after the next upload of `main`. The navy verdict panel under the Bible mirror on the home page was tested only on a mock-up page with fallback fonts, at 1400, 820 and 390 px. Check it with the real fonts, then open the two newest articles (`covenant-of-prophets`, `affirm-and-reinterpret`) and the source list (`sources-for-written-change`), and search a page for a stray "Allah". The rule is "God" wherever Yusuf Ali has "Allah" (`.claude/CLAUDE.md`).
- Rotate the Pexels and Pixabay API keys. Both were pasted into a working chat in November 2026. Neither is in any file in the repository, but a pasted key stays valid until it is changed.

### Later

- The Book page. Crawlers asked for `/book/` 6 times in the same logs and got a 404, as designed. It opens when the book is announced and `book.json` has a title.
- A ready installer lock. The logs show fatal errors on `wp-login.php` and `xmlrpc.php` on 1 November while WordPress core was half uploaded. The theme cannot run then; a lock rule for `wp-admin/install.php` and `setup-config.php`, shown on the Error pages tab, would cover the worst case.

- Bring `tools/build/theme_trees.py` back in line with the hand-edited theme files. A full regeneration once overwrote hand edits to `parts/footer.html`, `patterns/bible-mirror.php` and `templates/tag.html`, and they had to be restored. Compare the generator's output with every hand-edited file before anyone regenerates the whole theme, and bring the generator into line first. Only `templates/404.html` has been regenerated since.
- The ESV New Testament pull. A bulk puller (`tools/verify/esv_pull.py`, with a rewrite of `tools/verify/esv.py` that adds aliases, a fetch error class and retries) was written and never run or committed. It lived in a git stash in one working copy only, so it is lost unless someone recovers it. Write it again when the verification of New Testament quotations is next needed.
- The Book page needs a title. `book.json` has none, so the page stays hidden. The companion book's title is still unknown.
- Check the 31 entries marked "Not checked" in `post-sources-for-written-change`. They rest on books the sandbox could not reach, such as the *Encyclopaedia of the Qurʾān*, al-Bīrūnī, the al-Mahdī and Timothy exchange, and several gradings by al-Albānī and al-Mundhirī. Each needs the printed page or a library copy. Entries that Shamela or the lexicon can answer have been answered already.
- Finish two hadith chains. The *Musnad Aḥmad* chain for the Muʿādh ibn Jabal hadith (no. 19404) loses the name after "ʿAbd" in the text the sandbox read, and the al-Ṭabarānī chain for "The Children of Israel wrote a book" (*al-Awsaṭ*, vol. 5, pp. 358–359) breaks off mid-chain. Both need a full page. The Muʿādh chains were drawn in HadithGraph and show one shared strand through al-Qāsim ibn ʿAwf; the second hadith has not been drawn.
- More of the Arabic Lexicon. Six of the 43 dictionaries listed on arabiclexicon.hawramani.com are in use: Lane, al-Zamakhsharī, al-Fīrūzābādī, al-Rāghib, Abū Ḥayyān and al-Fayyūmī. The *Taʿrīfāt*, *Tawqīf* and *Kashshāf Iṣṭilāḥāt al-Funūn* could define *tawātur*, *tafsīr* and *taʾwīl*.
- Answer the audience question about Jesus writing nothing. `post-manuscripts`, `post-human-book` and `post-blindness` may not answer the comparison with the Qur'an and hadith, which came up in a debate. The transmission of hadith and the evidence for the Prophet belong on bestofislam.org; `page-faq` points there.
- Extend the Yusuf Ali scan. The check that found 19 paraphrases looks only at quotations written next to a verse reference, so a quotation with no reference nearby is not covered. One flagged line is an opponent's objection and was left as it is.
- Decide whether the quote cards and the "Three steps, in order" section need the same design pass as the verdict panel. In narrow tablet cards the justified quotation text opens wide word gaps; a left-aligned quote inside a card would tidy it.
- Page-width caps on the admin pages of the sister themes. `injil.help` lost its 1080 px cap in 3.36.1. The same kind of cap remains in `murtadd` (1080 px), `SM` and `compelling-evidence` (1100 px), `paulus` (1040 px), `abrahamic` (960 px), `lailatulqadar` (1024 px) and `TBE` (832 px). `bestofislam` showed none in its admin stylesheet, and `TMA` is private and was not checked.

### Ideas

- A "Last reviewed" line under the dates. The other themes carry one, and `.claude/SEO.md` asks for accurate dates; a date field beside the Key points box would feed it.
- A mid-article next step for long articles, before the third section. The one at the foot shipped in 1.4.7, and the longest articles run past 4,000 words before a reader reaches it.
- A Security tab: application passwords off, REST writes only from the editor, a spam gate and a security log.
- A `/.well-known/security.txt`. Scanners asked for it 28 times in the same logs.
- 410 Gone for addresses that never belonged to the site, as the other themes answer spam paths. The logs show no spam paths here yet, so this waits on evidence.
- More details from the cover of the book the study replies to, taken as ideas and never as artwork: a shallow V at the foot of the hero with fanning gold lines, wide-spaced capitals for author lines, and a cream title block with a gold hairline.
