// Usage: node overlaytest.js <url> <toggle selector> <panel selector> <input selector>
const { chromium } = require('playwright');
(async () => {
  const [url, tog, panel, inputSel] = process.argv.slice(2);
  const b = await chromium.launch(); const out = {};
  for (const w of [1280, 390]) {
    const p = await b.newPage({ viewport: { width: w, height: 800 } });
    const errs = []; p.on('pageerror', e => errs.push(e.message));
    await p.goto(url); await p.waitForTimeout(400);
    const r = {};
    const t = await p.$(tog); const tb = await t.boundingBox();
    r.toggleSize = [Math.round(tb.width), Math.round(tb.height)];
    r.toggleName = await p.evaluate(s => { const e = document.querySelector(s); return e.getAttribute('aria-label') || e.textContent.trim(); }, tog);
    // keyboard open
    await t.focus(); await p.keyboard.press('Enter'); await p.waitForTimeout(400);
    r.openByEnter = await p.evaluate(s => !!document.querySelector(s) && document.querySelector(s).getClientRects().length > 0, panel);
    r.focusInInput = await p.evaluate(s => document.activeElement === document.querySelector(s), inputSel);
    r.scrollLocked = await p.evaluate(() => { const before = scrollY; scrollBy(0, 400); const moved = scrollY !== before; scrollTo(0, before); return !moved; });
    r.dialogSemantics = await p.evaluate(s => { const e = document.querySelector(s).closest('dialog,[role=dialog]'); return e ? (e.tagName === 'DIALOG' ? 'native dialog' : 'role=dialog aria-modal=' + e.getAttribute('aria-modal')) : 'none'; }, panel);
    // focus trap: tab 15 times, record whether focus ever leaves the panel
    let escaped = 0;
    for (let i = 0; i < 15; i++) { await p.keyboard.press('Tab'); escaped += await p.evaluate(s => { const pn = document.querySelector(s); return pn.contains(document.activeElement) ? 0 : 1; }, panel); }
    r.tabEscapes = escaped;
    for (let i = 0; i < 4; i++) { await p.keyboard.press('Shift+Tab'); escaped += await p.evaluate(s => document.querySelector(s).contains(document.activeElement) ? 0 : 1, panel); }
    r.tabEscapesWithShift = escaped;
    // background reachable by screen readers? (inert / aria-hidden / native modal)
    r.backgroundInert = await p.evaluate(s => { const main = document.querySelector('main'); if (!main) return 'no main'; const d = document.querySelector(s).closest('dialog'); return (d && d.matches(':modal')) || main.closest('[inert],[aria-hidden=true]') ? 'yes' : 'no'; }, panel);
    // tap targets inside panel
    r.smallTargets = await p.evaluate(s => [...document.querySelector(s).querySelectorAll('a,button,input')].filter(e => e.getClientRects().length).map(e => { const bb = e.getBoundingClientRect(); return [e.tagName + ':' + (e.textContent.trim() || e.getAttribute('aria-label') || e.name).slice(0, 14), Math.round(bb.height)]; }).filter(x => x[1] < 44), panel);
    // Escape closes and returns focus
    await p.keyboard.press('Escape'); await p.waitForTimeout(450);
    r.closedByEsc = await p.evaluate(s => document.querySelector(s).getClientRects().length === 0 || !document.querySelector(s).checkVisibility(), panel);
    r.focusReturned = await p.evaluate(s => document.activeElement === document.querySelector(s), tog);
    r.scrollRestored = await p.evaluate(() => { const before = scrollY; scrollBy(0, 300); const moved = scrollY !== before; scrollTo(0, before); return moved || document.documentElement.scrollHeight <= innerHeight; });
    // submit goes to search
    await t.click(); await p.waitForTimeout(400); await p.fill(inputSel, 'Injil');
    await Promise.all([p.waitForNavigation({ timeout: 8000 }).catch(() => null), p.keyboard.press('Enter')]);
    r.submitUrl = p.url().replace(/^.*\//, '');
    r.errors = errs;
    out[w] = r; await p.close();
  }
  console.log(JSON.stringify(out, null, 1)); await b.close();
})();
