<?php
/**
 * Title: Article with sidebar
 * Slug: islamicdilemma/article-with-sidebar
 * Categories: islamicdilemma
 * Description: Two columns: the article, and a sidebar with the 'On this page' list and margin notes.
 * Inserter: yes
 *
 * @package islamicdilemma
 */

?>
<!-- wp:columns {"align":"wide","className":"article-grid"} -->
<div class="wp-block-columns alignwide article-grid"><!-- wp:column {"width":"64%"} -->
<div class="wp-block-column" style="flex-basis:64%"><!-- wp:paragraph -->
<p>Write the article here. Use level-two headings for its sections; they appear in the “On this page” list automatically.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"36%"} -->
<div class="wp-block-column" style="flex-basis:36%"><!-- wp:group {"className":"sidebar","layout":{"type":"default"}} -->
<div class="wp-block-group sidebar"><!-- wp:shortcode -->
[islamicdilemma_toc]
<!-- /wp:shortcode -->

<!-- wp:group {"className":"is-style-side-box","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-side-box"><!-- wp:heading {"className":"side-title"} -->
<h2 class="wp-block-heading side-title">Margin notes</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"gloss"} -->
<p class="gloss"><strong>Term</strong> A short note that sits beside the article on wide screens.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
