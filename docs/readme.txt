=== Islamic Dilemma ===
Contributors: islamicdilemma
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.4.9
Version: 1.4.9
License: GNU General Public License v3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Template: twentytwentyfive

Child theme of Twenty Twenty-Five for islamicdilemma.info.

== Description ==

This theme runs islamicdilemma.info, a Muslim reply to the "Islamic Dilemma" argument. It carries the whole site with it: the pages, the Enrichment articles, the glossary, the reading list and the reader tools.

You install it, and it fills the site. When a new version brings new or changed articles, the site takes them on the next page load. An article you have edited by hand is never overwritten.

The design is light only, in midnight navy, antique gold and parchment. Every font ships inside the theme, so nothing is fetched from Google or any other server.

== Installation ==

Twenty Twenty-Five must be installed first (Appearance, Themes). It comes with WordPress and need not be active.

With the WordPress uploader:

1. Go to Appearance, Themes, Add New, Upload Theme.
2. Choose islamicdilemma.zip and press Install Now.
3. Press Activate.

With FTP:

1. Turn on hidden files in your FTP program first. In FileZilla it is Server, Force showing hidden files. The theme has files whose names start with a dot (.htaccess) and they must go up too.
2. Upload the islamicdilemma folder into wp-content/themes under a temporary name, such as islamicdilemma-new. Send its .htaccess files first: the one at the top of the folder, then those in docs, tests and tools. On Apache their rules then apply to each file as it arrives, so the files they refuse on the live site are refused in the new folder too. Styles, scripts, fonts and images stay public, as they are on the live site. Nginx reads no .htaccess; there the server block rules do this work.
3. When every file is up, rename the old folder (if there is one) to islamicdilemma-old, then at once rename islamicdilemma-new to islamicdilemma. Do the two renames one straight after the other, at a quiet hour, with Appearance, Themes closed. A page asked for in the moment between them finds no theme and shows an error, and if WordPress opens that screen while the folder is missing, it switches the site to another theme. Delete islamicdilemma-old once the site looks right.
4. Activate the theme under Appearance, Themes if it is not active yet.

The folder must be called islamicdilemma, all lower case.

Uploading into the live folder file by file is riskier. While one of the theme's PHP files is missing or half written, visitors see a short "Back shortly" page instead of an error.

That page covers only those PHP files. functions.php itself, the templates, the stylesheets, the images and the content files are not checked, and while one of them is missing or cut off a page can look broken or show an error. The temporary folder and the rename avoid all of it.

Never upload WordPress itself (wp-admin, wp-includes and the files beside them) to a live folder that has no wp-config.php. In that state the WordPress installer answers anyone who finds it. Put wp-config.php in place first, or upload WordPress to a folder the web server does not serve (one outside public_html), then move it into place, as above.

== What happens on the first activation ==

The theme adds every page and article it carries, with their categories and tags. It sets Home as the front page and Enrichment as the page for articles.

It also changes a few site settings: the site title and tagline, 10 posts per page, and readable addresses (pretty permalinks) if they are off.

Then it moves every other post and page on the site to the Trash. Nothing is deleted: you can restore any of them from the Trash for as long as your site keeps it, 30 days unless your host or wp-config.php sets another time. To keep them where they are, switch this off before you activate.

The Theme content screen only appears once the theme is active, so ask your host to switch both settings off first. UPGRADING.md, under "Before the first activation", has the two lines your host needs; the second one also keeps your own tags.

After activation the same switches are under Appearance, Theme content, Settings.

A notice on the dashboard says what was added and what went to the Trash. Images are copied into the Media Library a few at a time in the background, so the first visits stay fast.

== Updating ==

Upload the new version in the same way as above. You need not press anything afterwards.

On the next page load the theme compares its articles with the site. New articles are added and unedited ones take the new text. An article the theme no longer carries goes to the Trash, unless you edited it.

Anything you changed by hand stays as you left it. That covers the text and the settings of an article: its address, date, status, categories, tags and featured image.

Tags are the one exception. While the Tags setting is on, as it is until you switch it off, every sync deletes each tag the theme does not carry, from your own posts too. That means each update, and each press of Sync now.

UPGRADING.md in the theme's docs folder has a note for each version and says what is planned next.

== Settings ==

Everything is under Appearance, Theme content. The screen has three tabs.

= Content =

A list of every page and article the theme carries, with its state. An item matches the theme, has a newer version waiting, or is not imported yet. Or you edited it, or deleted it, and the theme leaves it alone.

Sync now brings the site up to date at once. To throw away your edits to an item and take the theme's version, tick it and press "Restore the theme version of the selected items".

= Settings =

First activation: whether the first activation moves the site's other posts and pages to the Trash. It is on until you switch it off.

Tags: whether each update deletes tags that are not part of the theme. A deleted tag is gone for good, and every post loses it, your own posts too. It is on until you switch it off, so switch it off before an update if you want to keep tags of your own.

