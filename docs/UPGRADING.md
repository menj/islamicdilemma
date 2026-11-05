# Upgrading the Islamic Dilemma theme

How to bring a live site up to a new version, what each version asks of you, and where the theme is going next.

## Updating an existing site

1. Turn on hidden files in the FTP program (in FileZilla: Server, Force showing hidden files). The theme carries `.htaccess` files in its own folder and in `tests/`, `tools/` and `docs/`.
2. Upload the new `islamicdilemma` folder into `wp-content/themes/` as `islamicdilemma-new`.
3. Rename the live folder to `islamicdilemma-old` and at once `islamicdilemma-new` to `islamicdilemma`, with Appearance, Themes closed, at a quiet hour. A page asked for between the two renames finds no theme, and that screen switches the site to another theme if it finds the folder missing. Delete the old one once the site looks right.
4. Open any admin page as an administrator. That writes the error pages and the `.htaccess` sections the new version wants.
5. Read the note below for every version you are passing.

You can skip versions. Content syncs on the next page load after the upload, whatever version you came from, and it never overwrites an item edited by hand.

An upload straight into the live folder also works for most files. While one is missing, visitors see a 503 "Back shortly" page, and the login screen keeps working. `functions.php` is the exception, since it holds that guard: if it is cut off part-way, visitors get a PHP error until it is whole.

Never upload WordPress core to a live folder without `wp-config.php` in place. The installer answers anyone in that state.

## Notes by version

### To 1.4.6

Nothing to do. The release adds documents and brings no new feature or content.

The routines every version change sets off still run once. The next admin page rewrites the error pages, which carry the version, and a cron scan a minute after the first page load copies any theme image still at its theme address into the Media Library.

### To 1.4.5

- Upload with hidden files shown, so the new `docs/.htaccess` goes up.
- On Apache, open an admin page: the theme writes "Block xmlrpc.php" and "Islamic Dilemma: block direct file access" into the site's `.htaccess`. Check the site afterwards. To leave the second section out, define `ISLAMICDILEMMA_BLOCK_DIRECT_FILES` as false in `wp-config.php` first.
- For a site in `/blog/` whose `wp-config.php` sits in the web root, the theme cannot refuse that file. Deny it in the web root's own `.htaccess` or the server configuration.
- On Nginx nothing is written. Ask the host to add these lines to the server block, above the `location ~ \.php$` block. Leave out the last line while `ISLAMICDILEMMA_ALLOW_XMLRPC` is true, and put the site's path in front of each rule for a site in a subfolder.

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
- Remove development files from a live site after an update. Other themes the owner runs delete `.git`, `.claude` and `tests` on the first request after a version change. This theme only refuses them by `.htaccess`, which does nothing on Nginx, and the logs from 28 October to 4 November show 188 requests for `/.git/config`.
- Limit login attempts or offer a private login address. The logs from 28 October to 4 November show 2,655 login form posts, and 6 of them came from the owner.

### Later

- Pull request #14 (schema.org markup for all content, a full 404 page, a fuller serializer). It has been open since 1 November. It adds FAQPage and the site search box, which `.claude/SEO.md` rules out, so it waits on the owner's decision and a rebase onto 1.4.x.
- The Book page. Crawlers asked for `/book/` 6 times in the same logs and got a 404, as designed. It opens when the book is announced and `book.json` has a title.
- A ready installer lock. The logs show fatal errors on `wp-login.php` and `xmlrpc.php` on 1 November while WordPress core was half uploaded. The theme cannot run then; a lock rule for `wp-admin/install.php` and `setup-config.php`, shown on the Error pages tab, would cover the worst case.

### Ideas

- A Security tab like the one the owner's other themes carry: application passwords off, REST writes only from the editor, a spam gate and a security log.
- A `/.well-known/security.txt`. Scanners asked for it 28 times in the same logs.
- 410 Gone for addresses that never belonged to the site, as the other themes answer spam paths. The logs show no spam paths here yet, so this waits on evidence.
