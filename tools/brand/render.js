// Render the PNG icon set from the SVGs. Run from the theme root: node tools/brand/render.js <tmpdir>
// then: convert <tmpdir>/ico-16.png <tmpdir>/ico-32.png <tmpdir>/ico-48.png assets/brand/favicon.ico
const { chromium } = require('playwright'); const fs = require('fs');
(async () => {
  const tmp = process.argv[2]; const b = await chromium.launch(); const p = await b.newPage();
  const jobs = [['favicon.svg', 16, tmp + '/ico-16.png'], ['favicon.svg', 32, tmp + '/ico-32.png'], ['favicon.svg', 48, tmp + '/ico-48.png'],
    ['favicon.svg', 96, 'assets/brand/favicon-96.png'], ['brandmark-square.svg', 180, 'assets/brand/apple-touch-icon.png'],
    ['brandmark.svg', 192, 'assets/brand/icon-192.png'], ['brandmark.svg', 512, 'assets/brand/icon-512.png'],
    ['brandmark-square.svg', 512, 'assets/brand/icon-maskable-512.png']];
  for (const [f, w, o] of jobs) {
    const d = fs.readFileSync('assets/brand/' + f).toString('base64');
    await p.setViewportSize({ width: w, height: w });
    await p.setContent(`<body style="margin:0"><img src="data:image/svg+xml;base64,${d}" width=${w} height=${w} style="display:block"></body>`);
    await p.screenshot({ path: o, omitBackground: true });
  }
  await b.close();
})();
