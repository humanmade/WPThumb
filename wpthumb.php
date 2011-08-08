<?php
/*
Plugin Name: WPThumb
Plugin URI: https://github.com/humanmade/WPThumb
Description: PHPThumb for WordPress
Author: Human Made Limited
Version: 0.2
Author URI: http://www.humanmade.co.uk/
*/

/**
 * wpthumb_media_form_crop_position function.
 *
 * Adds a back end for selecting the crop position of images.
 *
 * @access public
 * @param array $fields
 * @param array $post
 * @return $post
 */
function wpthumb_media_form_crop_position( $fields, $post ) {

    $current_position = get_post_meta( $post->ID, 'wpthumb_crop_pos', true );

    if ( !$current_position )
    	$current_position = 'center,center';

    $html = '<style>#wpthumb_crop_pos input { margin: 5px; }</style>';
    $html .= '<div id="wpthumb_crop_pos">';
    $html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="left,top" title="Left, Top" ' . checked( 'left,top', $current_position, false ) . '/>';
    $html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="center,top" title="Center, Top" ' . checked( 'center,top', $current_position, false ) . '/>';
    $html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="right,top" title="Right, Top" ' . checked( 'right,top', $current_position, false ) . '/><br/>';
    $html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="left,center" title="Left, Center" ' . checked( 'left,center', $current_position, false ) . '/>';
    $html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="center,center" title="Center, Center"' . checked( 'center,center', $current_position, false ) . '/>';
    $html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="right,center" title="Right, Center" ' . checked( 'right,center', $current_position, false ) . '/><br/>';
    $html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="left,bottom" title="Left, Bottom" ' . checked( 'left,bottom', $current_position, false ) . '/>';
    $html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="center,bottom" title="Center, Bottom" ' . checked( 'center,bottom', $current_position, false ) . '/>';
    $html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="right,bottom" title="Right, Bottom" ' . checked( 'right,bottom', $current_position, false ) . '/>';
    $html .= '</div>';

    $fields[] = array(
    	'label' => __('Crop Position', 'wpthumb'),
    	'input' => 'html',
    	'html' => $html
    );
    return $fields;
}
add_filter( 'attachment_fields_to_edit', 'wpthumb_media_form_crop_position', 10, 2 );


/**
 * wpthumb_media_form_crop_position_save function.
 *
 * Saves crop position in post meta.
 *
 * @access public
 * @param array $post
 * @param array $attachment
 * @return $post
 */
function wpthumb_media_form_crop_position_save( $post, $attachment ){

    if ( $attachment['wpthumb_crop_pos'] == 'center,center' )
    	delete_post_meta( $post['ID'], 'wpthumb_crop_pos' );
    else
    	update_post_meta( $post['ID'], 'wpthumb_crop_pos', $attachment['wpthumb_crop_pos'] );

    return $post;
}
add_filter( 'attachment_fields_to_save', 'wpthumb_media_form_crop_position_save', 10, 2);

/**
 * Resizes a given image (local).
 *
 * @param mixed absolute path to the image
 * @param int $width.
 * @param int $height.
 * @param bool $crop. (default: false)
 * @return (string) url to the image
 */
