# Changelog

Every release of the Islamic Dilemma theme, newest first. The version at the top matches `style.css` and the Stable tag in `docs/readme.txt`, which keeps a short form of the latest entries.

## 1.4.6 (2026-11-05)

Documentation only: no new feature, setting or content. The routines a version change sets off still run once (the error pages are rewritten, and the image scan queues theme images still at their theme address for the image batches).

### Added
- `docs/CHANGELOG.md` (this file), with every release the readme carried until now.
- `docs/SSOT.md`, the fixed facts and rules: identity, the owner decisions word for word, the search rules, the writing rules and the release checklist.
- `docs/UPGRADING.md`: how to update a live site over FTP, a note per version, and a roadmap.
- `.claude/skills/theme-docs/SKILL.md`, the documentation standard for the five base documents, and one line in `.claude/CLAUDE.md` that points to it.

### Changed
- `docs/readme.txt` is written for the site owner: what the theme does, how to install and update it over FTP, each tab of Appearance, Theme content, and a FAQ. Its changelog keeps the last three releases.
- `docs/README.md` gains the boot sequence, the hooks, filters, options and constants, the exact `.htaccess` sections with their Nginx equivalents, the upload guard, multisite behaviour and the full list of checks.

### Fixed
- The readme named Amiri among the fonts. Amiri left in 1.1.0; the credits now list the fonts the theme serves.
- The README left `inc/security/` out of the file map, the Tags setting out of the settings, and `tests/htaccess-files-test.php` and `tests/live-logs-test.php` out of the checks.

## 1.4.5 (2026-11-05)

- The web server now refuses the files that never serve a page, before WordPress runs. A live Apache 2.4 check found wp-settings.php, wp-config-sample.php, readme.html, wp-admin/install.php, PHP in wp-includes and the docs folder all answering 200.
- The theme keeps two sections in the site's .htaccess: "Block xmlrpc.php", a marker other themes share, while XML-RPC is off, and "Islamic Dilemma: block direct file access".
- The second section refuses PHP in wp-includes (the TinyMCE loader and ms-files.php stay open), wp-admin/includes and the themes folder, wp-config.php in the core folder (and one folder up when the .htaccess sits above the core folder), wp-settings.php, wp-config-sample.php, wp-admin/install.php, readme.html, .git and .claude, and the theme's tests, tools and docs folders.
- Both sections are written on an administrator's visit, at most once an hour, and taken out when another theme is activated. On a network they stay, since every site shares one .htaccess.
- Define ISLAMICDILEMMA_BLOCK_DIRECT_FILES as false in wp-config.php to leave the second one out. The rules carry the site's own folder, so a site in a subfolder is covered.
- Without mod_rewrite, an Apache 2.4 <If> on the requested file's path refuses the same files. On a live server with mod_rewrite off, 70 request paths per setup answered as expected.
- The theme folder's .htaccess refuses docs/ and every Markdown file by name, and docs/ carries a deny file of its own. The quick reference PDF in assets/downloads stays open.
- tests/htaccess-files-test.php checks the lines and runs them over the request paths, at the domain root and in a subfolder. On a live Apache 2.4 server, at the root and in /blog/, with and without mod_alias, 71 request paths per setup all answered as expected.

## 1.4.4 (2026-11-05)

- Fixes from the server logs. Old addresses that crawlers still ask for now redirect (301).
- The verse tags without separators, /tag/q-568/, q-546, q-548, q-2946 and q-5727, go to /tag/q-5-68/ and the rest. A dated address such as /2026/10/27/is-the-god-of-islam-distant/ goes to the published post with that slug, or with the slug as the theme now writes it, without "a", "an" or "the".
- Images are no longer copied into the Media Library on init, inside a visitor's request. The content sync writes the posts with the theme's own image addresses and queues them.
- A cron batch then adds at most 3 images, each started within its first 8 seconds (a copy already running cannot be stopped part-way), rewrites the post and renews its checksum, and schedules the next batch while any are left. Each waiting post carries a marker that stays until the post is finished, so a batch cut short leaves it queued.
- A post edited by hand leaves the queue untouched: the batch looks again after its sideloads and leaves a post an editor changed in the meantime, and the content is written only if it is still, byte for byte, what the batch read. An image that already has its copy costs the batch nothing.
- An image whose copy failed is tried again a day later, and each batch takes the waiting posts in a random order, so one post that cannot finish never holds up the rest. A batch that gets nothing done comes back an hour later, not a minute.
- The post's modified date moves, and the usual save hooks fire (through wp_after_insert_post), so caches hear of the change. After an update, a one-time cron scan also queues the theme's own posts that still hold an image at its theme address, which the earlier importer could leave behind.
- A post whose write failed stays queued, a post queued while a batch runs is kept, and a batch that finds the theme half uploaded is rescheduled.
- A theme caught half uploaded answers 503 with Retry-After instead of a fatal error. Every file functions.php loads is checked before any loads, and a half-written one is caught too. functions.php itself is parsed before it runs, so it cannot guard itself.
- The error log gets one line per problem every ten minutes. The login screen (by its path), AJAX, cron and WP-CLI go on.
- Visitors who are not logged in get no /wp/v2/users routes. ?author=N goes straight to the About page, before WordPress could name the account in a redirect.
- XML-RPC is off: xmlrpc.php answers 403, and the pingback header and RSD link go. Define ISLAMICDILEMMA_ALLOW_XMLRPC as true in wp-config.php to keep it.
- The theme folder carries an .htaccess that refuses every dot entry (.git, .claude and the like; .well-known stays open), any PHP file in the theme, and the tests and tools folders. Dot folders are refused without mod_alias and mod_rewrite too, by an Apache 2.4 <If>. Those two folders carry an .htaccess of their own, and their PHP scripts exit outside the command line.

