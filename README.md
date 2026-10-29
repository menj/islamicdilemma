# IslamicDilemma.info — final light-only HTML review package

Open `site/index.html` in your browser. CSS and images are included under `site/assets/`. The site is **not deployed**, and it is **not yet a WordPress theme**.

Pages: `index.html` (home), `islamic-dilemma.html`, `muslim-response.html` (evergreen pages), `enrichment.html` (blog index), `enrichment-5-48.html` (single post), `category-quranic-studies.html` (category), `tag-tahrif.html` (tag), `resources.html`, `about.html`.

Visual system: fixed light appearance, navy `#18283C`, gold `#B18B54`, parchment `#F7F4ED`; rounded cards, pill buttons, gentle CSS rollovers, reduced-motion support. No Tailwind.

Read `CLAUDE-REVIEW.md` for peer assessment and outstanding checks, and `site/CREDITS.md` for image sourcing.

No production use or implied factual verification. Never embed the Pexels API key into public code.

## WordPress theme (1.0.1)

| Path | What it is |
|---|---|
| `wordpress-theme/islamicdilemma/` | Twenty Twenty-Five child theme. Content ships as JSON in `content/` and syncs on activation; see its `README.md`. |
| `build/` | Authoring pipeline. Articles are plain text in `build/content/*.txt`; the scripts turn them into block markup, using WordPress's own serializer, and then into the theme's JSON. |
| `tools/` | Playwright checks: block-editor validity, the front-end regression at four widths, the search overlay and keyboard focus. Each expects a local WordPress at `localhost:8099`. |
| `docs/theme/` | Review notes, the verification report, claim coverage and screenshots. |

To rebuild the content after editing an article, run this from `build/` with a local WordPress running:

```bash
python3 build_content.py trees content-trees && node serialize.js content-trees content-markup && python3 build_content.py json content-markup ../wordpress-theme/islamicdilemma
```

Nothing in this repository is deployed. The theme has not been installed on islamicdilemma.info.
