=== Islamic Dilemma ===
Contributors: islamicdilemma
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Version: 1.0.1
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

= 1.0.1 =
* Content: new page "Errors in the case for the dilemma"; hadith footnotes carry collection numbers; tafsir citations corrected after verification.
* URL slugs drop the articles "a", "an" and "the"; tag slugs follow the same rule.
* Sync: every content file is watched for changes, not just the manifest.
* Sync: hand edits to settings (status, slug, date, order, parent, template, comments, categories, tags, featured image) now count as edits and are kept.
* Sync: removing non-theme content on first activation stays on by default, with a setting under Appearance → Theme content to switch it off.
* Images in content are added to the Media Library on import, so WordPress serves responsive sizes; phones get a smaller hero photo.
* Search overlay: closing is safe under rapid repeated input.
