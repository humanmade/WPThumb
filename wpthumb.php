<?php

/*
Plugin Name: WP Thumb
Plugin URI: https://github.com/humanmade/WPThumb
Description: phpThumb for WordPress
Author: Human Made Limited
Version: 0.5
Author URI: http://www.hmn.md/
*/

/*  Copyright 2011 Human Made Limited  (email : hello@humanmade.co.uk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class WP_Thumb {

	private $args;
	private $file_path;

	function __construct( $file_path = null, $args = array() ) {

		if ( !class_exists( 'PhpThumbFactory' ) )
	    	include_once( dirname( __FILE__ ) . '/phpthumb/src/ThumbLib.inc.php' );

		if( $file_path )
			$this->setFilePath( $file_path );

		$this->setArgs( $args );

		if( $file_path && ! file_exists( $this->getCacheFilePath() ) )
			$this->generateCacheFile();

	}

	public function setFilePath( $file_path ) {

		$upload_dir = wp_upload_dir();

		if( strpos( $file_path, ABSPATH ) === 0 ) {
			$this->file_path = $file_path;
			return;
		}

		if( strpos( $file_path, $upload_dir['baseurl'] ) !== false )
    		$this->file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_path );

    	else
    		$this->file_path = str_replace( get_bloginfo( 'url' ) . '/', ABSPATH, $file_path );

	}

	public function setArgs( $args ) {

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

    	if ( $args['width'] === 'thumbnail' )
    		$new_args = array( 'width' => get_option('thumbnail_size_w'), 'height' => get_option('thumbnail_size_h'), 'crop' => get_option('thumbnail_crop') );

    	elseif ( $args['width'] === 'medium' )
    		$new_args = array( 'width' => get_option('medium_size_w'), 'height' => get_option('medium_size_h') );

    	elseif ( $args['width'] === 'large' )
    		$new_args = array( 'width' => get_option('large_size_w'), 'height' => get_option('large_size_h') );

    	elseif ( is_string( $args['width'] ) && $args['width'] )
    		$new_args = apply_filters( 'wpthumb_create_args_from_size', $args );

    	elseif ( is_array( $args['width'] ) )
    		$new_args = $args;

    	$args = wp_parse_args( $new_args, $args );

    	// Cast some args
    	$args['crop']	= (bool) $args['crop'];
    	$args['resize'] = (bool) $args['resize'];
    	$args['cache'] 	= (bool) $args['cache'];
		$args['width'] 	= (int) $args['width'];
		$args['height'] = (int) $args['height'];

    	// Format the crop from position arg
    	if ( is_string( $args['crop_from_position'] ) )
    		$args['crop_from_position'] = explode( ',', $args['crop_from_position'] );

		// Sort out the watermark args
	    if ( ! empty( $args['watermark_options']['mask'] ) ) {
    		$wpthumb_wm_defaults = array( 'padding' => 0, 'position' => 'cc', 'pre_resize' => false );
    		$args['watermark_options'] = wp_parse_args( $args['watermark_options'], $wpthumb_wm_defaults );
	    }

    	$this->args = $args;

	}

	public function getFilePath() {

		if( strpos( $this->file_path, '/' ) === 0 && ! file_exists( $this->file_path ) && $this->args['default'] )
			$this->file_path = $this->args['default'];

		return $this->file_path;
	}

	public function getArgs() {
		return $this->args;
	}

	public function getFileExtension() {

		$path = $this->getFilePath();

		$ext = strtolower( end( explode( '.', $path ) ) );

		// Remove a query string if there is one
    	$ext = reset( explode( '?', $ext ) );

    	if ( strlen( $ext ) > 4 ) {
    		// Seems like we dont have an ext, lets guess at JPG
    		// TODO this isn't very nice
			$ext = 'jpg';
    	}

		return $ext;

	}

	public function getCacheFilePath() {

		$path = $this->getFilePath();
		
		if ( !$path )
			return '';

	    return $this->getCacheFileDirectory() . '/' . $this->getCacheFileName();

	}

	public function getCacheFileDirectory() {

		$path = $this->getFilePath();
		if ( !$path )
			return '';

		$original_filename = end( explode( '/', $this->getFilePath() ) );

    	// If the image was remote, we want to store them in the remote images folder, not it's name
    	if ( strpos( $original_filename, '0_0_resize' ) === 0 )
    		$original_filename = end( explode( '/', str_replace( '/' . $original_filename, '', $this->getFilePath() ) ) );

		$parts = explode( '.', $original_filename );

		array_pop( $parts );

		$filename_nice = implode( '_', $parts );

    	$upload_dir = wp_upload_dir();

    	if ( strpos( $this->getFilePath(), $upload_dir['basedir'] ) === 0 ) :
    		$new_dir = $upload_dir['basedir'] . '/cache' . $upload_dir['subdir'] . '/' . $filename_nice;

    	else :
    		$parts = parse_url( $this->getFilePath() );

			if ( !empty( $parts['host'] ) )
	    		$new_dir = $upload_dir['basedir'] . '/cache/remote/' . sanitize_title( $parts['host'] );

	    	else
	    		$new_dir = $upload_dir['basedir'] . '/cache/remote/';

    	endif;

    	$new_dir = str_replace( '/cache/cache', '/cache', $new_dir );

    	return $new_dir;

	}

	public function getCacheFileName() {

		$path = $this->getFilePath();
	
		if ( !$path )
			return '';

		$serialize = crc32( serialize( array_merge( $this->args, array( $this->getFilePath() ) ) ) );
		
		// Gifs are converted to pngs
		if ( $this->getFileExtension() == 'gif' )
			return $serialize . '.png';

    	return $serialize . '.' . $this->getFileExtension();

	}

	public function generateCacheFile() {

		$new_filepath = $this->getCacheFilePath();
		$file_path = $this->getFilePath();

		// Up the php memory limit
    	@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', '256M' ) );

    	// Create the image
    	try {
    		$thumb = phpThumbFactory::create( $file_path, array( 'jpegQuality' => $this->args['jpeg_quality'] ) );

    	} catch ( Exception $e ) {
    		$this->error = $e;
    		return $this->returnImage();

    	}

    	$thumb = apply_filters( 'wpthumb_image_filter', $thumb, $this->args );

		extract( $this->args );

		// Convert gif images to png before resizing
    	if ( $this->getFileExtension() == 'gif' ) :

    		// Don't resize animated gifs and the animations will be broken
    		if ( !empty( $resize_animations ) !== true && $this->isAnimatedGif() ) {
    			$this->error = new WP_Error( 'animated-gif' );
    			return $this->returnImage();
    		}
    		
			wp_mkdir_p( $this->getCacheFileDirectory() );
			
    		// Save the converted image
    		$thumb->save( $new_filepath, 'png' );

    		unset( $thumb );

    		// Pass the new file back through the function so they are resized
    		return wpthumb( $new_filepath . '.png', $this->args );

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
		
		wp_mkdir_p( $this->getCacheFileDirectory() );

    	$thumb->save( $new_filepath );

    	// Destroy the image
    	unset( $thumb );

	}

	public function errored() {

		return ! empty( $this->error );

	}

	public function returnImage() {

		if ( ! empty( $this->error ) )
			$path = $this->getFilePath();
		else
			$path = $this->getCacheFilePath();

		if ( $this->args['return'] == 'path' )
			return $path;

		return $path ? $this->getFileURLForFilePath( $path ) : $path;
	}

	public function getCacheFileURL() {
		return $this->getFileURLForFilePath( $this->getCacheFilePath() );
	}

	public function getFileURL() {
		return $this->getFileURLForFilePath( $this->getFilePath() );
	}

	private function getFileURLForFilePath( $path ) {

		$upload_dir = wp_upload_dir();

		if ( strpos( $path, $upload_dir['basedir'] ) !== false ) {
    	    return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $path );

		} else {
			return str_replace( ABSPATH, get_bloginfo( 'url' ) . '/', $path );

		}

	}

	private function isAnimatedGif() {

		$filename = $this->getFilePath();

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

}

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

    $fields['crop-from-position'] = array(
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
    if ( is_numeric( $args ) ) {
    	$legacy_args = array_combine( array_slice( array( 'width', 'height', 'crop', 'resize' ), 0, count( array_slice( func_get_args(), 1 ) ) ), array_slice( func_get_args(), 1, 4 ) );
	}
	
    if ( isset( $legacy_args ) && $legacy_args )
    	$args = $legacy_args;

	$thumb = new WP_Thumb( $url, $args );

	return $thumb->returnImage();
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

		global $_wp_additional_image_sizes;

    	// Convert keyword sizes to heights & widths. Will still use file wordpress saved unless you change the thumbnail dimensions.
    	// TODO Might be ok to delete as I think it has been duplicated.  Needs testing.
    	if ( $args == 'thumbnail' )
    		$new_args = array( 'width' => get_option('thumbnail_size_w'), 'height' => get_option('thumbnail_size_h'), 'crop' => get_option('thumbnail_crop') );

    	elseif ( $args == 'medium' )
    		$new_args = array( 'width' => get_option('medium_size_w'), 'height' => get_option('medium_size_h') );

    	elseif ( $args == 'large' )
    		$new_args = array( 'width' => get_option('large_size_w'), 'height' => get_option('large_size_h') );

		elseif( is_string( $args ) && isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) && array_key_exists( $args, $_wp_additional_image_sizes ) )
    		$new_args = array( 'width' => $_wp_additional_image_sizes[$args]['width'], 'height' => $_wp_additional_image_sizes[$args]['height'], 'crop' => $_wp_additional_image_sizes[$args]['crop'] );

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

    if ( empty( $path ) )
    	$path = get_attached_file( $id );

	$image = new WP_Thumb( $path, $args );

    $args = $image->getArgs();

    extract( $args );

	if ( file_exists( $path ) ) {

		$image_src = $image->returnImage();

    	$crop = (bool) ( empty( $crop ) ) ? false : $crop;

    	if ( !$image->errored() && $image_meta = getimagesize( $image->getCacheFilePath() ) ) :

    	    $html_width = $image_meta[0];
    	    $html_height = $image_meta[1];

    	else :
    		$html_width = $html_height = false;

    	endif;

	} else {

		$html_width = $width;
		$html_height = $height;
		$image_src = $image->getFileURL();

	}

    return array( $image_src, $html_width, $html_height, true );

}
add_filter( 'image_downsize', 'wpthumb_post_image', 99, 3 );

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

	$upload_dir = wp_upload_dir();

	$wpthumb = new WP_Thumb( $upload_dir['basedir'] . $file );

	wpthumb_rmdir_recursive( $wpthumb->getCacheFileDirectory() );

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
    if( !$_GET['button'] )
        return;
    
    ?>
    <script type="text/javascript">
        jQuery(".add-watermark").live('click', function(e) {
            e.preventDefault();
            addWmPane = jQuery(this).closest('tr').next('tr');
            jQuery(addWmPane).show();
        });
        jQuery(".wm-watermark-options a.preview-watermark").live('click', function(e) {
            e.preventDefault();
            WMCreatePreview( jQuery(this).closest(".wm-watermark-options") );
        });
        jQuery(".wm-watermark-options a.cancel-watermark").live('click', function(e) {
            e.preventDefault();
            jQuery(this).closest(".wm-watermark-options").find("input.wm_use_watermark").removeAttr('checked');
            jQuery(this).closest("tr").hide();
        });
        function WMCreatePreview( optionsPane ) {
            position = jQuery(optionsPane).find("select.wm_watermark_position").val();
            padding = jQuery(optionsPane).find("input.wm_watermark_padding").val();
            mask = jQuery(optionsPane).find("select.wm_watermark_mask").val();
            
            //show loading
            jQuery(optionsPane).next(".wm-watermark-preview").html('<span class="wm-loading">Generating Preview...</span>');
            
            if( typeof(WMCreatePreviewXHR) != 'undefined' )
                WMCreatePreviewXHR.abort();
                
            WMCreatePreviewXHR = jQuery.get(jQuery(optionsPane).find("a.preview-watermark").attr("href"), { action: 'wpthumb_wm_watermark_preview_image', position: position, padding: padding, image_id: jQuery(optionsPane).attr('rel'), mask: mask },
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
    <?php
}
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
    
    //work out hihc button id this is
    $buttons = get_option( 'custom_media_buttons' );

    if ( $_GET['button'] ) :
        $button_id = $_GET['button'];

    endif;
    
    $button = '<a class="button add-watermark" rel="' . $media->ID . '">' . (wpthumb_wm_image_has_watermark($media->ID) ? 'Edit' : 'Add') .' Watermark</a>';
    
    $watermark_masks_options_html = '';
    foreach( wpthumb_wm_get_watermark_masks() as $mask_id => $watermark_mask ) {
        $watermark_masks_options_html .= '<option value="' . $mask_id . '" ' . ( wpthumb_wm_mask( $media->ID ) == $mask_id ? 'selected="selected"' : '' ) . '>' . $watermark_mask['label'] . '</option>' . "\n";
    }
    
    $form_fields['buttons']['tr'] = substr( $form_fields['buttons']['tr'], 0, strlen($form_fields['buttons']['tr']) - 11) . $button . '</td></tr>';
    $form_fields['buttons']['tr'] .= '
        <tr style="display:none"><td></td><td>
            <div class="watermark">
            <div rel="' . $media->ID . '" class="wm-watermark-options">
                <p><label>
                    <input class="wm_use_watermark" ' . (wpthumb_wm_image_has_watermark( $media->ID ) ? 'checked="checked"' : '') . ' type="checkbox" name="wpthumb_wm_use_watermark[' . $media->ID . ']" />
                    <strong>Apply watermark</strong>
                </label></p>
                <p><label>Positition</label>
                    <select class="wm_watermark_position" name="wpthumb_wm_watermark_position[' . $media->ID . ']">
                        <option ' . ( wpthumb_wm_position($media->ID) == 'top-left' ? 'selected="selected"' : '' ) .' value="top-left">Top Left</option>
                        <option ' . ( wpthumb_wm_position($media->ID) == 'top-right' || wpthumb_wm_position($media->ID) == '' ? 'selected="selected"' : '' ) .' value="top-right">Top Right</option>
                        <option ' . ( wpthumb_wm_position($media->ID) == 'bottom-left' ? 'selected="selected"' : '' ) .' value="bottom-left">Bottom Left</option>
                        <option ' . ( wpthumb_wm_position($media->ID) == 'bottom-right' ? 'selected="selected"' : '' ) .' value="bottom-right">Bottom Right</option>
                    </select>
                </p>
                <p><label>Padding</label>
                    <input class="wm_watermark_padding" type="text" value="' . wpthumb_wm_padding($media->ID) . '" style="width:30px" name="wpthumb_wm_watermark_padding[' . $media->ID . ']">px
                </p>
                <p><small>Padding (gutter) is the space that the watermark appears from the edge of the image</small><br /></p>
                
                <p>
                    <label>Select Watermark</label>
                    <select name="wm_watermark_mask[' . $media->ID . ']" class="wm_watermark_mask">
                        ' . $watermark_masks_options_html . '
                    </select>
                </p>
                
                <p class="clear clearfix">
                    <input type="submit" name="' . $button_id . '[' . $media->ID . ']" class="button-primary" value="Add Watermark"> <a href="' . str_replace( ABSPATH, get_bloginfo('url') . '/', dirname( __FILE__ )) . '/watermark-actions.php' . '" class="button preview-watermark">Preview</a> | <a href="" class="cancel-watermark">Cancel</a>
                </p>
            </div>
            <div class="wm-watermark-preview">    
            </div>
            </div>
        </td></tr>';
    return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'wpthumb_wm_add_watermark_button', 100, 2 );

function wpthumb_wm_watermark_preview_image( $position, $padding, $image_id, $mask ) {
    $image = get_attached_file($image_id);
    $watermark = array();
    $watermark['mask'] = wpthumb_wm_get_watermark_mask_file( $mask );
        
    if( $position == 'top-left' ) $watermark['position'] = 'lt';
    if( $position == 'top-right' ) $watermark['position'] = 'rt';
    if( $position == 'bottom-left' ) $watermark['position'] = 'lb';
    if( $position == 'bottom-right' ) $watermark['position'] = 'rb';
    
    $watermark['padding'] = (int) $padding;
    $watermark['pre_resize'] = true;
    
    $large_watermark = $watermark;
    $large_watermark['pre_resize'] = false;
    
    $args = array( 'width' => 200, 'crop' => false, 'obfuscate_filename' => true, 'watermark_options' => $watermark );
    
    $image_src = wpthumb_get_image_with_scaled_watermark( $image_id, $args, 560, 0 );

    return '<img src="' . $image_src . '" /><a target="_blank" href="' . wpthumb( $image, array( 'width' => 1000, 'height' => 0, 'crop' => false, 'resize'  => true, 'watermark_options' => $large_watermark, 'cache' => false ) ) . '">View Large</a>';
} 


function wpthumb_get_image_with_scaled_watermark( $id = null, $image_args = array(), $original_image_width, $original_image_height  ) {
		
	if( $id ) {
		//create the scaled down versions before watermark		
		$args = wp_parse_args( $image_args, array( 'watermark_options' => wpthumb_wm_get_options( $id ) ) );
		$args['watermark_options']['original_image_width'] = $original_image_width;
		$args['watermark_options']['original_image_height'] = $original_image_height;
		$args['watermark_options']['pre_resize'] = true;
				
		return wpthumb( get_attached_file( $id ), $args );
	}
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
	
	if ( $pos = get_post_meta( $image_id, 'wpthumb_wm_position', true ) )
		return $pos;
		
	//legacy
	if ( $pos = get_post_meta( $image_id, 'wm_position', true ) )
	    return $pos;
}

/**
 * wpthumb_wm_padding function.
 *
 * @access public
 * @param mixed $image_id
 * @return null
 */
