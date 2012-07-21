<?php

/**
 * bbPress Digest AJAX Functions
 *
 * @package bbPress Digest
 * @subpackage AJAX Functions
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handle one-click subscription
 *
 * @since 2.0
 *
 * @uses bbp_get_current_user_id() To get ID of current user
 * @uses current_user_can() To check if the current user can edit user
 * @uses bbp_get_forum() To get forum's data
 * @uses check_ajax_referer() To check nonce
 * @uses get_user_meta() To get user's digest settings
 * @uses current_time() To get current UNIX time
 * @uses add_user_meta() To save user's setting
 * @uses update_user_meta() To update user's setting
 * @uses delete_user_meta() To delete user's setting
 */
 function bbp_digest_do_one_click_ajax_handle() {
	/* Get current user's ID */
	$user_id  = bbp_get_current_user_id();

	/* Get forum's ID */
	$forum_id = intval( $_POST['id'] );

	/* Bail if user can't edit itself */
	if ( ! current_user_can( 'edit_user', $user_id ) )
		die( '-1' );

	/* Get forum object */
	$forum = bbp_get_forum( $forum_id );

	/* Bail if no forum */
	if ( empty( $forum ) )
		die( '0' );

	/* Check nonce */
	check_ajax_referer( 'toggle-bbp-digest-sub_' . $forum->ID );

	/* Get user's settings */
	$bbp_digest_time = get_user_meta( $user_id, 'bbp_digest_time', true );
	$bbp_digest_forums = get_user_meta( $user_id, 'bbp_digest_forums', true );

	/* If not receiving digest, setup hour */
	if ( ! $bbp_digest_time )
		$new_bbp_digest_time = date( 'H', current_time( 'timestamp' ) );
	else
		$new_bbp_digest_time = $bbp_digest_time;

	/* If no forums included, setup array */
	if ( ! is_array( $bbp_digest_forums ) )
		$bbp_digest_forums = array();

	/* Check if we're adding or removing forum */
	if ( isset( $_POST['dimClass'] ) && 'is-subscribed' == $_POST['dimClass'] ) {
		$new_bbp_digest_forums = array();
		/* Setup counters to see if we've removed all forums */
		$_total = $_removed = 0;
		foreach ( $bbp_digest_forums as $_forum ) {
			$_total++;
			if ( $_forum == $forum->ID )
				$_removed++;
			else
				$new_bbp_digest_forums[] = $_forum;
		}
		/* If we've removed all forums, stop sending digest */
		if ( $_total == $_removed )
			$new_bbp_digest_time = $new_bbp_digest_forums = '';
	} else {
		$new_bbp_digest_forums = $bbp_digest_forums;
		$new_bbp_digest_forums[] = $forum->ID;
	}

	/* Save data to the database */
	$meta = array(
		'bbp_digest_time' => $new_bbp_digest_time,
		'bbp_digest_forums' => $new_bbp_digest_forums,
	);

	foreach ( $meta as $meta_key => $new_meta_value ) {
		/* Get the current meta value of the key. */
		$meta_value = get_user_meta( $user_id, $meta_key, true );

		/* If a new meta value was added and there was no previous value, add it. */
		if ( $new_meta_value && '' == $meta_value )
			add_user_meta( $user_id, $meta_key, $new_meta_value, true );

		/* If the new meta value does not match the old value, update it. */
		elseif ( $new_meta_value && $new_meta_value != $meta_value )
			update_user_meta( $user_id, $meta_key, $new_meta_value );

		/* If there is no new meta value but an old value exists, delete it. */
		elseif ( '' == $new_meta_value && $meta_value )
			delete_user_meta( $user_id, $meta_key, $meta_value );
	}

	die( '1' );
}