= Error pages =

When WordPress cannot run (during an update, after a PHP fault, or with the database down) it shows three pages from wp-content. The theme writes them in its own design. This tab shows each page's state and has an Install error pages button.

The tab also gives the lines to add to the site's .htaccess for the errors the web server answers itself. Copy them from the tab, since they carry your site's real addresses.

== Frequently Asked Questions ==

= How do I add key points to an article? =

Open the article or page and find the Key points box beside the editor. Type one point a line, up to six, and update. They show in a box above the text. Leave the box empty and no box shows; the short description under the title already sums up the article.

= Can I add questions and answers to an article? =

Yes. Insert the "Questions readers ask" pattern, or give a Details block the FAQ style. The questions show on the page as they are. The theme adds no FAQ markup for search engines: the site's search rules leave that type out.

= Will an update overwrite what I wrote? =

No. An article you edited keeps your text, and one you deleted is never put back. Articles you write yourself keep their text; only the Tags setting reaches them, by deleting tags the theme does not carry.

= Where did my old posts go? =

The first activation moved them to the Trash. Restore them from Posts, Trash or Pages, Trash, usually within 30 days. If the Trash is switched off on your site, the theme moves nothing.

= I see "Back shortly" on the site. =

The theme is incomplete on the server, usually because an upload is still running or stopped part-way. Upload the missing files, or the whole folder again. The login screen keeps working while this shows, unless the file missing or broken is functions.php itself.

= The Book page says "not found". =

It stays hidden until the book's title is filled in, in book.json in the theme folder. Nothing else changes until then.

= I use an SEO plugin. =

With Yoast, Rank Math, AIOSEO, SEOPress or The SEO Framework active, the theme leaves the search tags and structured data to the plugin.

= My host runs Nginx. =

The theme writes its .htaccess rules only on Apache. On Nginx, give your host the rules in UPGRADING.md (in the theme's docs folder, under "To 1.4.5") to add to the server settings.

== Upgrade Notice ==

= 1.4.9 =
Nothing to do. The "Updated" line now shows only when the text of an article or page changed. On the first page load after the update, items whose date came only from a theme update get their published date back.

= 1.4.8 =
Nothing to do. Title tags now stay under 60 characters, and the structured data names the author as the site's publisher.

= 1.4.7 =
Nothing to do. Articles and pages gain a key points box, a reading progress bar, links to related pages in the running text and a next step at the foot. The theme is now under the GNU GPL version 3 or later.

= 1.4.6 =
Documentation only: no new feature or content. As after any update, the error pages are rewritten once.

== Changelog ==

= 1.4.9 =
* The "Updated" line under the title shows only after a real change to the words: the title, the summary or the text. A theme update that leaves them as they were, or a picture moved into the Media Library, keeps the old date.
* A one-time repair gives the published date back to items that carried the day of the last theme update. Anything you edited yourself keeps its date.

= 1.4.8 =
* Every title tag stays under 60 characters. Long titles take a short form in the browser tab and in search results only; the headings on the pages are unchanged.
* The structured data names the author, not an organisation, as the site's publisher.

= 1.4.7 =
* The "Updated" date shows the date of the last change, and only when it falls on a later day than the publication. Until now it repeated the publication date.
* A Key points box on the edit screen fills a box above the article. A next step after the author box offers the next article, the notes and the Contact page.
* A thin gold bar at the top fills as you read. The first mention of a subject another page treats links to that page.
* Pages show their reading time, which now counts transliterated words such as taḥrīf once. Wide tables scroll from the keyboard on a phone, and Details blocks take a new FAQ style.
* The theme is now under the GNU GPL version 3 or later, with the full text in LICENSE.

= 1.4.6 =
* Documentation only. A changelog, a single source of truth and upgrade notes join the readme and the README in the docs folder. The readme now speaks to the site owner, and the fonts it lists are the ones the theme serves.

The full history is in CHANGELOG.md in the theme's docs folder.

== Copyright ==

Islamic Dilemma WordPress theme, Copyright 2026 islamicdilemma.info. Distributed under the terms of the GNU GPL version 3 or later. The full text is in the LICENSE file.

Fonts:
* Marcellus, Literata, IBM Plex Sans and Noto Naskh Arabic, under the SIL Open Font License 1.1.
* KFGQPC HAFS Uthmanic Script, under the licence of the King Fahd Glorious Qur'an Printing Complex.
* Arslan Wessam, a free font by Arslan (Dev-Point).
* Sabon Next LT, under the site owner's licence from Monotype.
* SBL BibLit, SBL Greek and SBL Hebrew, under the licence of the Society of Biblical Literature.

Photographs: Pexels License, public domain images from Wikimedia Commons, and portraits and the author photo supplied by the site owner. Every image is listed with its source in CREDITS.md.
