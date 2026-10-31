# Final candidate for owner review

Status: final HTML candidate for the owner's review and approval. Not a WordPress theme, not production-ready, not deployed.

The visual direction is agreed by Claude and ChatGPT. This package is ChatGPT's light-only revision of the merged mockup (`islamicdilemma-final-light-html-claude.zip`) with the two agreed fixes:

1. Phone menu links are at least 44px tall (previously 31px).
2. Unused theme-switch CSS (`.head-tools`, `.pill-btn`) removed.

Owner edits: wording in the footer, the About page, the argument card and the Islamic Dilemma page was revised at the owner's request.

Nothing else changed: design, content, typography, layouts and light-only appearance are as agreed.

## Final regression check (all 9 pages)

Checked at 1440px, 768px, 390px and 320px wide. The 390px pass ran with the phone set to dark mode.

- No horizontal scrolling at any width
- No script errors; all images load and have alt text
- All internal links and on-page anchors resolve
- Mobile menu opens each of the six sections and highlights the current one
- Keyboard: every link, button and expandable premise shows a visible focus outline
- Light appearance holds when the device is in dark mode; no dark-mode CSS remains
- No green rendered anywhere; owner wording rules applied

Result: all checks passed.

## Still separate from design approval

- Editorial and theological verification of quotations, translations, footnotes and historical claims
- Image licence confirmation before publication
- WordPress Twenty Twenty-Five child theme conversion, only once the owner authorises it
