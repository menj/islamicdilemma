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

### Later

- Pull request #14 (schema.org markup for all content, a full 404 page, a fuller serializer). It has been open since 1 November. It adds FAQPage and the site search box, which `.claude/SEO.md` rules out, so it waits on the owner's decision and a rebase onto 1.4.x.
- The Book page. Crawlers asked for `/book/` 6 times in the same logs and got a 404, as designed. It opens when the book is announced and `book.json` has a title.
- A ready installer lock. The logs show fatal errors on `wp-login.php` and `xmlrpc.php` on 1 November while WordPress core was half uploaded. The theme cannot run then; a lock rule for `wp-admin/install.php` and `setup-config.php`, shown on the Error pages tab, would cover the worst case.

### Ideas

- A "Last reviewed" line under the dates. The other themes carry one, and `.claude/SEO.md` asks for accurate dates; a date field beside the Key points box would feed it.
- A mid-article next step for long articles, before the third section. The one at the foot shipped in 1.4.7, and the longest articles run past 4,000 words before a reader reaches it.
- A Security tab: application passwords off, REST writes only from the editor, a spam gate and a security log.
- A `/.well-known/security.txt`. Scanners asked for it 28 times in the same logs.
- 410 Gone for addresses that never belonged to the site, as the other themes answer spam paths. The logs show no spam paths here yet, so this waits on evidence.
