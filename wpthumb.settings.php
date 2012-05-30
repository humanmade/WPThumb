<?php

/**
 * Add the watermark setting field to the media settings page
 */
function wpthumb_watermark_setting() {
	
	register_setting( 'media', 'wpthumb_watermark' );
	add_settings_field( 'wpthumb_watermark', 'Enable watermarking functionality', 'wpthumb_watermark_setting_field', 'media' );

}
add_action( 'admin_init', 'wpthumb_watermark_setting' );

/**
 * Output the watermark setting field
 */
function wpthumb_watermark_setting_field() { ?>

	<input name="wpthumb_watermark" id="wpthumb_watermark" type="checkbox"<?php checked( get_option( 'wpthumb_watermark' ), 'on' ); ?> /> <label for="wpthumb_watermark">Adds the ability to watermark images through the media library.</label>

<?php }