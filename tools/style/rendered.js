// Dump the visible prose of every page to text files so lint.py can check the theme's own strings.
// Usage: node tools/style/rendered.js <urls.txt> <outdir>   (needs the local site on http://localhost:8099)
const { chromium } = require('playwright'); const fs = require('fs'); const path = require('path');
(async () => {
  const [urlFile, out] = process.argv.slice(2); fs.mkdirSync(out, { recursive: true });
  const urls = fs.readFileSync(urlFile, 'utf8').trim().split('\n');
  const b = await chromium.launch(); const p = await b.newPage();
  for (const u of urls) {
    await p.goto('http://localhost:8099' + u, { waitUntil: 'domcontentloaded' });
    const text = await p.evaluate(() => {
      const skip = 'script,style,noscript,blockquote,cite,.is-style-ayah,.is-style-arabic,.is-style-quran,.is-style-hadith,.ar,[lang="ar"],.wp-block-footnotes,.screen-reader-text,.ref,.shahada-arabic,.site-map-note';
      const clone = document.body.cloneNode(true); clone.querySelectorAll(skip).forEach(e => e.remove());
      return [...clone.querySelectorAll('h1,h2,h3,p,li,td,th,summary,figcaption,label,button,a')].filter(e => !e.closest('a') || e.tagName === 'A')
        .map(e => e.innerText.trim()).filter(Boolean).join('\n');
    });
    fs.writeFileSync(path.join(out, (u.replace(/\//g, '_') || '_home') + '.txt'), text);
  }
  await b.close();
})();
