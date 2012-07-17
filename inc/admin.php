<?php

/**
 * bbPress Digest Admin Functions
 *
 * @package bbPress Digest
 * @subpackage Admin Functions
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add bbPress Digest settings section.
 *
 * @since 2.0
 *
 * @param array $sections existing sections
 * @return array $sections new sections
 */
function bbp_digest_add_settings_section( $sections ) {
	/* Append section to existing ones */
	$sections['bbp_settings_digest'] = array(
		'title'    => _x( 'bbPress Digest Settings', 'settings section title', 'bbp-digest' ),
		'callback' => 'bbp_digest_admin_setting_callback_section',
		'page'     => 'bbpress',
	);

	return $sections;
}
add_filter( 'bbp_admin_get_settings_sections', 'bbp_digest_add_settings_section' );

/**
 * Add bbPress Digest settings fields.
 *
 * @since 2.0
 *
 * @param array $fields existing fields
 * @return array $fields new fields
 */
function bbp_digest_add_settings_fields( $fields ) {
	/* Append fields to existing ones */
	$fields['bbp_settings_digest'] = array(
		/* One-click subscription setting */
		'_bbp_digest_show_one_click' => array(
			'title'             => __( 'Show one-click subscription', 'bbp-digest' ),
			'callback'          => 'bbp_digest_admin_setting_callback_one_click',
			'sanitize_callback' => 'intval',
			'args'              => array()
		),
	);

	return $fields;
}
add_filter( 'bbp_admin_get_settings_fields', 'bbp_digest_add_settings_fields' );

/**
 * bbPress Digest settings section description for the settings page
 *
 * @since 2.0
 */
function bbp_digest_admin_setting_callback_section() {
	?>
	<p><?php _e( 'bbPress Digest settings for enabling features', 'bbp-digest' ); ?></p>
	<?php
}

/**
 * One-click subscription setting field
 *
 * @since 2.0
 *
 * @uses checked() To display the checked attribute
 */
function bbp_digest_admin_setting_callback_one_click() {
	?>
	<input id="_bbp_digest_show_one_click" name="_bbp_digest_show_one_click" type="checkbox" id="_bbp_digest_show_one_click" value="1" <?php checked( bbp_digest_is_it_active( '_bbp_digest_show_one_click' ) ); ?> />
	<label for="_bbp_digest_show_one_click"><?php _e( 'Allow users to include forum in a digest from a single forum page', 'bbp-digest' ); ?></label>
	<?php
}