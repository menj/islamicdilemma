=== Islamic Dilemma ===
Contributors: islamicdilemma
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.4.6
Version: 1.4.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
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
2. Upload the islamicdilemma folder into wp-content/themes under a temporary name, such as islamicdilemma-new.
3. When every file is up, rename the old folder (if there is one) to islamicdilemma-old, then at once rename islamicdilemma-new to islamicdilemma. Do the two renames one straight after the other, with Appearance, Themes closed: if WordPress opens that screen while the folder is missing, it switches the site to another theme. Delete islamicdilemma-old once the site looks right.
4. Activate the theme under Appearance, Themes if it is not active yet.

The folder must be called islamicdilemma, all lower case.

Uploading into the live folder file by file also works for most files. While one of them is missing or half written, visitors see a short "Back shortly" page instead of an error.

The one file that page cannot cover is functions.php, since it is the file that shows it. If that file is cut off part-way, visitors get an error until it is whole. The temporary folder and the rename avoid both.

Never upload WordPress itself (wp-admin, wp-includes and the files beside them) to a live folder that has no wp-config.php. In that state the WordPress installer answers anyone who finds it. Put wp-config.php in place first, or upload WordPress to a temporary folder and rename it, as above.

== What happens on the first activation ==

The theme adds every page and article it carries, with their categories and tags. It sets Home as the front page and Enrichment as the page for articles.

It also moves every other post and page on the site to the Trash. Nothing is deleted: you can restore any of them from the Trash for as long as your site keeps it, 30 days unless your host or wp-config.php sets another time. To keep them where they are, switch this off before you activate (Appearance, Theme content, Settings).

A notice on the dashboard says what was added and what went to the Trash. Images are copied into the Media Library a few at a time in the background, so the first visits stay fast.

== Updating ==

Upload the new version in the same way as above. You need not press anything afterwards.

On the next page load the theme compares its articles with the site. New articles are added and unedited ones take the new text. An article the theme no longer carries goes to the Trash, unless you edited it.

Anything you changed by hand stays as you left it. That covers the text and the settings of an article: its address, date, status, categories, tags and featured image.

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

= Will an update overwrite what I wrote? =

No. An article you edited keeps your text, and one you deleted is never put back. Articles you write yourself keep their text; only the Tags setting reaches them, by deleting tags the theme does not carry.

= Where did my old posts go? =

The first activation moved them to the Trash. Restore them from Posts, Trash or Pages, Trash, usually within 30 days. If the Trash is switched off on your site, the theme moves nothing.

= I see "Back shortly" on the site. =

The theme is incomplete on the server, usually because an upload is still running or stopped part-way. Upload the missing files, or the whole folder again. The login screen keeps working while this shows.

= The Book page says "not found". =

It stays hidden until the book's title is filled in, in book.json in the theme folder. Nothing else changes until then.

= I use an SEO plugin. =

With Yoast, Rank Math, AIOSEO, SEOPress or The SEO Framework active, the theme leaves the search tags and structured data to the plugin.

= My host runs Nginx. =

The theme writes its .htaccess rules only on Apache. On Nginx, give your host the rules in UPGRADING.md (in the theme's docs folder, under "To 1.4.5") to add to the server settings.

== Upgrade Notice ==

= 1.4.6 =
Documentation only. Nothing on the site changes.

= 1.4.5 =
Upload with hidden files shown, so the new .htaccess files go up. The web server then refuses the theme's development files and WordPress's internal files.

== Changelog ==

= 1.4.6 =
* Documentation only. A changelog, a single source of truth and upgrade notes join the readme and the README in the docs folder. The readme now speaks to the site owner, and the fonts it lists are the ones the theme serves.

= 1.4.5 =
* The web server refuses the files that never serve a page before WordPress runs. Among them are WordPress's internal files and its installer, the theme's development folders and the .git and .claude folders.

= 1.4.4 =
* Old addresses that crawlers still ask for now redirect, and images are copied into the Media Library in small background batches.
* A theme caught half uploaded shows "Back shortly" instead of an error.
* XML-RPC is off, and visitors can no longer list the site's user accounts.

The full history is in CHANGELOG.md in the theme's docs folder.

== Copyright ==

Islamic Dilemma WordPress theme, Copyright 2026 islamicdilemma.info. Distributed under the terms of the GNU GPL v2 or later.

Fonts:
* Marcellus, Literata, IBM Plex Sans and Noto Naskh Arabic, under the SIL Open Font License 1.1.
* KFGQPC HAFS Uthmanic Script, under the licence of the King Fahd Glorious Qur'an Printing Complex.
* Arslan Wessam, a free font by Arslan (Dev-Point).
* Sabon Next LT, under the site owner's licence from Monotype.
* SBL BibLit, SBL Greek and SBL Hebrew, under the licence of the Society of Biblical Literature.

Photographs: Pexels License, public domain images from Wikimedia Commons, and portraits and the author photo supplied by the site owner. Every image is listed with its source in CREDITS.md.
