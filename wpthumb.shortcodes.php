<?php

/**
 * The [img] shortcode is used for resizing image URLs / attachments from within the content
 * 
 * The img shortcode supports all the WP Thumb arguments, for example:
 * 
 * [img 4567 width=400 height=200]
 */

add_shortcode( 'img', 'wpthumb_img_shortcode' );

function wpthumb_img_shortcode( $args ) {

	if ( is_numeric( $args[0] ) ) {

		$attachment_id = $args[0];
		unset( $args[0] );

		return wp_get_attachment_image( $attachment_id, $args );

	} else if ( ! empty( $args ) ) {

		$url = esc_url( $args[0] );
		unset( $args[0] );

		$image = wpthumb( $url, $args );

		list( $width, $height ) = getimagesize( $image );

		return '<img src="' . $image . '" width="' . $width . '" height="' . $height . '" />';
	}
}