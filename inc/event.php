<?php

/**
 * bbPress Digest Cron Event Functions
 *
 * @package bbPress Digest
 * @subpackage Cron Event Functions
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Send digest emails on schedule
 *
 * @since 1.0
 */
function bbp_digest_do_event() {

	/* Get current time */
	$current_time = current_time( 'timestamp' );

	/* Get minutes passed since full hour */
	$current_minute = date( 'i', $current_time );

	/* Count number of minutes passed since full hour, plus seconds */
	if ( $current_minute > 00 ) {
		$seconds_late = ( $current_minute * 60 ) + date( 's', $current_time );
	} else {
		$seconds_late = date( 's', $current_time );
	}

	/* Get yesterday's time */
	$yesterday_time = $current_time - ( ( 24 * 3600 ) + $seconds_late );

	/* Setup arguments for user query */
	$user_args = array(
		'meta_key'   => 'bbp_digest_time',
		'meta_value' => date( 'H', $current_time ), // Only users that should receive in this hour
	);

	/* Query users */
	$users = get_users( $user_args );

	/* Only proceed further if there are users */
	if ( $users ) {

		/* Setup topic IDs array */
		$topic_ids = array();

		/* Setup arguments for topic query*/
		$topic_args = array(
			'post_type'      => bbp_get_topic_post_type(), // Only bbPress topic type
			'posts_per_page' => -1, // All topics
			'meta_key'       => '_bbp_last_active_time',
			'orderby'        => 'meta_value', // Order by _bbp_last_active_time (ie. from newest to oldest)
			'post_status'    => join( ',', array( bbp_get_public_status_id(), bbp_get_closed_status_id() ) ), // All public statuses
			'meta_query'     => array(
				array(
					'key' => '_bbp_last_active_time',
					'value' => date( 'Y-m-d H:i:s', $yesterday_time ), // Only active last 24 hours, plus passed time since full hour
					'compare' => '>',
					'type' => 'DATETIME',
				)
			)
		);

		/* Query topics */
		$topics = get_posts( $topic_args );

		/* Only proceed further if there are topics */
		if ( $topics ) {

			/* Add topics IDs to array */
			foreach ( $topics as $topic ) {
				$topic_ids[] = $topic->ID;
			}

			/* Set subject of email based on current time */
			if ( date( 'G', $current_time ) < apply_filters( 'bbp_digest_time_border', 8 ) ) { // If before 08:00, use yesterday
				$subject = sprintf( __( 'Active topics for %1$s', 'bbp-digest' ), date_i18n( _x( 'd. F Y.', 'one day span email title date format', 'bbp-digest' ), $yesterday_time ) );
			} else { // Otherwise, use both
				$subject = sprintf( _x( 'Active topics for %1$s / %2$s', '1. Yesterday 2. Today', 'bbp-digest' ), date_i18n( _x( 'd. F Y.', 'two day span yesterday email title date format', 'bbp-digest' ), $yesterday_time ), date_i18n( _x( 'd. F Y.', 'one day span today email title date format', 'bbp-digest' ), $current_time ) );
			}

			/* Set standard message intro */
			$message = __( "This topics have been active in the last 24 hours:\n\n", "bbp-digest" );

			/* Set list item placeholder; used because of new line (\n) */
			$item_placeholder = _x( '%1$s: %2$s
', '1. Topic title 2. Topic URL', 'bbp-digest' );

			/* Setup list of topics */
			$all_topics_list = '';

			/* Go through all topics */
			foreach ( $topic_ids as $topic_id ) {
				$all_topics_list .= sprintf( $item_placeholder, bbp_get_topic_title( $topic_id ), bbp_get_topic_last_reply_url( $topic_id ) );
			}

			/* Go through all users */
			foreach ( $users as $user ) {

				/* If user folows only selected forums, loop all topics again */
				if ( $user_forums = get_user_meta( $user->ID, 'bbp_digest_forums', true ) ) {

					/* Get string name of forum array, used for reducing duplication */
					$topic_list = md5( serialize( $user_forums ) );

					/* Check if topic list already created and send it, otherwise create it & send it */
					if ( $$topic_list ) {
						/* Send notification email */
						wp_mail( $user->user_email, $subject, $message . $$topic_list );
					} else {
						/* Setup list of topics */
						$$topic_list = '';

						/* Go through all topics */
						foreach ( $topic_ids as $topic_id ) {
							/* Is topic from forum user selected? */
							if ( in_array( bbp_get_topic_forum_id( $topic_id ), $user_forums ) ) {
								$$topic_list .= sprintf( $item_placeholder, bbp_get_topic_title( $topic_id ), bbp_get_topic_last_reply_url( $topic_id ) );
							}
						}

						/* Send notification email */
						wp_mail( $user->user_email, $subject, $message . $$topic_list );
					}
				/* Otherwise, send all topics */
				} else {
					/* Send notification email */
					wp_mail( $user->user_email, $subject, $message . $all_topics_list );
				}
			}
		}
	}
}