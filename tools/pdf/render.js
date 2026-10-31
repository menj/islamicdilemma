// Run from the theme root: node tools/pdf/render.js
const { chromium } = require('playwright'); const path = require('path');
(async () => {
  const b = await chromium.launch(); const p = await b.newPage();
  await p.goto('file://' + path.resolve('tools/pdf/quick-reference.html')); await p.evaluate(() => document.fonts.ready);
  await p.pdf({ path: 'assets/downloads/islamic-dilemma-quick-reference.pdf', format: 'A4', printBackground: true, preferCSSPageSize: true,
    displayHeaderFooter: true, headerTemplate: '<span></span>',
    footerTemplate: '<div style="width:100%;font:7pt sans-serif;color:#5B6573;text-align:center">islamicdilemma.info · page <span class="pageNumber"></span> of <span class="totalPages"></span></div>' });
  await b.close();
})();
