<?php
/**
 * Block styles editors can choose in the block toolbar's Styles panel.
 * The CSS for each style lives in assets/css/theme.css.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the theme's block styles.
 */
function islamicdilemma_register_block_styles() {
	$styles = array(
		// Premise cards: each Details block shows its verdict as a pill.
		'core/details'   => array(
			'verdict-granted'         => __( 'Verdict: Granted', 'islamicdilemma' ),
			'verdict-equivocates'     => __( 'Verdict: Equivocates', 'islamicdilemma' ),
			'verdict-does-not-follow' => __( 'Verdict: Does not follow', 'islamicdilemma' ),
			'verdict-false'           => __( 'Verdict: False', 'islamicdilemma' ),
			'verdict-unsupported'     => __( 'Verdict: Unsupported', 'islamicdilemma' ),
		),
		'core/paragraph' => array(
			'arabic'  => __( 'Arabic verse', 'islamicdilemma' ),
			'lead'    => __( 'Lead', 'islamicdilemma' ),
			'eyebrow' => __( 'Eyebrow label', 'islamicdilemma' ),
			'verse-ref' => __( 'Verse reference', 'islamicdilemma' ),
		),
		'core/group'     => array(
			'ayah'     => __( 'Verse card', 'islamicdilemma' ),
			'callout'  => __( 'Gold callout', 'islamicdilemma' ),
			'side-box' => __( 'Sidebar box', 'islamicdilemma' ),
			'card'     => __( 'Card', 'islamicdilemma' ),
		),
		'core/quote'     => array(
			'bible' => __( 'Scripture quotation', 'islamicdilemma' ),
		),
		'core/button'    => array(
			'ghost' => __( 'Ghost on navy', 'islamicdilemma' ),
		),
		'core/list'      => array(
			'notes' => __( 'Footnotes', 'islamicdilemma' ),
		),
	);

	foreach ( $styles as $block => $variants ) {
		foreach ( $variants as $name => $label ) {
			register_block_style(
				$block,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}
add_action( 'init', 'islamicdilemma_register_block_styles' );
