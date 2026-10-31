const { chromium } = require('playwright');
const fs = require('fs');
(async () => {
  const [urlFile] = process.argv.slice(2);
  const urls = fs.readFileSync(urlFile, 'utf8').trim().split('\n');
  const b = await chromium.launch(); let problems = 0; const links = new Set();
  const report = (...a) => { problems++; console.log('PROBLEM', ...a); };
  for (const u of urls) for (const w of [1440, 768, 390, 320]) {
    const p = await b.newPage({ viewport: { width: w, height: 900 } });
    const errs = []; p.on('pageerror', e => errs.push(e.message));
    p.on('requestfailed', r => errs.push('failed ' + r.url()));
    const resp = await p.goto('http://localhost:8099' + u);
    for (let y = 0; y < 20000; y += 700) { await p.evaluate(v => scrollTo(0, v), y); await p.waitForTimeout(25); }
    await p.waitForTimeout(250);
    const r = await p.evaluate(() => {
      const text = document.body.innerText;
      const green = [...document.querySelectorAll('*')].filter(e => e.getClientRects().length).some(e => [getComputedStyle(e).color, getComputedStyle(e).backgroundColor].some(c => { const m = c.match(/rgba?\((\d+), (\d+), (\d+)/); return m && +m[2] > +m[1] + 40 && +m[2] > +m[3] + 15; }));
      return {
        ov: document.documentElement.scrollWidth - innerWidth,
        imgs: [...document.querySelectorAll('img')].filter(i => !(i.complete && i.naturalWidth > 0)).map(i => i.src),
        noAlt: [...document.querySelectorAll('img')].filter(i => !i.hasAttribute('alt')).length,
        green, banned: (text.match(/islamicdilemma\.com|organi[sz]ation|independent|\bhorns?\b/gi) || []),
        nav: [...document.querySelectorAll('.main-nav a, .search-toggle')].map(a => Math.round(a.getBoundingClientRect().height)),
        links: [...document.querySelectorAll('a[href]')].map(a => a.href).filter(h => h.startsWith(location.origin) && !h.includes('/wp-admin') && !h.includes('/feed')),
        anchors: [...document.querySelectorAll('a[href^="#"]')].map(a => a.getAttribute('href')).filter(h => h.length > 1 && !document.getElementById(decodeURIComponent(h.slice(1)))),
        h1: document.querySelectorAll('h1').length,
      };
    });
    const tag = `${u} @${w}`;
    const expect404 = u === '/no-such-page/';
    if (!expect404 && resp.status() !== 200) report(tag, 'status', resp.status());
    if (r.ov) report(tag, 'overflow', r.ov);
    errs.filter(e => !(expect404 && e.includes('no-such-page'))).forEach(e => report(tag, e));
    r.imgs.forEach(i => report(tag, 'image not loaded', i));
    if (r.noAlt) report(tag, 'images without alt', r.noAlt);
    if (r.green) report(tag, 'green rendered');
    if (r.banned.length) report(tag, 'banned words', r.banned);
    if (w <= 781 && r.nav.some(h => h < 44)) report(tag, 'tap targets', r.nav);
    if (r.h1 !== 1) report(tag, 'h1 count', r.h1);
    r.anchors.forEach(a => report(tag, 'broken anchor', a));
    r.links.forEach(l => links.add(l.split('#')[0]));
    await p.close();
  }
  // every internal link resolves
  const ctx = await b.newContext();
  for (const l of links) { const res = await ctx.request.get(l, { maxRedirects: 3 }); if (res.status() >= 400 && !l.includes('no-such-page')) report('link', l, res.status()); }
  console.log(`checked ${urls.length} URLs x 4 widths, ${links.size} distinct internal links`);
  console.log(problems ? problems + ' problems' : 'ALL CHECKS PASSED'); await b.close();
})();