function wpthumb( $url, $args = array() ) {

    if ( !class_exists( 'PhpThumbFactory' ) )
    	include_once( dirname( __FILE__ ) . '/phpthumb/src/ThumbLib.inc.php' );

    if ( !class_exists( 'phpThumbFactory' ) && error_log( 'phpThumbFactory class not found.' ) )
        return $url;
	
    // Check if is using legacy args
    if ( is_numeric( $args ) )
    	$legacy_args = array_combine( array_slice( array( 'width', 'height', 'crop', 'resize' ), 0, count( array_slice( func_get_args(), 1 ) ) ), array_slice( func_get_args(), 1 ) );

    if ( isset( $legacy_args ) && $legacy_args )
    	$args = wpthumb_parse_args( $legacy_args );

    else
    	$args = wpthumb_parse_args( $args );

    extract( $args );

    // If the url is blank, use the default
    if ( empty( $url ) && $default )
    	$url = $default;

    elseif ( !$url && !$default )
    	return '';

    // Sort out the watermark args
    if ( isset( $watermark_options['mask'] ) && $watermark_options['mask'] ) {
    	$wpthumb_wm_defaults = array( 'padding' => 0, 'position' => 'cc', 'pre_resize' => false );
    	$watermark_options = wp_parse_args( $watermark_options, $wpthumb_wm_defaults );
    }

    $width = (int) $width;
    $height = (int) $height;

    // If the file already matches (or is less than) the resize features, just return the url
    if ( ( !defined( 'WPTHUMB_FORCE_ENABLED' ) || defined( 'WPTHUMB_FORCE_ENABLED' ) && !WPTHUMB_FORCE_ENABLED ) && !$args['custom'] && function_exists( 'getimagesize' ) && strpos( $url, ABSPATH ) === 0 && file_exists( $url ) && ( $dimensions = getimagesize( $url ) ) && $dimensions[0] <= $width && $dimensions[1] <= $height )
    	return wpthumb_get_file_url_from_file_path( $url );

    $file_path = wpthumb_get_file_path_from_file_url( $url );
    $new_filepath = wpthumb_calculate_image_cache_file_path( $file_path, $args );

    $ext = '.' . end( explode( '.', $new_filepath ) );

    if ( $new_filepath )
    	wpthumb_create_dir_for_file( $new_filepath );

    // Only create the resized version if one hasn't already been created - or $cached is set to false.
    if ( !file_exists( $new_filepath )  || $cache === false || $ext == '.gif') :

    	// Up the php memory limit
    	@ini_set( 'memory_limit', '256M' );

    	// Create the image
    	try {
    		$thumb = phpThumbFactory::create( $file_path, array( 'jpegQuality' => $jpeg_quality ) );

    	} catch ( Exception $e ) {
    		return wpthumb_get_file_url_from_file_path( $file_path );

    	}

    	$thumb = apply_filters( 'wpthumb_image_filter', $thumb, $args );

    	// Convert gif images to png before resizing
    	if ( $ext == '.gif' ) :

    		// Don't resize animated gifs and the animations will be broken
    		if ( $args['resize_animations'] !== true && wpthumb_is_gif_animated( $file_path ) )
    			return wpthumb_get_file_url_from_file_path( $file_path );

    		// Save the converted image
    		$thumb->save( $new_filepath . '.jpg', 'jpg' );

    		unset( $thumb );

    		// Pass the new file back through the function so they are resized
    		return wpthumb( $new_filepath . '.jpg', $args );

    	endif;

    	// Watermarking (pre resizing)
    	if ( isset( $watermark_options['mask'] ) && $watermark_options['mask'] && isset( $watermark_options['pre_resize'] ) && $watermark_options['pre_resize'] === true ) {
    		$thumb->resize( 99999, 99999 );
    		$thumb->createWatermark( $watermark_options['mask'], $watermark_options['position'], $watermark_options['padding'] );
    	}

    	// Cropping
    	
    	if ( $crop === true && $resize === true ) :
    	  if ( $crop_from_position && count( $crop_from_position ) == 2 && method_exists( $thumb, 'adaptiveResizeFromPoint' ) && empty( $background_fill ) ) {
    	  	$thumb->adaptiveResizeFromPoint( $width, $height, $crop_from_position[0], $crop_from_position[1] );
		
    	  }
    	  
    	  elseif( $background_fill == 'solid' && $thumb->canBackgroundFillSolidColorWithResize( $width, $height ) ) {
			$thumb->resize( $width, $height );
    	  	$thumb->backgroundFillColorAuto( $width, $height );
    	  }
			
		  else {
		  	$thumb->adaptiveResize( $width, $height );
		  }
		  	
    	elseif ( $crop === true && $resize === false ) :
    	  $thumb->cropFromCenter( $width, $height );
		
    	else :
    	  $thumb->resize( $width, $height );
		
    	endif;

    	// Watermarking (post resizing)
    	if ( isset( $watermark_options['mask'] ) && $watermark_options['mask'] && isset( $watermark_options['pre_resize'] ) && $watermark_options['pre_resize'] === false )
    		$thumb->createWatermark($watermark_options['mask'], $watermark_options['position'], $watermark_options['padding']);

    	$thumb->save( $new_filepath );

    	// Destroy the image
    	unset( $thumb );

    endif;

    if ( $return == 'path' )
    	return $new_filepath;

    return wpthumb_get_file_url_from_file_path( $new_filepath );
}

/**
 * Calculates ful image path and new filename from an image path or src
 *
 * @param string $src
 * @param mixed $args
 * @return string - full image path
 */
function wpthumb_calculate_image_cache_file_path( $src, $args ) {

    $path = wpthumb_get_file_path_from_file_url( $src );

    return wpthumb_calculate_image_cache_dir( $path, $args ) . '/' . wpthumb_calculate_image_cache_filename( end( explode( '/', $path ) ), $args );
}

/**
 * wpthumb_calculate_image_cache_dir function.
 *
 * @access public
 * @param mixed $path
 * @param mixed $args. (default: null)
 * @return null
 */
