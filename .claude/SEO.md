# Search rules for every change

The owner asked that the rules in Google's *Search Engine Optimization Starter Guide* and its list of structured data that Google Search supports apply to every page, template and article on this site, at all times. This file states them as checks.

## Titles and descriptions
- Every page has its own `<title>`: brief, accurate, specific to that page, with no keyword stuffing.
- The title always follows the format `<page title or keyword or description> | <site title>`, with a vertical bar and the site title last. The page number goes inside the first part, for example `Glossary (Page 2) | Islamic Dilemma Is No Dilemma`. The front page uses the tagline as its description. `islamicdilemma_title_parts()` in `inc/seo/seo.php` enforces this; do not add another title filter.
- Every page has its own meta description, a sentence or two that would inform and interest someone reading it as a search snippet. It must not be a keyword list, a copy of the whole page, or shared with other pages. Excerpts serve as descriptions, so every article and page needs a written excerpt.

## URLs and structure
- URLs are short, lower-case words that describe the content; the owner's slug rule is to drop only the articles "a", "an" and "the". Avoid deep nesting, IDs and parameters.
- Each document has one URL. Use a 301 redirect or `rel="canonical"` for any alternative.
- Hierarchy flows from general to specific: Home → section → article. Breadcrumbs appear on every inner page and match the BreadcrumbList data.
- Navigation uses text links, so crawlers can follow every path.
- There is an HTML sitemap page for people as well as the XML sitemap for crawlers, organised by subject and kept free of broken links.
- The 404 page returns a 404 status, matches the site's design, and links home, to the main sections and to search.

## Content
- Write for readers first: original, useful, well organised, and free of spelling and grammar errors. Never rehash or duplicate content across pages.
- Use headings as an outline: one `h1` per page, then `h2` and `h3` in order, only where they mark structure.
- Never put text inside images, and never hide text from readers that search engines can see.
- Use a mix of the terms beginners and specialists search for: "Gospel" and "Injil", "corruption" and "taḥrīf", and so on.

## Links
- Anchor text is short and describes the target page. Never "click here", "read more", "this page" or a bare URL.
- Links look like links.
- Links from users or from sources we do not vouch for carry `rel="nofollow"` or `rel="ugc"`.

## Images
- Give images short, descriptive file names (`codex-sinaiticus-matthew-6.webp`, not `1.jpg`) and keep them in one directory (`assets/img/`).
- Write short, descriptive alt text for every content image. Never stuff it with keywords.
- Images are AVIF or WebP only (never JPEG, PNG or GIF), converted with `tools/images/convert.py` or saved that way by `tools/images/source.py`, at most 1600 px wide, with a file extension that matches the type. Favicons and app icons in `assets/brand/` stay PNG, ICO and SVG because browsers and the web app manifest require them.
- Record each image's credit and licence in `assets/img/credits.json`, which feeds the image metadata.

## Crawling
- Search result pages are `noindex, follow` and disallowed in robots.txt. robots.txt is never used to hide sensitive material.
- Mobile and desktop visitors, and crawlers, get the same content; there is no cloaking.

## Structured data
The owner's rule is to describe every element and every kind of content on the site with schema.org, wherever a type fits. The graph is built in two places: `inc/seo/seo.php` (WebSite, Organization, Person, WebPage, Article, BreadcrumbList, the primary image) and `inc/seo/schema.php` (everything else). Only describe what is visible on the page.

What each part of the site uses:
- **Site:** WebSite with a SearchAction, the author's Person with a ContactPoint, and the two works the site rests on (the study as ScholarlyArticle, *Paulus* as Book).
- **Pages:** WebPage, with Article or BlogPosting for content, and the specific page types: FAQPage, ContactPage, CollectionPage, SearchResultsPage and ProfilePage. Every page carries WPHeader, WPFooter and SiteNavigationElement parts, a WebPageElement for each section heading, and a BreadcrumbList that matches the visible breadcrumbs.
- **Quotations:** block quotes and hadith are Quotation. Qur'an verse cards are Quotation in Arabic, with the translation as `workTranslation` and a link to the Qur'an as a Book.
- **Citations:** inline references become `mentions` of a verse of the Qur'an or the Bible; footnotes and outside links become `citation`.
- **Tables and lists:** Table, and ItemList for any list of three or more items.
- **Images:** ImageObject with creator, credit text, licence, licence page and copyright notice from `assets/img/credits.json`.
- **Reader tools:** the FAQ is FAQPage with Question and Answer; the glossary is DefinedTermSet with DefinedTerm, one per tag; the reading list is an ItemList of Book, ScholarlyArticle and Thesis; the timeline is an ItemList; the testimonies are Person nodes with portraits and death dates.
- **Archives:** tag pages describe their DefinedTerm; category pages and the posts page are CollectionPage with an ItemList of their posts; search results are SearchResultsPage.
- **Speakable** for the lead, the deck and the summaries.

Rules:
- Use only real schema.org types and properties, with values that fit each property's range. Run `python3 -I tools/verify/schema_check.py http://localhost:8099 <urls-file>` against a running site; it checks every page against the live vocabulary and must report no problems.
- Google shows rich results only for the types on its supported list (Article, BreadcrumbList, ProfilePage, image metadata, speakable, Q&A). The other types add meaning for search engines and AI assistants and do not change how Google displays the page. Google limits FAQ rich results to a few kinds of site, so expect none here.
- Bump `ISLAMICDILEMMA_SCHEMA_VERSION` in `inc/seo/schema.php` when the logic changes, so cached page nodes rebuild.
- Validate with the Rich Results Test and the Schema Markup Validator before launch.
