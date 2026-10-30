# Search rules for every change

The owner asked that the rules in Google's *Search Engine Optimization Starter Guide* and its list of structured data that Google Search supports apply to every page, template and article on this site, at all times. This file states them as checks.

## Titles and descriptions
- Every page has its own `<title>`: brief, accurate, specific to that page, with no keyword stuffing.
- Every page has its own meta description, a sentence or two that would inform and interest someone reading it as a search snippet. It must not be a keyword list, a copy of the whole page, or shared with other pages. Excerpts serve as descriptions, so every article and page needs a written excerpt.

## URLs and structure
- URLs are short, lower-case words that describe the content; the owner's slug rule is to drop only the articles "a", "an" and "the". Avoid deep nesting, IDs and parameters.
- Each document has one URL. Use a 301 redirect or `rel="canonical"` for any alternative.
- Hierarchy flows from general to specific: Home → section → article. Breadcrumbs appear on every inner page and match the BreadcrumbList data.
- Navigation is in text links, not images or scripts alone.
- There is an HTML site map page for people as well as the XML sitemap for crawlers, organised by subject and kept free of broken links.
- The 404 page returns a 404 status, matches the site's design, and links home, to the main sections and to search.

## Content
- Write for readers first: original, useful, well organised, and free of spelling and grammar errors. Don't rehash or duplicate content across pages.
- Use headings as an outline: one `h1` per page, then `h2` and `h3` in order, only where they mark structure.
- Never put text inside images, and never hide text from readers that search engines can see.
- Use a mix of the terms beginners and specialists search for: "Gospel" and "Injil", "corruption" and "taḥrīf", and so on.

## Links
- Anchor text is short and describes the target page. Never "click here", "read more", "this page" or a bare URL.
- Links look like links.
- Links from users or from sources we do not vouch for carry `rel="nofollow"` or `rel="ugc"`.

## Images
- Give images short, descriptive file names (`codex-sinaiticus-matthew-6.jpg`, not `1.jpg`) and keep them in one directory (`assets/img/`).
- Write short, descriptive alt text for every content image. Don't stuff it with keywords.
- Use common formats, with a file extension that matches the type.
- Record each image's credit and licence in `assets/img/credits.json`, which feeds the image metadata.

## Crawling
- Search result pages are `noindex, follow` and disallowed in robots.txt. robots.txt is never used to hide sensitive material.
- Mobile and desktop visitors, and crawlers, get the same content; there is no cloaking.

## Structured data
Use only types on Google's supported list, and only to describe what is visible on the page:
- **Article / BlogPosting** for articles, with author, dates, image and headline.
- **BreadcrumbList**, matching the visible breadcrumbs.
- **ProfilePage** for the About page, with the author as the Person and main entity.
- **Image metadata**: creator, credit text, licence and licence page.
- **Speakable** for the lead, the deck and the "In short" summaries.
- **Q&A** only for a page that is one question followed by its answers.
- **Video**, if videos are added.

Also allowed:
- **WebSite**, for the site name.

Not allowed:
- **Organization:** the site names no organisation.
- **FAQPage** and the site search box: not on the supported list.

Validate changes with the Rich Results Test before launch.