function wpthumb_calculate_image_cache_dir( $path, $args = null ) {

    $original_filename = end( explode( '/', $path ) );

    // If the image was remote, we want to store them in the remote images folder, not it's name
    if ( strpos( $original_filename, '0_0_resize' ) === 0 )
    	$original_filename = end( explode( '/', str_replace( '/' . $original_filename, '', $path ) ) );
	
	$parts = explode( '.', $original_filename );
	array_pop($parts);
	$filename_nice = implode( '_', $parts );
	
    $upload_dir = wp_upload_dir();
    $upload_dir_base = $upload_dir['basedir'];
    
    if ( strpos( $path, $upload_dir_base ) === 0 ) :
    	$new_dir = $upload_dir_base . '/cache' . $upload_dir['subdir'] . '/' . $filename_nice;

    else :
    	$new_dir = $upload_dir_base . '/cache';

    endif;

    $new_dir = str_replace( '/cache/cache', '/cache', $new_dir );

    return $new_dir;
}

/**
 * Calcualtes the cached filename (not full path) from an image path
 *
 * @param string $path
 * @param mixed $args
 * @return string - filename
 */
function wpthumb_calculate_image_cache_filename( $filename, $args ) {

    $ext = strtolower( end( explode( '.', $filename ) ) );
    
    return crc32( serialize( $args ) ) . '.' . $ext;
}

/**
 * wpthumb_create_dir_for_file function.
 *
 * @access public
 * @param mixed $path
 * @return null
 */
function wpthumb_create_dir_for_file( $path ) {

    $filename = end( explode( '/', $path ) );
    $dir = str_replace( $filename, '', $path );
	
	wp_mkdir_p( $dir );

    return;
}

/**
 * Create a image from a path with $size args
 *
 * @param string $image_path
 * @param mixed $args - wp_args
 * @return string - image URL
 */
function wpthumb_image_from_args( $image_path, $args ) {

    $args = wp_parse_args( $args );

    extract( $args );

    $image = wpthumb( $image_path, $args );

    $crop = (bool) ( empty( $crop ) ) ? false : $crop;

    if ( $image_meta = getimagesize( wpthumb_get_file_path_from_file_url( $image ) ) ) :

        $html_width = $image_meta[0];
        $html_height = $image_meta[1];

    else :
    	$html_width = $html_height = false;

    endif;

    return array( $image, $html_width, $html_height, true );

}

/**
 * wpthumb_post_image function.
 *
 * @access public
 * @param mixed $null
 * @param mixed $id
 * @param mixed $args
 * @return null
 */
function wpthumb_post_image( $null, $id, $args ) {

    if ( ( !strpos( (string) $args, '=' ) ) && !( is_array( $args ) && isset( $args[0] ) && $args[0] == $args[1] ) ) {

    	// Convert keyword sizes to heights & widths. Will still use file wordpress saved unless you change the thumbnail dimensions.
    	// TODO Might be ok to delete as I think it has been duplicated.  Needs testing.
    	if ( $args == 'thumbnail' )
    		$new_args = array( 'width' => get_option('thumbnail_size_w'), 'height' => get_option('thumbnail_size_h'), 'crop' => get_option('thumbnail_crop') );
    	
    	elseif ( $args == 'medium' )
    		$new_args = array( 'width' => get_option('medium_size_w'), 'height' => get_option('medium_size_h') );
    	
    	elseif ( $args == 'large' )
    		$new_args = array( 'width' => get_option('large_size_w'), 'height' => get_option('large_size_h') );
    	
    	elseif ( is_string( $args ) && ( $args != ( $new_filter_args = apply_filters( 'wpthumb_create_args_from_size', $args ) ) ) )
    		$new_args = $new_filter_args;
    	
    	elseif ( is_array( $args ) )
    		$new_args = $args;
    
    	else
    		$new_args = null;

    	if ( !$new_args )
	    	return $null;

    	$args = $new_args;

    }

    $args = wp_parse_args( $args );

    if ( isset( $args[0] ) && $args[0] )
    	$args['width'] = $args[0];

    elseif ( isset( $args['width'] ) )
    	$args['width'] = $args['width'];

    else
    	$args['width'] = null;

    if ( isset( $args[1] ) && $args[1] )
    	$args['height'] = $args[1];

    elseif ( isset( $args['height'] ) )
    	$args['height'] = $args['height'];

    else
    	$args['height'] = null;

    $args['original_size'] = ( isset( $args['original_size'] ) && $args['original_size'] ) ? $args['original_size'] : 'thumbnail';

    if ( empty( $args['crop_from_position'] ) )
    	 $args['crop_from_position'] = get_post_meta( $id, 'wpthumb_crop_pos', true );

    $args = wpthumb_parse_args( $args );

    if ( $args['original_size'] == 'thumbnail' && $args['crop_from_position'] == array( 'center', 'center' ) ) {

    	$intermediate = image_get_intermediate_size( $id, 'thumbnail' );
    	$path = wpthumb_get_file_path_from_file_url( $intermediate['url'] );

    	if ( wpthumb_is_image_smaller_than_dimensions( $path, $args['width'], $args['height'] ) )
			$path = null;
    }

    if ( empty( $path ) )
    	$path = get_attached_file( $id );

    if ( file_exists( $path ) && !is_dir( $path ) && !$args['default'] )
    	return wpthumb_image_from_args( $path, $args );

    else
    	return $null;
}
add_filter( 'image_downsize', 'wpthumb_post_image', 99, 3 );

