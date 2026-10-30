/** Native-dialog regression fixture, independent of a WordPress installation. */
const assert = require('node:assert/strict');
const path = require('node:path');
const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  let checks = 0;
  try {
    for (const width of [390, 1440]) {
      for (const reducedMotion of ['reduce', 'no-preference']) {
        const page = await browser.newPage({ viewport: { width, height: 800 }, reducedMotion });
        const errors = [];
        page.on('pageerror', e => errors.push(e.message));
        await page.setContent(`<!doctype html><html><head><style>
          @keyframes id-sink { to { opacity: 0; } }
          .is-closing .search-panel { animation: id-sink 50ms; }
          </style></head><body><a class="search-toggle" href="/?s=">Search</a>
          <dialog id="islamicdilemma-search"><div class="search-panel">
          <button class="search-close">Close</button><input class="search-input">
          <button type="submit">Search</button></div></dialog></body></html>`);
        await page.addScriptTag({ path: path.join(__dirname, '../blocks/search-toggle/view.js') });
        const state = () => page.evaluate(() => ({
          open: document.querySelector('dialog').open,
          lock: document.documentElement.classList.contains('search-open'),
          expanded: document.querySelector('.search-toggle').getAttribute('aria-expanded'),
          focus: document.activeElement.className
        }));
        const openState = { open: true, lock: true, expanded: 'true', focus: 'search-input' };
        const closedState = { open: false, lock: false, expanded: 'false', focus: 'search-toggle' };
        await page.click('.search-toggle');
        assert.deepEqual(await state(), openState); checks++;
        // The native close event is dispatched after this task has reopened the dialog.
        await page.evaluate(() => {
          document.querySelector('dialog').close();
          document.querySelector('.search-toggle').click();
        });
        await page.waitForTimeout(100);
        assert.deepEqual(await state(), openState); checks++;
        await page.keyboard.press('Escape'); await page.keyboard.press('Escape');
        await page.waitForTimeout(400);
        assert.deepEqual(await state(), closedState); checks++;
        if (reducedMotion === 'no-preference') {
          await page.click('.search-toggle');
          await page.evaluate(() => {
            document.querySelector('.search-close').click();
            document.querySelector('.search-toggle').click();
          });
          await page.waitForTimeout(400);
          assert.deepEqual(await state(), openState); checks++;
          await page.keyboard.press('Escape'); await page.waitForTimeout(400);
        }
        for (let i = 0; i < 10; i++) {
          await page.evaluate(() => {
            document.querySelector('.search-toggle').click();
            document.querySelector('.search-close').click();
          });
          await page.waitForTimeout(400);
        }
        assert.deepEqual(await state(), closedState); checks++;
        assert.deepEqual(errors, []); checks++;
        await page.close();
      }
    }
    console.log(`${checks} search-overlay regression checks passed.`);
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
