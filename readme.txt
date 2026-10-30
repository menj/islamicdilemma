=== Islamic Dilemma ===
Contributors: islamicdilemma
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Version: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Template: twentytwentyfive

Child theme of Twenty Twenty-Five for islamicdilemma.info.

== Description ==

A light-only editorial design for a Muslim reply to the "Islamic Dilemma" argument. Ships its own content as JSON and keeps it in step with each theme version, without overwriting anything edited by hand. See README.md for details.

== Copyright ==

Islamic Dilemma WordPress theme, Copyright 2026 islamicdilemma.info. Distributed under the terms of the GNU GPL v2 or later.

Fonts: Marcellus, Literata, IBM Plex Sans and Amiri, SIL Open Font License 1.1.
Photographs: Pexels License, and public domain images from Wikimedia Commons. See CREDITS.md.

== Changelog ==

= 1.1.0 =
* Author identity: author.json in the theme drives the byline, the author box after every article, the full profile on the About page (#author) and the author structured data. Nothing shows until a name is set.
* Structured data (one JSON-LD graph per page): WebSite, Person, WebPage/AboutPage/ProfilePage/CollectionPage, Article and BlogPosting, BreadcrumbList, ImageObject with credit and licence, and speakable sections. Only types Google Search supports; no Organization.
* Unique meta descriptions, Open Graph and Twitter cards, rel=author, canonical links on archives, noindex on search results, robots.txt rules, and /llms.txt for AI assistants. Stands down when an SEO plugin is active.
* Author archives redirect to the About page; usernames are kept out of the sitemap and feeds.
* New Site map page (HTML), linked in the footer. "Read more" links replaced with descriptive anchors.
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
* Brandmark and icons (assets/brand): a typographic "ID" monogram in Marcellus inside an eight-pointed star, in navy, gold, parchment and rubric. Favicon (SVG, ICO 16–48, PNG 96), Apple touch icon, 192/512 and maskable icons, and a web manifest. A Site Icon set in WordPress takes precedence.
* Resources: al-Maktabah al-Shāmilah, al-Durar al-Saniyyah, Daily Hadith Online and Aḥmad ʿAlī al-Imām's Variant Readings of the Qur'an.

= 1.0.1 =
* Content: new page "Errors in the case for the dilemma"; hadith footnotes carry collection numbers; tafsir citations corrected after verification.
* URL slugs drop the articles "a", "an" and "the"; tag slugs follow the same rule.
* Sync: every content file is watched for changes, not just the manifest.
* Sync: hand edits to settings (status, slug, date, order, parent, template, comments, categories, tags, featured image) now count as edits and are kept.
* Sync: removing non-theme content on first activation stays on by default, with a setting under Appearance → Theme content to switch it off.
* Images in content are added to the Media Library on import, so WordPress serves responsive sizes; phones get a smaller hero photo.
* Search overlay: closing is safe under rapid repeated input.
