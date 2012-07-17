<?php

/**
 * The bbPress Digest Plugin
 *
 * Send daily digest with forum's active topics.
 *
 * @package bbPress Digest
 * @subpackage Main
 */

/**
 * Plugin Name: bbPress Digest
 * Plugin URI:  http://blog.milandinic.com/wordpress/plugins/bbpress-digest/
 * Description: Send daily digest with forum's active topics.
 * Author:      Milan Dinić
 * Author URI:  http://blog.milandinic.com/
 * Version:     2.0-alfa-1
 * Text Domain: bbp-digest
 * Domain Path: /languages/
 * License:     GPL
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Schedule bbPress Digest event on activation
 *
 * @since 1.0
 */
function bbp_digest_activation() {
	/* Get timestamp of the next full hour */
	$current_time = current_time( 'timestamp' );
	$timestamp = $current_time + ( 3600 - ( ( date( 'i', $current_time ) * 60 ) + date( 's', $current_time ) ) ); // Add passed seconds from full hour to the current time

	/* Clear the old recurring event and set up a new one */
	wp_clear_scheduled_hook( 'bbp_digest_event' );
	wp_schedule_event( $timestamp, 'hourly', 'bbp_digest_event' );
}
register_activation_hook( __FILE__, 'bbp_digest_activation' );

/**
 * Unschedule bbPress Digest event on activation
 *
 * @since 1.0
 */
function bbp_digest_deactivation() {
	$timestamp = wp_next_scheduled( 'bbp_digest_event' );
	wp_unschedule_event( $timestamp, 'bbp_digest_event' );
}
register_deactivation_hook( __FILE__, 'bbp_digest_deactivation' );

/**
 * Remove options on uninstallation of plugin
 *
 * Based on delete_post_meta_by_key()
 *
 * @since 1.0
*/
function bbp_digest_uninstall() {
	delete_metadata( 'user', null, 'bbp_digest_time', '', true );
	delete_metadata( 'user', null, 'bbp_digest_forums', '', true );
}
register_uninstall_hook( __FILE__, 'bbp_digest_uninstall' );

/**
 * Register actions on init hook
 *
 * @since 2.0
 */
function bbp_digest_init() {
	/* Show one-click subscription */
	if ( bbp_is_single_forum() || is_user_logged_in() ) {
		add_action( 'bbp_head', 'bbp_digest_head_scripts' );
		add_action( 'bbp_template_after_topics_loop', 'bbp_digest_one_click_subscription' );
	}

	/* Handle one-click subscription */
	add_action( 'wp_ajax_dim-bbp-digest-subscription', 'bbp_digest_one_click_ajax_handle' );
}
add_action( 'init', 'bbp_digest_init' );

/**
 * Load textdomain for internationalization
 *
 * @since 1.0
 */
function bbp_digest_load_textdomain() {
	load_plugin_textdomain( 'bbp-digest', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Add action links to plugins page
 *
 * Thanks to Dion Hulse for guide
 * and Adminize plugin for implementation
 *
 * @link http://dd32.id.au/wordpress-plugins/?configure-link
 * @link http://bueltge.de/wordpress-admin-theme-adminimize/674/
 *
 * @since 1.0
 *
 * @param array $links Default links of plugin
 * @param string $file Name of plugin's file
 * @return array $links New & old links of plugin
 */
function bbp_digest_filter_plugin_actions( $links, $file ) {
	/* Load translations */
	bbp_digest_load_textdomain();

	static $this_plugin;

	if ( ! $this_plugin )
		$this_plugin = plugin_basename( __FILE__ );

	if ( $file == $this_plugin ) {
		$donate_link = '<a href="http://blog.milandinic.com/donate/">' . __( 'Donate', 'bbp-digest' ) . '</a>';
		$links = array_merge( array( $donate_link ), $links ); // Before other links
	}

	return $links;
}
add_filter( 'plugin_action_links', 'bbp_digest_filter_plugin_actions', 10, 2 );

/**
 * Send digest emails on schedule
 *
 * @since 1.0
 */
function bbp_digest_event() {
	/* Load translations */
	bbp_digest_load_textdomain();
	/* Load file with event function */
	require_once( dirname( __FILE__ ) . '/inc/event.php' );
	/* Do event */
	bbp_digest_do_event();
}
add_action( 'bbp_digest_event', 'bbp_digest_event' );

/**
 * Show settings on user profile page
 *
 * @param object $user Viewed user's data
 *
 * @since 1.0
 */
function bbp_digest_profile_fields( $user ) {
	/* Load translations */
	bbp_digest_load_textdomain();
	/* Load file with forum list generator */
	require_once( dirname( __FILE__ ) . '/inc/forums-list.php' );
	/* Load file with settings form */
	require_once( dirname( __FILE__ ) . '/inc/wp-profile.php' );
	/* Display form */
	bbp_digest_display_profile_fields( $user );
}
add_action( 'show_user_profile', 'bbp_digest_profile_fields' );
add_action( 'edit_user_profile', 'bbp_digest_profile_fields' );

/**
 * Handle submission from users profile.
 *
 * @param object $user ID of a user
 *
 * @since 1.0
 */
function bbp_digest_save_profile_fields( $user_id ) {
	/* Load file with function for saving */
	require_once( dirname( __FILE__ ) . '/inc/save-profile.php' );
	/* Do event */
	bbp_digest_do_save_profile_fields( $user_id );
}
add_action( 'personal_options_update', 'bbp_digest_save_profile_fields' );
add_action( 'edit_user_profile_update', 'bbp_digest_save_profile_fields' );

/**
 * Show settings on user's bbPress profile page
 *
 * @since 1.0
 */
function bbp_digest_bbp_profile_fields() {
	/* Load translations */
	bbp_digest_load_textdomain();
	/* Load file with forum list generator */
	require_once( dirname( __FILE__ ) . '/inc/forums-list.php' );
	/* Load file with settings form */
	require_once( dirname( __FILE__ ) . '/inc/bbp-profile.php' );
	/* Display form */
	bbp_digest_display_bbp_profile_fields();
}
add_action( 'bbp_user_edit_after', 'bbp_digest_bbp_profile_fields' );

/**
 * Show one-click subscription on a single forum
 *
 * @since 2.0
 */
function bbp_digest_one_click_subscription() {
	/* Load translations */
	bbp_digest_load_textdomain();
	/* Load file with template function */
	require_once( dirname( __FILE__ ) . '/inc/one-click-template.php' );
	/* Display template */
	bbp_digest_display_one_click_subscription();
}

/**
 * Handle one-click subscription submission
 *
 * @since 2.0
 */
function bbp_digest_one_click_ajax_handle() {
	/* Load file with function for saving */
	require_once( dirname( __FILE__ ) . '/inc/one-click-handle.php' );
	/* Do handling */
	bbp_digest_do_one_click_ajax_handle();
}

/**
 * Show Javascript in a head of a page
 *
 * @since 2.0
 */
function bbp_digest_head_scripts() {
	?>
	<script type="text/javascript">
		/* <![CDATA[ */
		var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
		/* ]]> */
	</script>
	<?php
}