## 1.4.3 (2026-11-05)

- Image alt text gets typographic apostrophes when the article sources are turned into blocks, so the WordPress serializer and tools/build/serialize_py.py write the same markup. Before, only the Python serializer converted them, and three images kept a straight apostrophe. These were the Birmingham manuscript in "None can change His words" and on the Qur'an manuscript timeline, and the Konya manuscript on "The Islamic Dilemma, answered in full".
- tools/build/check_serializer.py passes again for every stored post.

## 1.4.2 (2026-11-05)

- The homepage reads as one story. After the opening argument card come the shape of the dilemma and its missing third option, then the Bible held to the same test, then the three steps to read the case in full, then the latest Enrichment articles.
- Each homepage section opens with a line that leads on from the one before. No section was removed.
- On a network, switching theme leaves the error pages in place, since every site shares wp-content and another site may still run the theme.
- Another theme's error page is replaced only when its "safe to delete" mark sits in the file's header comment.

## 1.4.1 (2026-11-05)

- The working rules for contributors moved into the .claude folder of the repository. Nothing on the site changes, and the installable archive still leaves them out.

## 1.4.0 (2026-11-05)

- Error pages in the theme's design. On activation the theme writes php-error.php (500), db-error.php (503) and maintenance.php (503) into wp-content. Each is a standalone page with its styles inline, so it shows when WordPress cannot run.
- A fatal error from a file missing in wp-includes/, wp-admin/ or the theme, which is what a half finished update leaves, gets a 503 and a page that asks the visitor to reload in a minute or two.
- The files carry the line "Written by the Islamic Dilemma theme; safe to delete". The theme replaces copies another theme wrote and leaves any other file alone. Switching to another theme removes them.
- Appearance > Theme content has a new Error pages tab: the state of each file, an Install error pages button and the .htaccess lines for static pages for 403, 500, 502, 503 and 504. The theme writes those pages into wp-content/islamicdilemma-errors/ with the site's own addresses, and ships copies for a site at the domain root in errors/.
- The publication and update dates in the page header now use the header's light grey, which passes WCAG AA on navy.

## 1.3.0 (2026-11-05)

- Glossary entries now carry an image. A tag takes an optional entry image, chosen from the media library on its edit screen and stored as id_image.
- An entry with no image of its own uses the featured image of the newest article with that tag. Failing that, it takes a photograph shipped with the theme: Qur'an folios for Qur'an entries, codices and Bibles for biblical ones, hadith manuscripts, mosque geometry and scales for theology, and books or scrolls for people and works. The slug picks the photograph, so it stays the same.
- Topic pages open the glossary card with the image as a banner. The glossary page shows a small thumbnail beside each entry, and search and filtering work as before.
- The entry image is the topic page's og:image and its primaryImageOfPage.

## 1.2.1 (2026-11-05)

- Topic archives: the glossary entry now heads the main column, at the width of the post cards, and the sidebar starts level with it. On phones the entry, the posts and the sidebar stack in that order.
- Archive and search excerpts are set flush left with no automatic hyphenation. Cards in a list sit 16px apart, the gap the list sets, instead of 34px.

## 1.2.0 (2026-11-05)

- New article: "Is there a Christian dilemma? A test of method". It sets Hebrews 8:13 against Exodus 31:16–17, takes Matthew 2:23, Marcion and the Pseudo-Clementines, and says plainly that parity is no proof.
- Affirm and reinterpret: a section on Daniel 9 rereading Jeremiah's seventy years and on 1QpHab column 7, and a section on the seal of the prophets (33:40) against later claimants. The dilemma's two parts are now called its two claims.
- Judge by it, uphold it: Q 5:47 read with Q 3:85, and the rebuke of Q 2:85.
- Where is the Injil?: the reports of Waraqa (Bukhārī 3), the Negus and the Torah on the cushion (Abū Dāwūd 4449), and a note that the reply built on 7:156 is weak.
- Classical scholars on taḥrīf: Ibn ʿAbbās in Bukhārī 7363, read beside the majority view.
- FAQ: one answer reworded.
- Structured data: the author Person carries url and sameAs https://menj.bio, and every WebPage, Article and BlogPosting names that Person as author.

## 1.1.0 (2026-11-02)

