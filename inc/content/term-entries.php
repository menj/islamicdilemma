<?php
/**
 * Tags as glossary entries: structured term meta, its admin fields, and rendering helpers.
 *
 * @package islamicdilemma
 */

/**
 * Entry types, key => label.
 *
 * @return array
 */
function islamicdilemma_entry_types() {
	return array(
		'term'  => __( 'Glossary term', 'islamicdilemma' ),
		'topic' => __( 'Topic', 'islamicdilemma' ),
		'verse' => __( 'Verse', 'islamicdilemma' ),
		'person' => __( 'Person', 'islamicdilemma' ),
		'work'  => __( 'Work or manuscript', 'islamicdilemma' ),
	);
}

/**
 * Meta keys and how each is sanitised.
 *
 * @return array
 */
function islamicdilemma_entry_fields() {
	return array(
		'entry_type' => 'islamicdilemma_sanitize_entry_type',
		'arabic'     => 'sanitize_text_field',
		'category'   => 'sanitize_text_field',
		'definition' => 'sanitize_textarea_field',
		'related'    => 'sanitize_text_field',
	);
}

/**
 * Keep the entry type to the known list.
 *
 * @param string $value Submitted type.
 * @return string
 */
function islamicdilemma_sanitize_entry_type( $value ) {
	return isset( islamicdilemma_entry_types()[ $value ] ) ? $value : 'topic';
}

/**
 * Register the entry fields as tag meta.
 */
function islamicdilemma_register_entry_meta() {
	foreach ( islamicdilemma_entry_fields() as $key => $sanitize ) {
		register_term_meta(
			'post_tag',
			'id_' . $key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $sanitize,
				'auth_callback'     => function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);
	}
}
add_action( 'init', 'islamicdilemma_register_entry_meta' );

/**
 * One tag's entry data, with the related entries resolved to terms.
 *
 * @param WP_Term|int $term Tag.
 * @return array
 */
function islamicdilemma_term_entry( $term ) {
	$term = get_term( $term, 'post_tag' );
	if ( ! $term || is_wp_error( $term ) ) {
		return array();
	}
	$entry = array( 'term' => $term );
	foreach ( array_keys( islamicdilemma_entry_fields() ) as $key ) {
		$entry[ $key ] = (string) get_term_meta( $term->term_id, 'id_' . $key, true );
	}
	$entry['related'] = array_values( array_filter( array_map(
		function ( $slug ) {
			$related = get_term_by( 'slug', trim( $slug ), 'post_tag' );
			return $related ? $related : null;
		},
		array_filter( explode( ',', $entry['related'] ) )
	) ) );
	return $entry;
}

/**
 * Entry definitions use *italics* and {reference} marks.
 *
 * @param string $text Definition.
 * @return string Safe HTML.
 */
function islamicdilemma_entry_definition_html( $text ) {
	$text = preg_replace( '/\*([^*]+)\*/', '<em>$1</em>', $text );
	$text = preg_replace( '/\{([^}]+)\}/', '<span class="ref">$1</span>', $text );
	return wp_kses( $text, array( 'em' => array(), 'span' => array( 'class' => array() ) ) );
}

/**
 * Apply the entry data shipped in the theme to a tag, filling only fields still empty so edits survive.
 *
 * @param int   $term_id Tag ID.
 * @param array $data    Tag record from terms.json.
 */
function islamicdilemma_apply_entry_data( $term_id, $data ) {
	if ( empty( $data['entry_type'] ) && ! empty( $data['type'] ) ) {
		$data['entry_type'] = $data['type']; // The content files call it "type".
	}
	foreach ( array_keys( islamicdilemma_entry_fields() ) as $key ) {
		if ( empty( $data[ $key ] ) || '' !== (string) get_term_meta( $term_id, 'id_' . $key, true ) ) {
			continue;
		}
		$value = is_array( $data[ $key ] ) ? implode( ',', $data[ $key ] ) : $data[ $key ];
		update_term_meta( $term_id, 'id_' . $key, $value );
	}
}

/**
 * Fields on the Add Tag screen.
 */