/**
 * wpthumb_parse_args function.
 *
 * @access public
 * @param mixed $args
 * @return null
 */
function wpthumb_parse_args( $args ) {

    $arg_defaults = array(
    	'width' 				=> 0,
    	'height'				=> 0,
    	'crop'					=> false,
    	'crop_from_position' 	=> 'center,center',
    	'resize'				=> true,
    	'watermark_options' 	=> array(),
    	'cache'					=> true,
    	'skip_remote_check' 	=> false,
    	'default'				=> null,
    	'jpeg_quality' 			=> 80,
    	'resize_animations' 	=> true,
    	'return' 				=> 'url',
    	'custom' 				=> false,
    	'background_fill'		=> null
    );

    $args = wp_parse_args( $args, $arg_defaults );
    $new_args = array();

    if ( $args['width'] == 'thumbnail' )
    	$new_args = array( 'width' => get_option('thumbnail_size_w'), 'height' => get_option('thumbnail_size_h'), 'crop' => get_option('thumbnail_crop') );

    elseif ( $args['width'] == 'medium' )
    	$new_args = array( 'width' => get_option('medium_size_w'), 'height' => get_option('medium_size_h') );

    elseif ( $args['width'] == 'large' )
    	$new_args = array( 'width' => get_option('large_size_w'), 'height' => get_option('large_size_h') );

    elseif ( is_string( $args['width'] ) )
    	$new_args = apply_filters( 'wpthumb_create_args_from_size', $args );

    elseif ( is_array( $args['width'] ) )
    	$new_args = $args;

    $args = wp_parse_args( $new_args, $args );

    // Cast some args
    $args['crop'] = (bool) $args['crop'];
    $args['resize'] = (bool) $args['resize'];
    $args['cache'] = (bool) $args['cache'];

    // Format the crop from position arg
    if ( is_string( $args['crop_from_position'] ) )
    	$args['crop_from_position'] = explode( ',', $args['crop_from_position'] );

    return $args;

}

/**
 * wpthumb_get_file_path_from_file_url function.
 *
 * @access public
 * @param mixed $url
 * @return null
 */
function wpthumb_get_file_path_from_file_url( $url ) {

    $upload_dir = wp_upload_dir();

    if ( is_multisite() && !is_main_site() )
		return str_replace( get_bloginfo('wpurl') . '/files', $upload_dir['basedir'], $url );

    else
    	return str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
}

/**
 * wpthumb_get_file_url_from_file_path function.
 *
 * @access public
 * @param mixed $url
 * @return null
 */
function wpthumb_get_file_url_from_file_path( $url ) {

    if ( is_multisite() && !is_main_site() ) {
    	$upload_dir = wp_upload_dir();
    	return str_replace( $upload_dir['basedir'], get_bloginfo('wpurl') . '/files', $url );

    } else {
    	return str_replace( ABSPATH, get_bloginfo('wpurl') . '/', $url );

    }

}

/**
 * Checks if an image (gif) is animated
 *
 * @param string $image_path
 * @return bool
 */
function wpthumb_is_gif_animated( $filename ) {

        $filecontents = file_get_contents( $filename );

        $str_loc = $count = 0;

        while ( $count < 2 ) {

    		$where1 = strpos( $filecontents, "\x00\x21\xF9\x04" , $str_loc );

    		if ( $where1 === false ) {
				break;

    		} else {

				$str_loc = $where1 + 1;
    		    $where2 = strpos( $filecontents, "\x00\x2C", $str_loc );

    		    if ( $where2 === false ) {
					break;

    		    } else {

    		    	if ( $where1 + 8 == $where2 )
						$count++;

    		    	$str_loc=$where2+1;
    		    }
    		}
        }

        if ( $count > 1 )
			return true;

    	return false;

}

