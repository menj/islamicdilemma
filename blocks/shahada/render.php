<?php
/**
 * The testimony of faith, set as a decorated feature box.
 *
 * The closing line changes with the page it sits on: each one answers the
 * question that page raises. There is deliberately no button: the step it
 * describes is taken between the reader and God.
 *
 * @package islamicdilemma
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$islamicdilemma_messages = array(
	'default'   => array(
		__( 'That is the whole of it.', 'islamicdilemma' ),
		__( 'You need no priest, no ceremony, no sacrifice and no permission. It stays between you and God.', 'islamicdilemma' ),
	),
	'jesus'     => array(
		__( 'The first commandment, Jesus said, is “the Lord our God, the Lord is one.”', 'islamicdilemma' ),
		__( 'Islam asks you to believe exactly that, and to follow the last messenger who taught it.', 'islamicdilemma' ),
	),
	'mercy'     => array(
		__( 'God forgives whoever turns to Him.', 'islamicdilemma' ),
		__( 'No blood was ever the price of His mercy. Turn to Him, and everything before is forgiven.', 'islamicdilemma' ),
	),
	'paul'      => array(
		__( 'Follow Jesus himself.', 'islamicdilemma' ),
		__( 'He called people to worship his Lord and theirs. So does Islam.', 'islamicdilemma' ),
	),
	'witnesses' => array(
		__( 'Ministers, priests, a monk, a missionary and a preacher said these words.', 'islamicdilemma' ),
		__( 'They read, they asked, and they followed the evidence. The same words are open to you.', 'islamicdilemma' ),
	),
	'turn'      => array(
		__( 'Turn back to the God Jesus worshipped.', 'islamicdilemma' ),
		__( 'Whatever you have believed until now, the door is open, and it has never been closed.', 'islamicdilemma' ),
	),
);
$islamicdilemma_key  = isset( $islamicdilemma_messages[ $attributes['message'] ?? '' ] ) ? $attributes['message'] : 'default';
$islamicdilemma_line = $islamicdilemma_messages[ $islamicdilemma_key ];
?>
<aside <?php echo get_block_wrapper_attributes( array( 'class' => 'shahada', 'aria-labelledby' => 'shahada-' . esc_attr( $islamicdilemma_key ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="shahada-star shahada-star-tl" aria-hidden="true"></span>
	<span class="shahada-star shahada-star-tr" aria-hidden="true"></span>
	<span class="shahada-star shahada-star-bl" aria-hidden="true"></span>
	<span class="shahada-star shahada-star-br" aria-hidden="true"></span>
	<div class="shahada-inner">
		<p class="shahada-eyebrow" id="<?php echo esc_attr( 'shahada-' . $islamicdilemma_key ); ?>"><?php esc_html_e( 'The testimony of faith', 'islamicdilemma' ); ?> <span aria-hidden="true">·</span> <span class="shahada-term"><?php esc_html_e( 'al-shahādah', 'islamicdilemma' ); ?></span></p>
		<p class="shahada-arabic" lang="ar" dir="rtl">أَشْهَدُ أَن لَّآ إِلَـٰهَ إِلَّا ٱللَّهُ وَأَشْهَدُ أَنَّ مُحَمَّدًا رَّسُولُ ٱللَّهِ</p>
		<p class="shahada-rule" aria-hidden="true"><span></span></p>
		<p class="shahada-english"><?php esc_html_e( '“I bear witness that there is no god but God, and I bear witness that Muhammad is the messenger of God.”', 'islamicdilemma' ); ?></p>
		<p class="shahada-kicker"><strong><?php echo esc_html( $islamicdilemma_line[0] ); ?></strong> <?php echo esc_html( $islamicdilemma_line[1] ); ?></p>
	</div>
</aside>
