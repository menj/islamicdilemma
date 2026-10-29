const { chromium } = require('playwright');
(async () => {
  const out = process.argv[2]; const b = await chromium.launch(); const base = 'http://localhost:8099';
  const shot = async (name, url, w, full = true, before) => {
    const p = await b.newPage({ viewport: { width: w, height: 900 }, deviceScaleFactor: w < 500 ? 2 : 1 });
    await p.goto(base + url); await p.evaluate(() => document.fonts.ready);
    for (let y = 0; y < 20000; y += 800) { await p.evaluate(v => scrollTo(0, v), y); await p.waitForTimeout(25); }
    await p.evaluate(() => scrollTo(0, 0)); await p.waitForTimeout(300);
    if (before) await before(p);
    await p.screenshot({ path: `${out}/${name}.png`, fullPage: full }); await p.close();
  };
  await shot('01-home-desktop', '/', 1440);
  await shot('02-home-phone', '/', 390);
  await shot('03-premise-open', '/', 1440, false, async p => { await p.click('.argument details:nth-of-type(2) summary'); await p.waitForTimeout(300); });
  await shot('04-search-overlay', '/', 1440, false, async p => { await p.click('.search-toggle'); await p.waitForTimeout(450); });
  await shot('05-search-overlay-phone', '/', 390, false, async p => { await p.click('.search-toggle'); await p.waitForTimeout(450); });
  await shot('06-response-page', '/muslim-response/', 1440);
  await shot('07-enrichment-article', '/injil-where-is-it/', 1440);
  await shot('08-enrichment-blog', '/enrichment/', 1440);
  await shot('09-search-results', '/?s=injil', 1440);
  await shot('10-search-no-results', '/?s=zzqqxx', 1440);
  await shot('11-invitation-page', '/invitation-to-christians/', 1440);
  const p = await b.newPage({ viewport: { width: 1440, height: 1100 } });
  await p.goto(base + '/wp-login.php'); await p.fill('#user_login', 'admin'); await p.fill('#user_pass', 'admin');
  await Promise.all([p.waitForNavigation(), p.click('#wp-submit')]);
  await p.goto(base + '/wp-admin/themes.php?page=islamicdilemma-content'); await p.waitForTimeout(500);
  await p.screenshot({ path: `${out}/12-admin-theme-content.png`, fullPage: true });
  await b.close(); console.log('done');
})();
