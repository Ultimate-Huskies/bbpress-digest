<?php

/**
 * bbPress Digest WP Profile Functions
 *
 * @package bbPress Digest
 * @subpackage WP Profile Functions
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Display settings on bbPress profile page
 *
 * @since 1.0
 */
function bbp_digest_display_bbp_profile_fields() {
	/* Get displayed user's ID */
	$user_id = bbp_get_displayed_user_id();

	/* Get user's settings */
	$bbp_digest_time = get_user_meta( $user_id, 'bbp_digest_time', true );
	$bbp_digest_forums = get_user_meta( $user_id, 'bbp_digest_forums', true );

	/* Load jQuery */
	wp_print_scripts( 'jquery' );
	?>
	<style type="text/css">
	#content #bbp-your-profile fieldset #bbp-digest-check-row label, #content #bbp-your-profile fieldset #bbp-digest-pool-row label, #container #bbp-your-profile fieldset #bbp-digest-check-row label, #container #bbp-your-profile fieldset #bbp-digest-pool-row label {
		float: none;
		width: 210px;
		padding-right: 0px;
		text-align: right;
		line-height: 0;
	}
	#content #bbp-your-profile fieldset #bbp-digest-pool-selection label, #container #bbp-your-profile fieldset #bbp-digest-pool-selection label {
		width: 195px;
	}
	#content #bbp-your-profile fieldset #bbp-digest-forum-list label, #container #bbp-your-profile fieldset #bbp-digest-forum-list label {
		display:inline;
		margin-left: 170px;
	}
	#content fieldset.bbp-form #bbp-digest-check-row input, #content fieldset.bbp-form #bbp-digest-pool-row input, #container fieldset.bbp-form #bbp-digest-check-row input, #container fieldset.bbp-form #bbp-digest-pool-row input, #wrapper fieldset.bbp-form #bbp-digest-check-row input, #wrapper fieldset.bbp-form #bbp-digest-pool-row input {
		margin-bottom: 0px;
		line-height: normal;
		width: 13px;
	}
	</style>
	<h2 class="entry-title"><?php _e( 'bbPress Digest Emails', 'bbp-digest' ) ?></h2>

	<fieldset class="bbp-form">

		<div id="bbp-digest-check-row">
			<label for="bbp-digest-subscription"><input name="bbp-digest-subscription" type="checkbox" id="bbp-digest-subscription" value="1" <?php checked( ! $bbp_digest_time, false ); ?> /> <?php _ex( 'Yes', 'checkbox label', 'bbp-digest' ) ?></label>
			<span class="description"><?php _e( 'Check if you want to receive daily digest with active forum topics for that day.', 'bbp-digest' ) ?></span>
		</div>

		<div id="bbp-digest-time-row">
			<label for="bbp-digest-time"><?php _e( 'Daily digests should be sent at this time:', 'bbp-digest' ) ?> </label>
			<select name="bbp-digest-time" id="bbp-digest-time">
				<?php for ( $i = 0; $i <= 23; $i++ ) : ?>
					<?php if ( $i < 10 ) $i = '0' . $i ?>
					<option value="<?php echo $i?>" <?php selected( $i, $bbp_digest_time ); ?>><?php echo $i; ?></option>
				<?php endfor; ?>
			</select>
			<span class="description"><?php printf( __( 'Select the hour of the day when you want to receive digest emails. Current time is <code>%1$s</code>.', 'bbp-digest' ), date_i18n( _x( 'Y-m-d G:i:s', 'current time date format', 'bbp-digest' ) ) ); ?></span>
		</div>

		<div id="bbp-digest-pool-row">
			<div id="bbp-digest-pool-selection">
			<label for="bbp-digest-pool-all"><input name="bbp-digest-pool" id="bbp-digest-pool-all" type="radio" value="all" <?php checked( ! $bbp_digest_forums, true ); ?> /><?php _ex( 'All', 'radio button label', 'bbp-digest' ) ?> </label>
			<label for="bbp-digest-pool-selected"><input name="bbp-digest-pool" id="bbp-digest-pool-selected" type="radio" value="selected" <?php checked( ! $bbp_digest_forums, false ); ?> /><?php _e( 'Only forums I choose', 'bbp-digest' ) ?> </label><br />
			<span class="description"><?php _e( 'Choose should digest include topics from all forums or only from selected forums.', 'bbp-digest' ) ?></span><br />
			</div>

			<div id="bbp-digest-forum-list">
			<?php
			echo bbp_digest_get_dropdown( array(
				'selected_forums' => (array) $bbp_digest_forums,
				'disable_categories' => false
			) );
			?>
			<span class="description"><?php _e( 'Choose forums which you want to be included in a digest.', 'bbp-digest' ) ?></span>
			</div>
		</div>
	</fieldset>

	<script type="text/javascript">
		jQuery(document).ready(function($) {
			/* If not subscribed, hide time dropdown & forum selection */
			if ( false == $('input#bbp-digest-subscription').is(':checked') ) {
				$('#bbp-digest-time-row').hide();
				$('#bbp-digest-pool-row').hide();
			}

			/* On subscription state change, show/hide dropdown & forum selection, and enable/disable inputs */
			$('input#bbp-digest-subscription').click(function() {
				if ( $(this).is(':checked') ) {
					$('#bbp-digest-time-row').show();
					$('#bbp-digest-pool-row').show();

					$('#bbp-digest-time').attr("disabled",false);
					if ( $('#bbp-digest-pool-selected').is(':checked') ) {
						$('#bbp-digest-forum-list input').attr("disabled",false);
					}
				} else {
					$('#bbp-digest-time-row').hide();
					$('#bbp-digest-pool-row').hide();

					$('#bbp-digest-time').attr("disabled",true);
					$('#bbp-digest-forum-list input').attr("disabled",true);
				}
			});

			/* If subscribed to all, hide forum list */
			if ( $('input#bbp-digest-pool-all').is(':checked') ) {
				$('#bbp-digest-forum-list').hide();
			}

			/* On subscription pool state change, show/hide forum list, and enable/disable inputs */
			$('#bbp-digest-pool-selection input:radio').click(function() {
				/* Get id of selected option */
				var currentId = $(this).attr('id');

				if ( 'bbp-digest-pool-selected' == currentId ) {
					$('#bbp-digest-forum-list').show();
					$('#bbp-digest-forum-list input').attr("disabled",false);
				} else {
					$('#bbp-digest-forum-list').hide();
					$('#bbp-digest-forum-list input').attr("disabled",true);
				}
			});
		});
	</script>
	<?php
}