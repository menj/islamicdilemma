const { chromium } = require('playwright');
(async () => {
  const b = await chromium.launch();
  // Reduced motion: overlay opens and closes without animations
  const rm = await b.newPage({ viewport: { width: 1280, height: 800 }, reducedMotion: 'reduce' });
  await rm.goto('http://localhost:8099/'); await rm.click('.search-toggle'); await rm.waitForTimeout(100);
  const anim = await rm.evaluate(() => getComputedStyle(document.querySelector('.search-panel')).animationName);
  await rm.keyboard.press('Escape'); await rm.waitForTimeout(50);
  console.log('reduced motion: animation', anim, '| closed immediately:', await rm.evaluate(() => !document.getElementById('islamicdilemma-search').open));
  await rm.close();
  for (const u of ['/', '/muslim-response/', '/enrichment/', '/injil-where-is-it/', '/?s=injil']) {
    const p = await b.newPage({ viewport: { width: 1280, height: 800 } });
    await p.goto('http://localhost:8099' + u); await p.waitForTimeout(300);
    const total = await p.evaluate(() => document.querySelectorAll('a[href], button, summary, input').length);
    const missing = new Set(); let stops = 0;
    for (let i = 0; i < Math.min(total, 120); i++) {
      await p.keyboard.press('Tab');
      const s = await p.evaluate(() => { const e = document.activeElement; if (!e || e === document.body || e.closest('dialog')) return null; const c = getComputedStyle(e); return { ok: c.outlineStyle !== 'none' && parseFloat(c.outlineWidth) >= 2, t: e.tagName + ' ' + (e.textContent || e.getAttribute('aria-label') || '').trim().slice(0, 30) }; });
      if (s) { stops++; if (!s.ok) missing.add(s.t); }
    }
    console.log(u, 'tab stops', stops, missing.size ? 'NO VISIBLE FOCUS: ' + [...missing].join(' | ') : 'all visible');
    await p.close();
  }
  await b.close();
})();
