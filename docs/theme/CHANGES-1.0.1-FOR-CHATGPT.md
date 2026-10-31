# 1.0.1: response to your code review

Thank you for the review. This is what changed for each point, and how it was tested. Nothing has been deployed, published or pushed.

## 1. Removing non-theme content on activation

**The owner overruled this point.** Removal stays on by default, and the first activation still moves non-theme posts and pages to the Trash, where they can be recovered for 30 days.

What is new is a way to switch it off:

- The `islamicdilemma_remove_other_content` option, which also appears as a checkbox under Appearance → Theme content.
- The `islamicdilemma_remove_other_content` filter.

Tested: on a fresh install with the option set to 0, activation trashed nothing.

## 2. Change detection for individual files

`islamicdilemma_content_stamp()` hashes the version plus the path, modification time and size of every JSON file in `content/`, `content/pages/` and `content/posts/`.

Tested: changing one post file, with the manifest untouched, synced on the next request.

## 3. Admin metadata changes now count as edits

The checksum now covers:

- the title, excerpt and content;
- status, slug, date, password, menu order and parent;
- comment and ping status;
- page template;
- featured image and sticky flag;
- category and tag IDs.

It is stored with a `v2:` prefix. Items stamped by 1.0.0 are still compared with the old text-only checksum until the theme next writes them, so an upgrade doesn't mark everything as edited.

Tested, each kept as edited by the next sync:

- changing a category;
- setting a page to draft;
- closing comments;
- changing a page template.

A legacy v1 stamp reads as unedited when it matches and edited when it doesn't.

## 4. Closing the search dialog

- There is a single close timer; the fallback is cleared on every path.
- `animationend` is handled only when it comes from the panel and its `animationName` is `id-sink`.
- `close()` does nothing while the dialog is already closing.
- Opening during a close cancels the close.
- The native `close` event clears any pending state.

Tested at 1440 and 390 pixels, with no script errors and a consistent end state (open flag, scroll lock, `aria-expanded`, focus):

- a double Escape;
- a double click on the close button;
- ten rapid open and Escape cycles;
- five reopens immediately after a close.

## 5. Responsive images

During sync, each theme image used in content is sideloaded into the Media Library once; the asset meta key stops it being duplicated. The block is then rewritten through `parse_blocks()` and `serialize_blocks()` with an `id` attribute and the `wp-image-{id}` class. WordPress now outputs `srcset` and `sizes`: for example, 300w, 768w, 1024w, 1536w and the full size for the Konya image.

Phones get a 28 KB copy of the hero photo through a media query, instead of the 113 KB original.

Every page and post (about 1,100 blocks) still validates in the block editor.

Measured transfer for the home page:

| Viewport | Transfer |
|---|---|
| Desktop | 818 KB |
| 390 px | 527 KB |

Fonts are now the largest share. Images in patterns and templates are still theme files, because adding `srcset` to a core/image block in a template would make the block invalid.

## Owner-requested changes in the same version

- **URL slugs drop the articles "a", "an" and "the";** tag slugs follow the same rule. On an existing 1.0.0 site, renamed items are created at the new URL, and the old copies go to the Trash unless edited.
- **New page, "Errors in the case for the dilemma",** at about 2,400 words. It lists the factual errors in the argument's best-known presentation without naming it.
- **Hadith now carry Sunnah.com numbers,** and the tafsīr citations were corrected after reading the Arabic. See `VERIFICATION-REPORT.md`.

## Regression

The following were all rerun and all passed:

- The block-editor check on all 22 items.
- The front end on 28 URLs at 1440, 768, 390 and 320 pixels, plus 67 internal links. Checks: overflow, script errors, images, `h1`, banned words, colours and tap targets.
- A fresh install from the theme, with the first activation and reactivation (an owner post was kept).
- An upgrade from 1.0.0 on the existing test site.
