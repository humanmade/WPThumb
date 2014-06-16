<?php

/**
 * The [wpthumb] shortcode is used for resizing image URLs / attachments from within the content
 * 
 * The wpthumb shortcode supports all the WP Thumb arguments, for example:
 * 
 * [wpthumb 4567 width=400 height=200]
 */

add_shortcode( 'wpthumb', 'wpthumb_img_shortcode' );

function wpthumb_img_shortcode( $args ) {

	$args_attrs = array( 'class', 'alt' );
	$attrs = array();

	foreach ( $args_attrs as $att ) {
		if ( isset( $args[$att] ) ) {
			$attrs[$att] = $args[$att];
			unset( $args[$att] );
		}
	}

	if ( is_numeric( $args[0] ) ) {

		$attachment_id = $args[0];
		unset( $args[0] );

		return wp_get_attachment_image( $attachment_id, $args, false, $attrs );

	} else if ( ! empty( $args ) ) {

		$url = esc_url( $args[0] );
		unset( $args[0] );

		$image = wpthumb( $url, $args );

		list( $width, $height ) = getimagesize( $image );

		$attr = '';

		foreach ( $attrs as $a => $value ) {
			$attr .= ' ' . $a . '="' . esc_attr( $value ) . '"';
		}

		return '<img src="' . $image . '" width="' . $width . '" height="' . $height . '"' . $attr . ' />';
	}
}
