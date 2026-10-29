const { chromium } = require('playwright');
(async () => {
  const ids = process.argv.slice(2);
  const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 1400, height: 900 } });
  await p.goto('http://localhost:8099/wp-login.php'); await p.fill('#user_login', 'admin'); await p.fill('#user_pass', 'admin');
  await Promise.all([p.waitForNavigation(), p.click('#wp-submit')]);
  let bad = 0;
  for (const id of ids) {
    const warnings = []; const h = m => { if (/validation|invalid/i.test(m.text())) warnings.push(m.text().slice(0, 160)); }; p.on('console', h);
    await p.goto(`http://localhost:8099/wp-admin/post.php?post=${id}&action=edit`);
    await p.waitForFunction(() => window.wp && wp.data && wp.data.select('core/block-editor') && wp.data.select('core/editor').getCurrentPostId(), null, { timeout: 60000 });
    await p.waitForTimeout(1500);
    const r = await p.evaluate(() => {
      const out = { count: 0, invalid: [] };
      const walk = bs => bs.forEach(x => { out.count++; if (!x.isValid) out.invalid.push(x.name); walk(x.innerBlocks || []); });
      walk(wp.data.select('core/block-editor').getBlocks());
      return { ...out, title: wp.data.select('core/editor').getEditedPostAttribute('title') };
    });
    p.off('console', h);
    if (r.invalid.length || warnings.length) { bad++; console.log('PROBLEM', id, r.title, r.invalid, warnings.slice(0, 2)); }
    else console.log('ok', id, r.count, 'blocks', r.title.slice(0, 50));
  }
  console.log(bad ? bad + ' with problems' : 'ALL VALID IN EDITOR'); await b.close();
})();
