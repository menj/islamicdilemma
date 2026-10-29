# Islamic Dilemma — Twenty Twenty-Five child theme

Child theme for islamicdilemma.info. Requires the Twenty Twenty-Five parent theme (bundled with WordPress), WordPress 6.7 or later (tested on 7.1.4) and PHP 7.4 or later.

## Install

1. Make sure Twenty Twenty-Five is installed (Appearance → Themes).
2. Upload `islamicdilemma.zip` at Appearance → Themes → Add New → Upload Theme.
3. Activate it.

## What happens on activation

The site's content ships with the theme as JSON files in `content/`. On the **first** activation the theme:

- imports every page and Enrichment article, with categories and tags;
- moves every other post and page on the site to the **Trash** (not permanently deleted; restore from Posts → Trash or Pages → Trash within 30 days);
- sets Home as the front page, Enrichment as the posts page, pretty permalinks if they are off, 10 posts per page, and the site title and tagline.

A notice in the dashboard reports exactly what was added and what was moved to the Trash.

Moving other content to the Trash is **on by default**. To keep existing posts and pages, switch it off before activating, either with `wp option update islamicdilemma_remove_other_content 0` or with the filter `add_filter( 'islamicdilemma_remove_other_content', '__return_false' );`. The setting also appears under Appearance → Theme content.

Images in the theme content are copied into the **Media Library** on import, so WordPress serves them at responsive sizes (`srcset`).

## Theme updates

When a new theme version contains new or changed content, it syncs automatically on the next page load:

- new pages and articles are added;
- theme items you have **not** edited are updated to the new version;
- theme items removed from the theme are moved to the Trash, unless you edited them.

**Your changes are protected.** A theme item you have edited by hand, whether its text or its settings (status, slug, date, order, parent, template, comments, categories, tags or featured image), is never overwritten or removed, and one you deleted is never recreated. Articles you write yourself are never touched by the theme after the first activation.

To deliberately replace your edits with the theme's version, go to **Appearance → Theme content**, tick the items and choose "Restore the theme version of the selected items". With WP-CLI:

```
wp islamicdilemma content status
wp islamicdilemma content sync
wp islamicdilemma content restore post:injil-where-is-it page:about
```

## Design

Light only. The parent theme's style variations (including its dark ones) are hidden from the Site Editor. Colours: midnight navy `#18283C`, antique gold `#B18B54`, parchment `#F7F4ED`, defined as theme.json presets. Fonts (Marcellus, Literata, IBM Plex Sans, Amiri) are served from the theme, not from Google. No green, no Tailwind, no build step.

## What's in the theme

| Path | Contents |
|---|---|
| `theme.json` | Palette, fonts, sizes, layout widths, element styles |
| `templates/` | front-page, page, single, home (Enrichment), category, tag, archive, index, search, 404 |
| `parts/` | header (with the search overlay) and footer |
| `patterns/` | Homepage sections, Arabic verse card, premise card, article with sidebar |
| `blocks/` | Search overlay, reading time and result-type blocks |
| `inc/` | Light-only enforcement, block styles, render filters, shortcodes, content sync and admin |
| `content/` | Pages, posts, categories and settings as JSON |
| `assets/` | Stylesheet, scripts, fonts and images |

## Block styles for editors

- Details: Verdict (Granted, Equivocates, Does not follow, False, Unsupported), for premise cards
- Paragraph: Arabic verse, Lead, Eyebrow label, Verse reference
- Group: Verse card, Gold callout, Sidebar box, Card
- Quote: Scripture quotation
- List: Footnotes
- Button: Ghost on navy
- Text toolbar: "Qur'an reference" marks a reference such as Q 5:47 in gold

## Images

Images are stored in the theme and credited in `CREDITS.md`. For production, consider moving them into the Media Library so editors can manage them. No API keys are stored in the theme.