function wpthumb_wm_padding( $image_id ) {
    
    if ( $padding = (int) get_post_meta( $image_id, 'wpthumb_wm_padding', true ) )
		return $padding;
		
	//legacy
	if ( $padding = (int) get_post_meta( $image_id, 'wm_padding', true ) )
	    return $padding;
}

function wpthumb_wm_pre_resize( $image_id ) {
        
    if ( $pre = (bool) get_post_meta( $image_id, 'wpthumb_wm_pre_resize', true ) )
		return $pre;
		
	//legacy
	if ( $pre = (bool) get_post_meta( $image_id, 'wm_pre_resize', true ) )
	    return $pre;

}
function wpthumb_wm_mask( $image_id ) {
	
	if ( $pre = (string) get_post_meta( $image_id, 'wpthumb_wm_mask', true ) )
		return $pre;
		
	//legacy
	if ( $pre = (string) get_post_meta( $image_id, 'wm_mask', true ) )
	    return $pre;

}

/**
 * Returns all the watermarks that are registered
 * 
 * @return array
 */
function wpthumb_wm_get_watermark_masks() {
    global $_wm_registered_watermarks;
    $_wm_registered_watermarks = (array) $_wm_registered_watermarks;
    
    $masks = array( 'default' => array( 'file' => get_stylesheet_directory() . '/images/watermark.png', 'label' => 'Default' ) );
    
    $masks = array_merge( $masks, $_wm_registered_watermarks );
    
    return $masks;
}

