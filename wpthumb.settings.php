<?php

/**
 * Add the retina setting field to the media settings page
 */
function wpthumb_retina_setting() {
	
	register_setting( 'media', 'wpthumb_retina' );
	add_settings_field( 'wpthumb_retina', 'Add Retina support to thumbnails', 'wpthumb_retina_setting_field', 'media' );

}
add_action( 'admin_init', 'wpthumb_retina_setting' );

/**
 * Output the retina setting field
 */
function wpthumb_retina_setting_field() { ?>

	<input name="wpthumb_retina" id="wpthumb_retina" type="checkbox"<?php checked( get_option( 'wpthumb_retina' ), 'on' ); ?> /> <label for="wpthumb_retina">When possible, replace thumbnails with Retina versions on supported devices.</label>

<?php }