<?php
/**
 * Title: Recent Enrichment articles
 * Slug: islamicdilemma/recent-enrichment
 * Categories: islamicdilemma
 * Description: The three latest Enrichment posts as cards.
 * Inserter: yes
 *
 * @package islamicdilemma
 */

?>
<!-- wp:group {"className":"section recent","layout":{"type":"constrained","contentSize":"1160px"}} -->
<div class="wp-block-group section recent"><!-- wp:group {"className":"section-head","layout":{"type":"default"}} -->
<div class="wp-block-group section-head"><!-- wp:paragraph {"className":"is-style-eyebrow"} -->
<p class="is-style-eyebrow">From Enrichment</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Recent commentary and analysis</h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:query {"queryId":3,"query":{"perPage":3,"postType":"post","order":"desc","orderBy":"date","inherit":false,"sticky":"exclude"},"align":"wide"} -->
<div class="wp-block-query alignwide"><!-- wp:post-template {"className":"cards3 post-cards","layout":{"type":"grid","columnCount":3}} -->
<!-- wp:group {"className":"post-card lift","layout":{"type":"default"}} -->
<div class="wp-block-group post-card lift"><!-- wp:group {"className":"meta","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"left"}} -->
<div class="wp-block-group meta"><!-- wp:post-terms {"term":"category"} /-->

<!-- wp:islamicdilemma/reading-time /--></div>
<!-- /wp:group -->

<!-- wp:post-title {"level":3,"isLink":true} /-->

<!-- wp:post-excerpt {"moreText":"Read commentary →"} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->

<!-- wp:paragraph {"className":"more-link"} -->
<p class="more-link"><a href="/enrichment/" class="textlink">All Enrichment articles →</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