function islamicdilemma_entry_add_fields() {
	wp_nonce_field( 'islamicdilemma_entry', 'islamicdilemma_entry_nonce' );
	?>
	<div class="form-field">
		<label for="id_entry_type"><?php esc_html_e( 'Entry type', 'islamicdilemma' ); ?></label>
		<select name="id_entry_type" id="id_entry_type">
			<?php foreach ( islamicdilemma_entry_types() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="form-field">
		<label for="id_arabic"><?php esc_html_e( 'Arabic', 'islamicdilemma' ); ?></label>
		<input type="text" name="id_arabic" id="id_arabic" dir="rtl" lang="ar">
	</div>
	<div class="form-field">
		<label for="id_category"><?php esc_html_e( 'Glossary group', 'islamicdilemma' ); ?></label>
		<input type="text" name="id_category" id="id_category">
	</div>
	<div class="form-field">
		<label for="id_definition"><?php esc_html_e( 'Short definition', 'islamicdilemma' ); ?></label>
		<textarea name="id_definition" id="id_definition" rows="3"></textarea>
		<p><?php esc_html_e( 'One or two sentences. Use *asterisks* for italics. A tag with a definition appears in the glossary.', 'islamicdilemma' ); ?></p>
	</div>
	<div class="form-field">
		<label for="id_related"><?php esc_html_e( 'Related entries', 'islamicdilemma' ); ?></label>
		<input type="text" name="id_related" id="id_related">
		<p><?php esc_html_e( 'Tag slugs separated by commas.', 'islamicdilemma' ); ?></p>
	</div>
	<?php
}
add_action( 'post_tag_add_form_fields', 'islamicdilemma_entry_add_fields' );

/**
 * Fields on the Edit Tag screen.
 *
 * @param WP_Term $term Tag being edited.
 */
function islamicdilemma_entry_edit_fields( $term ) {
	$entry = array();
	foreach ( array_keys( islamicdilemma_entry_fields() ) as $key ) {
		$entry[ $key ] = (string) get_term_meta( $term->term_id, 'id_' . $key, true );
	}
	wp_nonce_field( 'islamicdilemma_entry', 'islamicdilemma_entry_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="id_entry_type"><?php esc_html_e( 'Entry type', 'islamicdilemma' ); ?></label></th>
		<td>
			<select name="id_entry_type" id="id_entry_type">
				<?php foreach ( islamicdilemma_entry_types() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $entry['entry_type'] ?: 'topic', $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="id_arabic"><?php esc_html_e( 'Arabic', 'islamicdilemma' ); ?></label></th>
		<td><input type="text" name="id_arabic" id="id_arabic" dir="rtl" lang="ar" value="<?php echo esc_attr( $entry['arabic'] ); ?>"></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="id_category"><?php esc_html_e( 'Glossary group', 'islamicdilemma' ); ?></label></th>
		<td><input type="text" name="id_category" id="id_category" value="<?php echo esc_attr( $entry['category'] ); ?>"></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="id_definition"><?php esc_html_e( 'Short definition', 'islamicdilemma' ); ?></label></th>
		<td>
			<textarea name="id_definition" id="id_definition" rows="3"><?php echo esc_textarea( $entry['definition'] ); ?></textarea>
			<p class="description"><?php esc_html_e( 'One or two sentences. Use *asterisks* for italics. A tag with a definition appears in the glossary, whether or not any article uses it.', 'islamicdilemma' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="id_related"><?php esc_html_e( 'Related entries', 'islamicdilemma' ); ?></label></th>
		<td>
			<input type="text" name="id_related" id="id_related" value="<?php echo esc_attr( $entry['related'] ); ?>">
			<p class="description"><?php esc_html_e( 'Tag slugs separated by commas.', 'islamicdilemma' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'post_tag_edit_form_fields', 'islamicdilemma_entry_edit_fields' );

/**
 * Save the entry fields.
 *
 * @param int $term_id Tag ID.
 */
function islamicdilemma_entry_save( $term_id ) {
	if ( ! isset( $_POST['islamicdilemma_entry_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['islamicdilemma_entry_nonce'] ) ), 'islamicdilemma_entry' )
		|| ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	foreach ( islamicdilemma_entry_fields() as $key => $sanitize ) {
		if ( isset( $_POST[ 'id_' . $key ] ) ) {
			update_term_meta( $term_id, 'id_' . $key, call_user_func( $sanitize, wp_unslash( $_POST[ 'id_' . $key ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
	}
}
add_action( 'created_post_tag', 'islamicdilemma_entry_save' );
add_action( 'edited_post_tag', 'islamicdilemma_entry_save' );