/**
 * Returns all images attached to a given post
 *
 * @param object $post. (default: global $post)
 * @param string $return. (default: 'file' [file, array])
 * @return array
 */
function wpthumb_get_attached_images( $post_id = null, $return = 'file' ) {

    if ( is_null( $post_id ) )
    	$post_id = get_the_id();

	if ( is_object( $post ) && !empty( $post->ID ) )
	    $post_id = $post->ID;

    if ( !is_numeric( $post_id ) )
    	return false;

    $images = array();

    foreach( (array) get_children( array( 'post_parent' => $post_id, 'post_type' => 'attachment', 'orderby' => 'menu_order', 'order' => 'ASC' ) ) as $attachment ) {

    	if ( !wp_attachment_is_image( $attachment->ID ) || !file_exists( get_attached_file( $attachment->ID ) ) )
    		continue;

    	if ( $return === 'file' )
    		$images[] = get_attached_file( $attachment->ID );

    	elseif ( $return === 'array' )
    		$images[] = $attachment;

    }

    return $images;
}

/**
 * Checks if an image is smaller than the especified dimentions
 *
 * @param string $path
 * @param int $width
 * @param int $height
 * @param bool $both. (default: true)
 * @return void
 */
function wpthumb_is_image_smaller_than_dimensions( $path, $width, $height, $both = true ) {

    if ( !file_exists( $path ) )
    	return false;

    $dimensions = @getimagesize( $path );

    if ( $both == true && ( $dimensions[0] < $width || $dimensions[1] < $height ) )
    	return true;

	return false;

}

/**
 * wpthumb_delete_cache_for_file function.
 *
 * @access public
 * @param mixed $file
 * @return null
 */
function wpthumb_delete_cache_for_file( $file ) {

    wpthumb_rmdir_recursive( wpthumb_calculate_image_cache_dir( $file ) );

    return $file;

}
add_filter( 'wp_delete_file', 'wpthumb_delete_cache_for_file' );

/**
 * wpthumb_wm_check_for_submitted function.
 *
 * @access public
 * @return null
 */
function wpthumb_wm_check_for_submitted() {

    if ( !empty( $_POST['wpthumb_wm_watermark_position'] ) ) {

    	//is_multiple check
    	preg_match( '/multiple=([A-z0-9_][^&]*)/', $_POST['_wp_http_referer'], $multiple_matches );
    	$multiple = $multiple_matches[1];

    	preg_match( '/button=([A-z0-9_][^&]*)/', $_POST['_wp_http_referer'], $matches );
    	$button_id = $matches[1];

    	// If the custom button was pressed
    	if ( is_array( $_POST[$button_id] ) ) {

    		$attach_id = key( $_POST[$button_id] );
    		$attach_thumb_url = wp_get_attachment_thumb_url( $attach_id );

    		update_post_meta( $attach_id, 'use_watermark', $_POST['wpthumb_wm_use_watermark'][$attach_id] );
    		update_post_meta( $attach_id, 'wpthumb_wm_position', $_POST['wpthumb_wm_watermark_position'][$attach_id] );
    		update_post_meta( $attach_id, 'wpthumb_wm_padding', (int) $_POST['wpthumb_wm_watermark_padding'][$attach_id] );

    	}
    }
}
add_action( 'admin_head-media-upload-popup', 'wpthumb_wm_check_for_submitted' );

/**
 * wpthumb_wm_add_scripts function.
 *
 * @access public
 * @return null
 */