- Author identity: author.json in the theme drives the byline, the author box after every article, the full profile on the About page (#author) and the author structured data. Nothing shows until a name is set.
- Structured data (one JSON-LD graph per page): WebSite, Person, WebPage/AboutPage/ProfilePage/CollectionPage, Article and BlogPosting, BreadcrumbList, ImageObject with credit and licence, and speakable sections. Only types Google Search supports; no Organization.
- Unique meta descriptions, Open Graph and Twitter cards, rel=author, canonical links on archives, noindex on search results, robots.txt rules, and /llms.txt for AI assistants. Stands down when an SEO plugin is active.
- Author archives redirect to the About page; usernames are kept out of the sitemap and feeds.
- New Sitemap page (HTML), linked in the footer. "Read more" links replaced with descriptive anchors.
- Reading text is justified, with hyphenation. An "Updated" date appears when an article changes.
- New articles: the qirāʾāt and the Qur'an's preservation; Q 15:9 and 6:115; Jesus' claims in the Gospels; "Son of God" and Q 5:116; Q 4:157 and the crucifixion; what the New Testament manuscripts can and cannot show. New page: Talking with Christians about the Islamic Dilemma.
- Fonts, all self-hosted WOFF2, unsubset: Sabon Next LT for reading text (Literata for missing transliteration letters); SBL BibLit, Greek and Hebrew for Bible quotations; KFGQPC Hafs Uthmanic Script for Qur'anic Arabic; Noto Naskh Arabic for hadith; Arslan Wessam for other Arabic. Amiri removed.
- Block quotations are styled as Bible quotations only when they quote the Bible; other sources use a new Source quotation style.
- Qur'an quotations are now in the Uthmani script, verified word for word.
- Companion-book support: book.json drives a Book page and per-chapter notes; hidden until a title is set.
- Site title: "Islamic Dilemma Is No Dilemma". In the hero, "false" is set in rubric red.
- Shahada block (islamicdilemma/shahada): the testimony of faith in the Uthmani script, in a navy panel with a double gold frame, star corners and a geometric pattern. Six closing lines to choose from. Placed only on Why Islam, the invitation, the testimonies, sin and repentance, Paul, the Muslim response and "Did Jesus claim to be God?".
- New pages: Christians who became Muslim (eight accounts, with portraits) and More errors in the case for the dilemma. New articles: the letters' deity texts; "a human book full of errors".
- Portraits: a new ::portrait marker; graded 4:5 photos with a gold frame. Every photo now has rounded corners, a gold hairline and a soft shadow.
- Brandmark and icons (assets/brand): the owner's raised fist, with the nail mark in rubric, on a parchment disc inside a gold eight-pointed star on navy. Favicon (SVG, ICO 16–48, PNG 96), Apple touch icon, 192/512 and maskable icons, and a web manifest. A Site Icon set in WordPress takes precedence.
- Custom login screen: brandmark and site title, the basmala in the Uthmani script, a parchment and geometric ground, a rounded card with pill fields and button, and gold and rubric notices.
- The study Reconsidering the "Islamic Dilemma" (2026) is worked into the site within its existing structure.
- Eight new articles cover classical scholarship on taḥrīf, which Bible, the scrolls of Abraham and Moses, the Hebrew Bible before the Masoretic text, the setting of the confirmation verses, the proof-texts, the argument's two standards, and Q 3:84 with its neighbours.
- New sections sit on the Islamic Dilemma page, the Muslim response, Paul, Q 5:48, the manuscripts article and Resources. The authors and platforms the study names are now named.
- Tables in articles (markdown tables in the authoring format). Post dates accept a time ("date: 2026-10-30 00:30").
- Transliteration marks ʿ and ʾ now come from SBL BibLit instead of Literata, so they sit tight in Sabon text.
- Reader tools: a FAQ, ten honest questions for Christians, a side-by-side table of Jesus in the Gospels and the Qur'an, a manuscript timeline, a filterable glossary and reading list, a verse index, a page on how Christians answer, a quick-reference PDF and a contact form. The contact form uses a hidden trap field, a signed timestamp and a rate limit. The glossary and reading list are seed content in content/*.json.
- Writing style: all site text follows the writing rules in .claude/skills/anti-ai-writing-style/SKILL.md in the repository. tools/style/lint.py checks the authoring files and the rendered pages.
- Resources: al-Maktabah al-Shāmilah, al-Durar al-Saniyyah, Daily Hadith Online and Aḥmad ʿAlī al-Imām's Variant Readings of the Qur'an.

## 1.0.1 (2026-11-02)

- Content: new page "Errors in the case for the dilemma"; hadith footnotes carry collection numbers; tafsir citations corrected after verification.
- URL slugs drop the articles "a", "an" and "the"; tag slugs follow the same rule.
- Sync: every content file is watched for changes, including the manifest.
- Sync: hand edits to settings (status, slug, date, order, parent, template, comments, categories, tags, featured image) now count as edits and are kept.
- Sync: removing non-theme content on first activation stays on by default, with a setting under Appearance → Theme content to switch it off.
- Images in content are added to the Media Library on import, so WordPress serves responsive sizes; phones get a smaller hero photo.
- Search overlay: closing is safe under rapid repeated input.
