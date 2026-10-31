const { chromium } = require('playwright');
(async () => { const [url, out, w] = process.argv.slice(2); const b = await chromium.launch();
  const p = await b.newPage({ viewport: { width: +w, height: 1100 } }); await p.goto(url, { waitUntil: 'networkidle' });
  await p.evaluate(() => { const el = document.querySelector('.byline') || document.querySelector('.prose'); if (el) el.scrollIntoView(); window.scrollBy(0, -120); });
  await p.screenshot({ path: out }); await b.close(); })();
