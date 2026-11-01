/**
 * Look phrases up in the Shamela library (shamela.ws) and print where each one occurs:
 * book title, volume and page, a link and a snippet. Used to check every classical citation
 * on the site against the printed text before it is published.
 *
 *   node tools/verify/shamela.js "arabic phrase" ["another phrase" ...]
 *   node tools/verify/shamela.js --book "title fragment" "arabic phrase"   (keep only matching books)
 *   node tools/verify/shamela.js --n 5 "arabic phrase"                       (results per phrase, default 4)
 *
 * Needs Playwright (require('playwright')). Results are what Shamela returns; read the page itself
 * (the link) before citing, since a snippet can be cut mid-sentence.
 */
const { chromium } = require( 'playwright' );

const args = process.argv.slice( 2 );
let bookFilter = '';
let limit = 4;
const phrases = [];
for ( let i = 0; i < args.length; i++ ) {
	if ( '--book' === args[ i ] ) { bookFilter = args[ ++i ]; }
	else if ( '--n' === args[ i ] ) { limit = parseInt( args[ ++i ], 10 ); }
	else { phrases.push( args[ i ] ); }
}

( async () => {
	const browser = await chromium.launch();
	const page = await browser.newPage();
	for ( const phrase of phrases ) {
		await page.goto( 'https://shamela.ws/search', { waitUntil: 'networkidle', timeout: 45000 } );
		const box = await page.$( 'input[type=text],input[type=search],input:not([type])' );
		await box.fill( '' );
		await box.fill( phrase );
		await box.press( 'Enter' );
		await page.waitForTimeout( 4500 );
		const results = await page.evaluate( () => Array.from( document.querySelectorAll( 'article.srch-result' ) ).map( ( art ) => {
			const link = art.querySelector( 'a[data-page-href]' );
			const title = art.querySelector( '.srch-result-title' );
			const snip = art.querySelector( '.srch-snippet-text, .srch-snippet' );
			return {
				url: link ? link.getAttribute( 'data-page-href' ) : '',
				text: ( title ? title.innerText : '' ).replace( /\s+/g, ' ' ).trim() + ' :: ' + ( snip ? snip.innerText : '' ).replace( /\s+/g, ' ' ).trim().slice( 0, 380 ),
			};
		} ) );
		const rows = results.filter( ( r ) => ! bookFilter || r.text.includes( bookFilter ) ).slice( 0, limit );
		console.log( '\n## ' + phrase );
		if ( ! rows.length ) { console.log( '  (no match)' ); }
		rows.forEach( ( r ) => console.log( '  ' + r.url + '\n    ' + r.text ) );
	}
	await browser.close();
} )();
