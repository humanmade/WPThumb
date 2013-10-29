<?php

/**
 *	Global setting for retina. Either set in media settings, or defined in wp-config.php.
 */
function wpthumb_retina_is_enabled() {
	
	if ( defined('WPTHUMB_RETINA_ENABLED') )
		return (bool) WPTHUMB_RETINA_ENABLED;

	return (bool) apply_filters( 'wpthumb_retina_is_enabled', get_option( 'wpthumb_retina' ) );

}

/**
 * Enqueue Retina-fy scripts.
 */
function wpthumb_retina_scripts() {

	wp_register_script( 'wpthumb_retina', WP_THUMB_URL . 'wpthumb.retina.js', false, false, true );
	
	if ( wpthumb_retina_is_enabled() )
		wp_enqueue_script( 'wpthumb_retina' );

}
add_action( 'wp_enqueue_scripts', 'wpthumb_retina_scripts' );

/**
 * Return the retina multiplier.
 *
 * The return value is the multiplier used when calculating how many times larger the high res image should be compared to the original.
 * Defaults to 2x
 * e.g. can set to 1.5 to allow you server 1.5x images to retina screens for better quality images whilst saving a bit of bandwith.
 * 
 * @return int 
 */
function wpthumb_retina_get_multiplier() {

	return apply_filters( 'wpthumb_retina_multiplier', 2 );

}

/**
 *	Generate the retina src attr. for WPThumb images.
 *	Hooks into wpthumb_action.
 */
function wpthumb_retina_action( $image, $id, $path, $args ) {

	if ( ! wpthumb_retina_is_enabled() )
		return;

	extract( $args );

	// Do these checks again.
	if ( ! file_exists( $path ) || $image->errored() || ! $image_meta = @getimagesize( $image->getCacheFilePath() ) )
		return;

	// If the retina arg is true or the global option is set and the retina arg isn't false
    add_filter( 'wp_get_attachment_image_attributes', $closure = function( $attr, $attachment ) use ( $args, $path, &$closure ) {

     	remove_filter( 'wp_get_attachment_image_attributes', $closure );

     	extract ( $args );

     	// Only continue if we have a width or a height
     	if ( empty( $width ) && empty( $height ) )
		 	 return $attr;

     	// Get the original image with and height
     	list( $orig_width, $orig_height ) = @getimagesize( $path );

     	// Make sure the original is big enough for a retina image
     	// Currently fails if less than 2 times. This has to be done 
     	if ( $orig_width < $width * wpthumb_retina_get_multiplier() || $orig_height < $height * wpthumb_retina_get_multiplier() )
     		return $attr;

     	$args['width'] = $width * wpthumb_retina_get_multiplier();
     	$args['height'] = $height * wpthumb_retina_get_multiplier();

     	unset( $args['retina'] );

     	$retina_image = new WP_Thumb( $path, $args );

     	if ( ! $retina_image->errored() )
     		$attr['data-retina-src'] = $retina_image->returnImage();

     	return $attr;

    }, 10, 2 );

}
add_action( 'wpthumb_action', 'wpthumb_retina_action', 10, 4 );

/**
 *	Add retina image attr to content images on insert
 */
function wpthumb_retina_get_image_html( $html, $id, $caption, $title, $align, $url, $size, $alt = '' ) {

	if ( ! wpthumb_retina_is_enabled() )
		return $html;

	global $_wp_additional_image_sizes;

	if ( in_array( $size, get_intermediate_image_sizes() ) ) {

		// If this is a defined/default image size.

		if ( isset( $_wp_additional_image_sizes[$size] ) ) {
			$args['width']  = (int)  $_wp_additional_image_sizes[$s]['width'];
			$args['height'] = (int)  $_wp_additional_image_sizes[$s]['height'];
			$args['crop']   = (bool) $_wp_additional_image_sizes[$s]['crop'];
		} else {
			$args['width']  = (int)  get_option( $size.'_size_w' );
			$args['height'] = (int)  get_option( $size.'_size_h' );
			$args['crop']   = (bool) get_option( $size.'_size_crop' );
		}

	} elseif ( is_array( $size ) ) {

		// If an array of args.
		$args['width']  = $size['width'] * wpthumb_retina_get_multiplier();
		$args['height'] = $size['height'] * wpthumb_retina_get_multiplier();

	}

    // Only continue if we have a width or a height
    if ( empty( $args['width'] ) && empty( $args['height'] ) )
    	return $html;

    // Get the original image with and height
	list( $orig_width, $orig_height ) = @getimagesize( trailingslashit( wp_upload_dir() ) . get_post_meta( $id, '_wp_attached_file', true ) );

	if ( ! isset( $args['width'] ) )
		$args['width'] = null;

	if ( ! isset( $args['height'] ) )
		$args['height'] = null;

	// Make sure the original is big enough for a retina image
	// If not cropped - dont worry - just return the biggest possible.
	if ( ! empty( $args['crop'] ) && ( $orig_width < $args['width'] * wpthumb_retina_get_multiplier() || $orig_height < $args['height'] * wpthumb_retina_get_multiplier() ) )
		return $html;

	$args['width'] = $args['width'] * wpthumb_retina_get_multiplier();
	$args['height'] = $args['height'] * wpthumb_retina_get_multiplier();

	$retina_image_attr = ' data-retina-src="' . reset( wp_get_attachment_image_src( $id, $args ) ) . '" ';

	wp_enqueue_script( 'wpthumb_retina' );

	return str_replace( '/>', $retina_image_attr . ' />', $html );	;

}
add_filter( 'image_send_to_editor', 'wpthumb_retina_get_image_html', 100, 8 );


/**
 * Add to extended_valid_elements for TinyMCE
 *
 * @param $init assoc. array of TinyMCE options
 * @return $init the changed assoc. array
 */
function wpthumb_retina_change_mce_options( $init ) {

	if ( ! wpthumb_retina_is_enabled() )
		return $init;

    // Command separated string of extended elements
    // I've set it to all - but maybe can modify defaults? If I only set the one I want, doesn't allow any others.
    $ext = 'img[*]';

    // Add to extended_valid_elements if it alreay exists
    if ( isset( $init['extended_valid_elements'] ) ) {
        $init['extended_valid_elements'] .= ',' . $ext;
    } else {
        $init['extended_valid_elements'] = $ext;
    }

    return $init;
}
add_filter( 'tiny_mce_before_init', 'wpthumb_retina_change_mce_options', 100 );