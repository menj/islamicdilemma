// Usage: node serialize.js <in-dir of *.json block trees> <out-dir for *.html>
// Each JSON file is an array of {name, attributes, innerBlocks}. Output is
// WordPress's own serialized block markup, then re-parsed to confirm validity.
const { chromium } = require('playwright');
const fs = require('fs'), path = require('path');
(async () => {
  const [inDir, outDir] = process.argv.slice(2);
  fs.mkdirSync(outDir, { recursive: true });
  const b = await chromium.launch();
  const ctx = await b.newContext({ viewport: { width: 1400, height: 900 } });
  const p = await ctx.newPage();
  await p.goto('http://localhost:8099/wp-login.php');
  await p.fill('#user_login', 'admin'); await p.fill('#user_pass', 'admin');
  await Promise.all([p.waitForNavigation(), p.click('#wp-submit')]);
  await p.goto('http://localhost:8099/wp-admin/post-new.php?post_type=page');
  await p.waitForFunction(() => window.wp && wp.blocks && wp.blocks.getBlockTypes().length > 50, null, { timeout: 60000 });
  let bad = 0;
  for (const f of fs.readdirSync(inDir).filter(x => x.endsWith('.json')).sort()) {
    const tree = JSON.parse(fs.readFileSync(path.join(inDir, f), 'utf8'));
    const res = await p.evaluate((tree) => {
      const missing = new Set();
      const make = (n) => {
        if (!wp.blocks.getBlockType(n.name)) missing.add(n.name);
        return wp.blocks.createBlock(n.name, n.attributes || {}, (n.innerBlocks || []).map(make));
      };
      const blocks = tree.map(make);
      const html = wp.blocks.serialize(blocks);
      const problems = [];
      const walk = (bs) => bs.forEach(x => { if (!x.isValid) problems.push(x.name + ': ' + (x.validationIssues || []).map(i => i.args && i.args.join(' ').slice(0, 200)).join(' | ')); walk(x.innerBlocks || []); });
      walk(wp.blocks.parse(html));
      return { html, problems, missing: [...missing] };
    }, tree);
    fs.writeFileSync(path.join(outDir, f.replace(/\.json$/, '.html')), res.html);
    if (res.problems.length || res.missing.length) { bad++; console.log('INVALID', f, res.missing, res.problems.slice(0, 5)); }
  }
  console.log(bad ? `${bad} files with problems` : 'all serialized and valid');
  await b.close();
})();
