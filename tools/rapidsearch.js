const { chromium } = require('playwright');
(async () => {
  const b = await chromium.launch(); const results = [];
  for (const w of [1440, 390]) {
    const ctx = await b.newContext({ viewport: { width: w, height: 800 } }); const p = await ctx.newPage();
    const errs = []; p.on('pageerror', e => errs.push(e.message));
    let bytes = 0; p.on('response', async r => { try { const h = await r.headerValue('content-length'); bytes += h ? +h : (await r.body()).length; } catch (e) {} });
    await p.goto('http://localhost:8099/', { waitUntil: 'networkidle' });
    const state = () => p.evaluate(() => ({ open: document.getElementById('islamicdilemma-search').open, closing: document.getElementById('islamicdilemma-search').classList.contains('is-closing'), lock: document.documentElement.classList.contains('search-open'), exp: document.querySelector('.search-toggle').getAttribute('aria-expanded'), focus: document.activeElement.className }));
    const t = '.search-toggle';
    // 1: open then close immediately, then reopen during the exit animation
    await p.click(t); await p.keyboard.press('Escape'); await p.waitForTimeout(60); await p.click(t, { force: true });
    await p.waitForTimeout(500); const s1 = await state();
    // 2: double Escape, rapid close button clicks
    await p.keyboard.press('Escape'); await p.keyboard.press('Escape'); await p.waitForTimeout(500); const s2 = await state();
    // 3: 10 rapid toggles
    for (let i = 0; i < 10; i++) { await p.click(t, { force: true }); await p.keyboard.press('Escape'); }
    await p.waitForTimeout(600); const s3 = await state();
    await p.click(t); await p.waitForTimeout(400); await p.click('.search-close'); await p.click('.search-close', { force: true }).catch(() => {}); await p.waitForTimeout(500); const s4 = await state();
    results.push({ w, reopenMidClose: s1, doubleEscape: s2, rapid10: s3, doubleCloseClick: s4, homeKB: Math.round(bytes / 1024), errs });
    await ctx.close();
  }
  console.log(JSON.stringify(results, null, 1)); await b.close();
})();