function wpthumb_wm_add_scripts() {

    if ( !isset( $_GET['button'] ) || !$_GET['button'] )
    	return; ?>

    <script type="text/javascript">

    	jQuery( '.add-watermark' ).live( 'click', function( e ) {
    		e.preventDefault();
    		addWmPane = jQuery(this).closest('tr').next('tr');
    		jQuery(addWmPane).show();
    	} );

    	jQuery(".wm-watermark-options a.preview-watermark").live('click', function(e) {
    		e.preventDefault();
    		WMCreatePreview( jQuery(this).closest(".wm-watermark-options") );
    	} );

    	jQuery(".wm-watermark-options a.cancel-watermark").live('click', function(e) {
    		e.preventDefault();
    		jQuery(this).closest(".wm-watermark-options").find("input.wpthumb_wm_use_watermark").removeAttr('checked');
    		jQuery(this).closest("tr").hide();
    	});

    	function WMCreatePreview( optionsPane ) {
    		position = jQuery(optionsPane).find("select.wpthumb_wm_watermark_position").val();
    		padding = jQuery(optionsPane).find("input.wpthumb_wm_watermark_padding").val();

    		//show loading
    		jQuery(optionsPane).next(".wm-watermark-preview").html('<span class="wm-loading">Generating Preview...</span>');

    		if ( typeof(WMCreatePreviewXHR) != 'undefined' )
    			WMCreatePreviewXHR.abort();

    		WMCreatePreviewXHR = jQuery.get(jQuery(optionsPane).find("a.preview-watermark").attr("href"), { action: 'wpthumb_wm_watermark_preview_image', position: position, padding: padding, image_id: jQuery(optionsPane).attr('rel') },
    		function(data){
    			jQuery(optionsPane).next(".wm-watermark-preview").html(data).show();
    		});
    	}

    </script>

    <style>
    	/* .A1B1 input[type=button] { display: none; } */
    	.watermark { border: 1px solid #1B77AD; -moz-border-radius: 5px; -webkit-border-radius: 5px; border-radius: 5px; overflow: hidden; padding: 0 10px; }
    	.wm-watermark-options { width: 230px; font-size: 11px; float: left; }
    	.wm-watermark-preview { width: 205px; float: left; text-align: center; line-height: 180%; margin-top: 20px; }
    	.wm-watermark-options label { font-size: 11px; }
    	.wm-watermark-preview img { padding: 3px; border: 1px solid #a1a1a1; }
    	.wm-watermark-preview a {font-size: 11px; text-decoration: none; }
    	.wm-loading { line-height: 16px; text-align: center; width: 120px; background: url(<?php echo get_bloginfo('url') . '/wp-admin/images/loading.gif' ?>) no-repeat; padding-left: 20px; padding-top: 1px; padding-bottom: 2px; font-size: 11px; color: #999; }
    </style>

<?php }
add_action( 'admin_head-media-upload-popup', 'wpthumb_wm_add_scripts' );

/**
 * wpthumb_wm_add_watermark_button function.
 *
 * @access public
 * @param mixed $form_fields
 * @param mixed $media
 * @return null
 */
function wpthumb_wm_add_watermark_button( $form_fields, $media ) {

    if ( !isset( $form_fields['buttons'] ) || !strpos( $form_fields['buttons']['tr'], 'Set as Post Image' ) )
    	return $form_fields;

    $button = '<a class="button add-watermark" rel="' . $media->ID . '">' . (wpthumb_wm_image_has_watermark($media->ID) ? 'Edit' : 'Add') .' Watermark</a>';
    $form_fields['buttons']['tr'] = substr( $form_fields['buttons']['tr'], 0, strlen($form_fields['buttons']['tr']) - 11) . $button . '</td></tr>';
    $form_fields['buttons']['tr'] .= '
    	<tr style="display:none"><td></td><td>
    		<div class="watermark">
    		<div rel="' . $media->ID . '" class="wm-watermark-options">
    			<p><label>
    				<input class="wpthumb_wm_use_watermark" ' . (wpthumb_wm_image_has_watermark( $media->ID ) ? 'checked="checked"' : '') . ' type="checkbox" name="wpthumb_wm_use_watermark[' . $media->ID . ']" />
    				<strong>Apply watermark</strong>
    			</label></p>
    			<p><label>Positition</label>
    				<select class="wpthumb_wm_watermark_position" name="wpthumb_wm_watermark_position[' . $media->ID . ']">
    					<option ' . ( wpthumb_wm_position($media->ID) == 'top-left' ? 'selected="selected"' : '' ) .' value="top-left">Top Left</option>
    					<option ' . ( wpthumb_wm_position($media->ID) == 'top-right' ? 'selected="selected"' : '' ) .' value="top-right">Top Right</option>
    					<option ' . ( wpthumb_wm_position($media->ID) == 'bottom-left' ? 'selected="selected"' : '' ) .' value="bottom-left">Bottom Left</option>
    					<option ' . ( wpthumb_wm_position($media->ID) == 'bottom-left' ? 'selected="selected"' : '' ) .' value="bottom-right">Bottom Right</option>
    				</select>
    			</p>
    			<p><label>Padding</label>
    				<input class="wpthumb_wm_watermark_padding" type="text" value="' . wpthumb_wm_padding($media->ID) . '" style="width:30px" name="wpthumb_wm_watermark_padding[' . $media->ID . ']">px
    			</p>
    			<p><small>Padding (or gutter) is the space that the watermark appears from the edge of the image</small><br /></p>
    			<p class="clear clearfix">
    				<input type="submit" name="afp_post_image[' . $media->ID . ']" class="button-primary" value="Add Watermark"> <a href="' . str_replace( ABSPATH, get_bloginfo('url') . '/', dirname( __FILE__ )) . '/watermark-actions.php' . '" class="button preview-watermark">Preview</a> | <a href="" class="cancel-watermark">Cancel</a>
    			</p>
    		</div>
    		<div class="wm-watermark-preview">
    		</div>
    		</div>
    	</td></tr>';
    return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'wpthumb_wm_add_watermark_button', 100, 2 );

/**
 * wpthumb_wm_watermark_preview_image function.
 *
 * @access public
 * @param mixed $position
 * @param mixed $padding
 * @param mixed $image_id
 * @return string
 */
function wpthumb_wm_watermark_preview_image( $position, $padding, $image_id ) {

    $image = get_attached_file($image_id);
    $watermark = array();
    $watermark['mask'] = get_template_directory() . '/images/watermark.png';

    if ( $position == 'top-left' )
    	$watermark['position'] = 'lt';

    if ( $position == 'top-right' )
    	$watermark['position'] = 'rt';

    if ( $position == 'bottom-left' )
    	$watermark['position'] = 'lb';

    if ( $position == 'bottom-right' )
    	$watermark['position'] = 'rb';

    $watermark['padding'] = (int) $padding;
    $watermark['pre_resize'] = true;

    return '<img src="' . wpthumb( $image, 200, 0, false, true, $watermark, false ) . '" /><a target="_blank" href="' . wpthumb( $image, 1000, 0, false, true, $watermark, false ) . '">View Large</a>';
}

/**
 * wpthumb_wm_image_has_watermark function.
 *
 * @access public
 * @param mixed $image_id
 * @return null
 */
function wpthumb_wm_image_has_watermark( $image_id ) {
    return (bool) get_post_meta( $image_id, 'use_watermark', true );
}

/**
 * wpthumb_wm_position function.
 *
 * @access public
 * @param mixed $image_id
 * @return null
 */
function wpthumb_wm_position( $image_id ) {
    return get_post_meta( $image_id, 'wpthumb_wm_position', true );
}

/**
 * wpthumb_wm_padding function.
 *
 * @access public
 * @param mixed $image_id
 * @return null
 */
function wpthumb_wm_padding( $image_id ) {
    return (int) get_post_meta( $image_id, 'wpthumb_wm_padding', true );
}

/**
 * wpthumb_wm_get_options function.
 *
 * @access public
 * @param mixed $id
 * @return array
 */
function wpthumb_wm_get_options( $id ) {

    if ( !wpthumb_wm_image_has_watermark( $id ) )
    	return array();

    $options['mask'] = get_template_directory() . '/images/watermark.png';
    $options['padding'] = wpthumb_wm_padding($id);
    $position = wpthumb_wm_position( $id );

    if ( $position == 'top-left' )
    	$options['position'] = 'lt';

    if ( $position == 'top-right' )
    	$options['position'] = 'rt';

    if ( $position == 'bottom-left' )
    	$options['position'] = 'lb';

    if ( $position == 'bottom-right' )
    	$options['position'] = 'rb';

    $options['pre_resize'] = true;

    return $options;
}

/**
 * Removes a dir tree. I.e. recursive rmdir
 *
 * @param string $dir
 * @return bool - success / failure
 */
function wpthumb_rmdir_recursive( $dir ) {

    if ( !is_dir( $dir ) )
		return false;

	$dir = trailingslashit( $dir );

    $handle = opendir( $dir );

    while ( false !== ( $file = readdir( $handle ) ) ) {

        if ( $file == '.' && $file == '..' ) {
        	continue;

        	$path = $dir . $file;

        	if ( is_dir( $path ) )
        		wpthumb_rmdir_recursive( $path );

        	else
        		unlink( $path );

        }
    }

    closedir( $handle );

    rmdir( $dir );

    return $result;

}

/**
 * wpthumb_errors function.
 *
 * @access public
 * @return null
 */
function wpthumb_errors() {

   	$dir_upload = wp_upload_dir();
   	$dir_upload = $dir_upload['path'];

    if ( file_exists( $dir_upload ) && !is_writable( $dir_upload ) )
    	echo '<div id="wpthumb-warning" class="updated fade"><p><strong>' . __( 'WPThumb has detected a problem.', 'wpthumb' ) . '</strong> ' . sprintf( __( 'The directory <code>%s</code> is not writable.', 'wpthumb' ), $dir_upload ) . '</p></div>';
}
add_action( 'admin_notices', 'wpthumb_errors' );

function wpthumb_test() {
	
	$test_images_dir = dirname( __FILE__ ) . '/test-images';

	?>
	<style>
		body{ background: pink }
	</style>
	
	<h2>Auto Background Fill</h2>
	<table>
	
		<thead>
			<th>Original Image</th>
			<th>Non-Padded Adaptive Resize</th>
			<th>Padded Resize</th>
			<th>Arguments</th>
		</thead>
		
		<tr>
			<td>
				<?php $memory_usage = memory_get_usage(); ?>
				<img src="<?php echo wpthumb( $test_images_dir . '/white.jpeg', 'width=500&height=200&cache=0' ) ?>" /><br />
				Memory Usage: <?php echo number_format( ( memory_get_peak_usage() - $memory_usage ) / 1024 / 1024, 2 ) ?>MB
			</td>
			<td>
				<?php $memory_usage = memory_get_usage(); ?>
				<img src="<?php echo wpthumb( $test_images_dir . '/white.jpeg', 'width=500&height=200&crop=1&cache=0' ) ?>"><br />
				Memory Usage: <?php echo number_format( ( memory_get_peak_usage() - $memory_usage ) / 1024 / 1024, 2 ) ?>MB
			</td>		
				
			<td>
				<?php $memory_usage = memory_get_usage(); ?>
				<img src="<?php echo wpthumb( $test_images_dir . '/white.jpeg', 'width=500&height=200&crop=1&background_fill=solid&cache=0' ) ?>"><br />
				Memory Usage: <?php echo number_format( ( memory_get_peak_usage() - $memory_usage ) / 1024 / 1024, 2 ) ?>MB
			</td>
		</tr>
		
		<tr>
			<td>
				<?php $memory_usage = memory_get_usage(); ?>
				<img src="<?php echo wpthumb( $test_images_dir . '/google.png', 'width=100&height=100&cache=0' ) ?>" /><br />
				Memory Usage: <?php echo number_format( ( memory_get_peak_usage() - $memory_usage ) / 1024 / 1024, 2 ) ?>MB
			</td>
			<td>
				<?php $memory_usage = memory_get_usage(); ?>
				<img src="<?php echo wpthumb( $test_images_dir . '/google.png', 'width=100&height=100&crop=1&cache=0' ) ?>"><br />
				Memory Usage: <?php echo number_format( ( memory_get_peak_usage() - $memory_usage ) / 1024 / 1024, 2 ) ?>MB
			</td>		
				
			<td>
				<?php $memory_usage = memory_get_usage(); ?>
				<img src="<?php echo wpthumb( $test_images_dir . '/google.png', 'width=100&height=100&crop=1&background_fill=solid&cache=0' ) ?>"><br />
				Memory Usage: <?php echo number_format( ( memory_get_peak_usage() - $memory_usage ) / 1024 / 1024, 2 ) ?>MB
			</td>
		</tr>
		
		<tr>
			<td><img src="<?php echo wpthumb( $test_images_dir . '/gradient-horizontal.jpg', 'width=0&height=100&cache=0' ) ?>"></td>
			<td><img src="<?php echo wpthumb( $test_images_dir . '/gradient-horizontal.jpg', 'width=500&height=100&crop=1&cache=0' ) ?>"></td>			
			<td><img src="<?php echo wpthumb( $test_images_dir . '/gradient-horizontal.jpg', 'width=500&height=100&crop=1&background_fill=solid&cache=0' ) ?>"></td>
		</tr>
		
		<tr>
			<td><img src="<?php echo wpthumb( $test_images_dir . '/gradient-vertical.jpg', 'width=100&height=500&cache=0' ) ?>"></td>
			<td><img src="<?php echo wpthumb( $test_images_dir . '/gradient-vertical.jpg', 'width=100&height=500&crop=1&cache=0' ) ?>"></td>			
			<td><img src="<?php echo wpthumb( $test_images_dir . '/gradient-vertical.jpg', 'width=100&height=500&crop=1&background_fill=solid&cache=0' ) ?>"></td>
		</tr>
		
		<tr>
			<td><img src="<?php echo wpthumb( $test_images_dir . '/photo.png', 'width=100&height=0&crop=1&cache=0' ) ?>"></td>
			<td><img src="<?php echo wpthumb( $test_images_dir . '/photo.png', 'width=500&height=100&crop=1&cache=0' ) ?>"></td>			
			<td><img src="<?php echo wpthumb( $test_images_dir . '/photo.png', 'width=500&height=100&crop=1&background_fill=solid&cache=0' ) ?>"></td>
		</tr>
	</table>	

	<?php
	exit;
}

if( isset( $_GET['wpthumb_test'] ) )
	add_action( 'init', 'wpthumb_test' );