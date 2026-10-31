const { chromium } = require('playwright');
(async () => { const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 390, height: 800 } });
  await p.goto('http://localhost:8099/');
  const st = () => p.evaluate(() => [document.getElementById('islamicdilemma-search').open, document.documentElement.classList.contains('search-open'), document.activeElement.className]);
  const out = [];
  for (let i = 0; i < 5; i++) { await p.click('.search-toggle'); await p.keyboard.press('Escape'); await p.waitForTimeout(330); await p.click('.search-toggle'); await p.waitForTimeout(350); out.push(await st()); await p.keyboard.press('Escape'); await p.waitForTimeout(350); }
  const sizes = await p.evaluate(() => performance.getEntriesByType('resource').map(r => [r.name.split('/').pop(), Math.round(r.transferSize / 1024)]).sort((a, b) => b[1] - a[1]).slice(0, 6));
  console.log(JSON.stringify(out), JSON.stringify(sizes)); await b.close(); })();
