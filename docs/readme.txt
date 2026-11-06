=== Islamic Dilemma ===
Contributors: islamicdilemma
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.4.11
Version: 1.4.11
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

Some items show an "Updated" date that an image batch set, although nobody changed them. The button "Reset Updated dates set by image batches" puts their published date back. It skips every item you edited and every item whose text differs from the theme, and it lists what it changed. Press it once.

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

= My site is in a subfolder. Where does the browser icon go? =

Browsers ask for /favicon.ico at the top of the domain, such as example.com/favicon.ico, even when the site lives in example.com/blog/. WordPress never sees that request, so put a copy of the theme's favicon.ico in the domain root folder. The icon tags in each page cover the pages either way. If you have set a Site Icon, put that icon there, converted to an ICO file named favicon.ico, instead.

== Upgrade Notice ==

= 1.4.11 =
Upload the whole theme folder. Then, under Appearance, Theme content, press "Reset Updated dates set by image batches" once. On a site in a subfolder, also put a copy of assets/brand/favicon.ico in the domain root folder (or, with a Site Icon set, that icon converted to an ICO file named favicon.ico).

= 1.4.10 =
Nothing to do. The first admin page writes the error pages again with the icon.

= 1.4.9 =
Nothing to do. A theme update now moves the "Updated" line only when it changes the text of an article or page. On the first page load after the update, items whose date came only from a theme update get their published date back.

= 1.4.8 =
Nothing to do. Title tags now stay under 60 characters, and the structured data names the author as the site's publisher.

= 1.4.7 =
Nothing to do. Articles and pages gain a key points box, a reading progress bar, links to related pages in the running text and a next step at the foot. The theme is now under the GNU GPL version 3 or later.

= 1.4.6 =
Documentation only: no new feature or content. As after any update, the error pages are rewritten once.

== Changelog ==

= 1.4.11 =
* A button under Appearance, Theme content puts the published date back on items that show "Updated" only because of an image batch. It leaves edited items alone and lists what it changed.
* The readme says where the browser icon goes on a site in a subfolder: a copy of favicon.ico in the domain root.

= 1.1.0 =
* Author identity: author.json in the theme drives the byline, the author box after every article, the full profile on the About page (#author) and the author structured data. Nothing shows until a name is set.
* Structured data (one JSON-LD graph per page): WebSite, Person, WebPage/AboutPage/ProfilePage/CollectionPage, Article and BlogPosting, BreadcrumbList, ImageObject with credit and licence, and speakable sections. Only types Google Search supports; no Organization.
* Unique meta descriptions, Open Graph and Twitter cards, rel=author, canonical links on archives, noindex on search results, robots.txt rules, and /llms.txt for AI assistants. Stands down when an SEO plugin is active.
* Author archives redirect to the About page; usernames are kept out of the sitemap and feeds.
* New Sitemap page (HTML), linked in the footer. "Read more" links replaced with descriptive anchors.
* Reading text is justified, with hyphenation. An "Updated" date appears when an article changes.
* New articles: the qirāʾāt and the Qur'an's preservation; Q 15:9 and 6:115; Jesus' claims in the Gospels; "Son of God" and Q 5:116; Q 4:157 and the crucifixion; what the New Testament manuscripts can and cannot show. New page: Talking with Christians about the Islamic Dilemma.
* Fonts, all self-hosted WOFF2, unsubset: Sabon Next LT for reading text (Literata for missing transliteration letters); SBL BibLit, Greek and Hebrew for Bible quotations; KFGQPC Hafs Uthmanic Script for Qur'anic Arabic; Noto Naskh Arabic for hadith; Arslan Wessam for other Arabic. Amiri removed.
* Block quotations are styled as Bible quotations only when they quote the Bible; other sources use a new Source quotation style.
* Qur'an quotations are now in the Uthmani script, verified word for word.
* Companion-book support: book.json drives a Book page and per-chapter notes; hidden until a title is set.
* Site title: "Islamic Dilemma Is No Dilemma". In the hero, "false" is set in rubric red.
* Shahada block (islamicdilemma/shahada): the testimony of faith in the Uthmani script, in a navy panel with a double gold frame, star corners and a geometric pattern. Six closing lines to choose from. Placed only on Why Islam, the invitation, the testimonies, sin and repentance, Paul, the Muslim response and "Did Jesus claim to be God?".
* New pages: Christians who became Muslim (eight accounts, with portraits) and More errors in the case for the dilemma. New articles: the letters' deity texts; "a human book full of errors".
* Portraits: a new ::portrait marker; graded 4:5 photos with a gold frame. Every photo now has rounded corners, a gold hairline and a soft shadow.
* Brandmark and icons (assets/brand): the owner's raised fist, with the nail mark in rubric, on a parchment disc inside a gold eight-pointed star on navy. Favicon (SVG, ICO 16–48, PNG 96), Apple touch icon, 192/512 and maskable icons, and a web manifest. A Site Icon set in WordPress takes precedence.
* Custom login screen: brandmark and site title, the basmala in the Uthmani script, a parchment and geometric ground, a rounded card with pill fields and button, and gold and rubric notices.
* The study Reconsidering the "Islamic Dilemma" (2026) is worked into the site within its existing structure.
* Eight new articles cover classical scholarship on taḥrīf, which Bible, the scrolls of Abraham and Moses, the Hebrew Bible before the Masoretic text, the setting of the confirmation verses, the proof-texts, the argument's two standards, and Q 3:84 with its neighbours.
* New sections sit on the Islamic Dilemma page, the Muslim response, Paul, Q 5:48, the manuscripts article and Resources. The authors and platforms the study names are now named.
* Tables in articles (markdown tables in the authoring format). Post dates accept a time ("date: 2026-10-30 00:30").
* Transliteration marks ʿ and ʾ now come from SBL BibLit instead of Literata, so they sit tight in Sabon text.
* Reader tools: a FAQ, ten honest questions for Christians, a side-by-side table of Jesus in the Gospels and the Qur'an, a manuscript timeline, a filterable glossary and reading list, a verse index, a page on how Christians answer, a quick-reference PDF and a contact form. The contact form uses a hidden trap field, a signed timestamp and a rate limit. The glossary and reading list are seed content in content/*.json.
* 404 page: a custom page that returns a real 404 status and noindex, with search, links to every main page and the four latest articles, and a note on reporting a broken link.
* Structured data: schema.org markup now covers every kind of content the theme prints (quotations, verse cards, citations, tables, lists, images, sections, the FAQ, glossary, reading list, timeline, testimonies, contact page, header, footer and navigation, search results and archives). tools/verify/schema_check.py checks every page against the live schema.org vocabulary.
* Writing style: all site text follows docs/anti-ai-writing-style.md. tools/style/lint.py checks the authoring files and the rendered pages.
* Resources: al-Maktabah al-Shāmilah, al-Durar al-Saniyyah, Daily Hadith Online and Aḥmad ʿAlī al-Imām's Variant Readings of the Qur'an.

= 1.4.10 =
* The browser icon set gains a 32px icon, and the icons show in the admin as well as on the site and the login screen.
* The site answers /favicon.ico with less traffic: a browser that already holds the icon gets a short "not changed" answer. The error pages show the icon in the tab.

= 1.4.9 =
* A theme update moves the "Updated" line under the title only when it changes the words: the title, the summary or the text. A picture moved into the Media Library keeps the old date too. Your own saves in the editor move it as before.
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