/**
 * Returns the watermaring image file for a given watermark name
 * 
 * @param string $mask
 * @return string
 */
function wpthumb_wm_get_watermark_mask_file( $mask ) {
    $masks = wpthumb_wm_get_watermark_masks();
    return $masks[$mask]['file'];
}

/**
 * Registers extr awatermark images for the suer to select in the admin
 * 
 * @param string $name - sanetixed identifier
 * @param string $file - full path to the watermarking image
 * @param string $label - test to be used for the watermarks name
 */
function wpthumb_wm_register_watermark( $name, $file, $label ) {
    
    global $_wm_registered_watermarks;
    $_wm_registered_watermarks = (array) $_wm_registered_watermarks;
    
    $_wm_registered_watermarks[$name] = array( 'file' => $file, 'label' => $label );
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
    
    $mask = wpthumb_wm_mask( $id );
    
    if( !empty( $mask ) ) {
        $options['mask'] = wpthumb_wm_get_watermark_mask_file( $mask );
    } else {
        $mask =  wpthumb_wm_get_default_watermark_mask();
        $options['mask'] = $mask['file'];
    }

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

    return $options;
}

/**
 * Returns the default watermask array ( file => string, label => string )
 * 
 * @return array
 */
function wpthumb_wm_get_default_watermark_mask() {
    $masks = wpthumb_wm_get_watermark_masks();
    return $masks['default'];
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

        if ( $file == '.' || $file == '..' )
        	continue;

        $path = $dir . $file;

        if ( is_dir( $path ) )
            wpthumb_rmdir_recursive( $path );

        else
        	unlink( $path );

    }

    closedir( $handle );

    rmdir( $dir );

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

/**
 * wpthumb_test function.
 *
 * @access public
 * @return null
 */
function wpthumb_test() {

	$remote_image_src = 'http://selfridgesretaillimited.scene7.com/is/image/SelfridgesRetailLimited/432-3000609-M1112318_GILLIGANRUSTMULTI?$PDP_M$';
	$image_with_query = 'http://static.zara.net/photos//2011/I/0/2/p/1564/330/401/1564330401_1_1_3.jpg?timestamp=1313153350286'; ?>

	<img src="<?php echo wpthumb( $image_with_query, 'width=100&height=100&crop=1' ) ?>" />

	<?php exit; ?>

	<img src="<?php echo wpthumb( $remote_image_src, 'width=100&height=100&crop=1' ) ?>" />

	<?php $test_images_dir = dirname( __FILE__ ) . '/test-images'; ?>

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

if ( isset( $_GET['wpthumb_test'] ) )
	add_action( 'init', 'wpthumb_test' );