<?php

/**
 * bbPress Digest One-click Template
 *
 * @package bbPress Digest
 * @subpackage One-click Template
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Display link for one-click subscription
 *
 * @since 2.0
 *
 * @uses bbp_is_single_forum() To check if it's single forum
 * @uses is_user_logged_in() To check if current visitor is logged in
 * @uses bbp_get_current_user_id() To get ID of current user
 * @uses get_user_meta() To get user's digest settings
 * @uses bbp_get_forum_id() To get current forum's ID
 * @uses bbp_get_user_profile_edit_url() To get URL of user's settings
 * @uses wp_print_scripts() To load wpLists file
 * @uses bbp_get_forum_permalink() To get URL of a forum
 * @uses esc_url() To escape URL
 * @uses wp_nonce_url() To add nonce to the URL
 * @uses add_query_arg() To add query arguments to the URL
 */
function bbp_digest_display_one_click_subscription() {
	/* Bail if not viewing a single forum or not logged in */
	if ( ! bbp_is_single_forum() || ! is_user_logged_in() )
		return;

	/* Get current user's ID */
	$user_id = bbp_get_current_user_id();

	/* Get user's settings */
	$bbp_digest_time   = get_user_meta( $user_id, 'bbp_digest_time',   true );
	$bbp_digest_forums = get_user_meta( $user_id, 'bbp_digest_forums', true );

	/* Bail if user subscribed to all */
	if ( $bbp_digest_time && ! $bbp_digest_forums )
		return;

	/* Get this forum's ID */
	$forum_id = bbp_get_forum_id();

	/* Check user's subcription status*/
	$is_sub = in_array( $forum_id, (array) $bbp_digest_forums ) ? 1 : 0;

	/* Get link to bbPress Digest section at profile page */
	$profile_url = bbp_get_user_profile_edit_url( $user_id ) . '#bbp-digest-check-row';

	/* Setup texts */
	$sub_text = __( '<a href="%1$s" class="%2$s">Include topics from this forum to your daily digest</a> (<a href="%3$s">edit settings</a>)', 'bbp-digest' );
	$unsub_text = __( 'Topics from this forum are included in your daily digest (<a href="%1$s" class="%2$s">remove </a> | <a href="%3$s">edit settings</a>)', 'bbp-digest' );

	/* Prepare Javascript variables */
	$localizations = array(
		'currentUserId' => $user_id,
		'forumId'       => $forum_id,
		'settingsLink'  => $profile_url,
		'isSubscribed'  => (int) $is_sub,
		'subYes'        => sprintf( $unsub_text, '%subLinkYes%', '%classLink%', $profile_url ),
		'subNo'         => sprintf( $sub_text, '%subLinkNo%', '%classLink%', $profile_url ),
	);

	/* Prepare script with code taken from WP_Scripts::localize */
	foreach ( (array) $localizations as $key => $value ) {
		if ( ! is_scalar( $value ) )
			continue;

		$scripts[$key] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
	}

	$script = 'var bbpDigestJS = ' . json_encode( $scripts ) . ';';

	/* Load necessary scripts */
	wp_print_scripts( 'wp-lists' );

	/* Print Javascript code */
	echo "<script type='text/javascript'>\n"; // CDATA and type='text/javascript' is not needed for HTML 5
	echo "/* <![CDATA[ */\n";
	echo "$script\n";
	echo "/* ]]> */\n";
	echo "</script>\n";

	/* Javascript that handles link clicks, taken from bbPress'	topic.js */
	?>
	<script type="text/javascript">
	/* Check if we already subscribed */
	bbpDigestJS.isSubscribed = parseInt( bbpDigestJS.isSubscribed );

	/* Here all magic happens */
	jQuery(document).ready( function() {
		/* Setup wpList that handles actions */
		var bbpDigestSubToggle = jQuery( '#bbp-digest-sub-toggle' )
			.addClass( 'list:bbp-digest-subscription' )
			.wpList( { alt: '', dimClass: 'as', dimAfter: bbpDigestSubLinkSetup } );

		var bbpDigestSubToggleSpan = bbpDigestSubToggle.children( 'span' );

		/* Function that's run on link click */
		function bbpDigestSubLinkSetup() {
			/* Setup link and class that're replaced later */
			var aLink = bbpDigestSubToggleSpan.find( 'a[class^="dim:"]' ).attr( 'href' );
			var aClass  = "dim:bbp-digest-sub-toggle:" + bbpDigestSubToggleSpan.attr( 'id' ) + ":is-subscribed";
			/* Do action based on if already subscribed */
			if ( bbpDigestJS.isSubscribed ) {
				html = bbpDigestJS.subNo
					.replace( /%subLinkNo%/, aLink )
					.replace( /%classLink%/, aClass );
				jQuery(bbpDigestSubToggleSpan).removeClass('is-subscribed').addClass('not-subscribed');
				bbpDigestJS.isSubscribed = false;
			} else {
				html = bbpDigestJS.subYes
					.replace( /%subLinkYes%/, aLink )
					.replace( /%classLink%/, aClass );
				jQuery(bbpDigestSubToggleSpan).removeClass('not-subscribed').addClass('is-subscribed');
				bbpDigestJS.isSubscribed = true;
			}
			/* Process action & toggle link */
			bbpDigestSubToggleSpan.html( html );
			bbpDigestSubToggle.get(0).wpList.process( bbpDigestSubToggle );
		}
	} );
	</script>
	<?php
	/* Setup variables based on subscription status */
	if ( 1 == $is_sub ) {
		$text = $unsub_text;
		$favs = array( 'action' => 'bbp_digest_remove_sub', 'forum_id' => $forum_id );
	} else {
		$text = $sub_text;
		$favs = array( 'action' => 'bbp_digest_add_sub', 'forum_id' => $forum_id );
	}

	/* Get link to the forum's page */
	$permalink = bbp_get_forum_permalink( $forum_id );

	/* Setup subelements */
	$url    = esc_url( wp_nonce_url( add_query_arg( $favs, $permalink ), 'toggle-bbp-digest-sub_' . $forum_id ) );
	$is_sub_class = $is_sub ? 'is-subscribed' : 'not-subscribed';
	$a_class = 'dim:bbp-digest-sub-toggle:bbp-digest-sub-' . $forum_id;
	$a_class = $is_sub_class ? $a_class . ':' . $is_sub_class : $a_class;

	/* Prepare elements with subelements */
	$_pre = '<span id="bbp-digest-sub-toggle"><span id="bbp-digest-sub-' . $forum_id . '" class="' . $is_sub_class . '">';
	$_mid = sprintf( $text, $url, $a_class, $profile_url );
	$_post = '</span></span>';

	/* Create and return final element */
	$html = $_pre . $_mid . $_post;

	echo $html;
}