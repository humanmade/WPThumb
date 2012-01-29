<?php

/*
Plugin Name: WP Thumb
Plugin URI: https://github.com/humanmade/WPThumb
Description: An on-demand image generation replacement for WordPress' image resizing.
Author: Human Made Limited
Version: 0.7
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

define( 'WP_THUMB_PATH', dirname( __FILE__ ) );
define( 'WP_THUMB_URL', str_replace( ABSPATH, site_url( '/' ), WP_THUMB_PATH ) );

// TODO wpthumb_create_args_from_size filter can pass string or array which makes it difficult to hook into

// Don't activate on anything less than PHP 5.2.4
if ( version_compare( phpversion(), '5.2.4', '<' ) ) {

	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( WP_THUMB_PATH . '/plugin.php' );

	if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'activate' || $_GET['action'] == 'error_scrape' ) )
		die( __( 'WP Thumb requires PHP version 5.2.4 or greater.', 'wpthumb' ) );

}

// Load the watermarking class
include_once( WP_THUMB_PATH . '/wpthumb.watermark.php' );

/**
 * Base WP_Thumb class
 *
 */
class WP_Thumb {

	/**
	 * Array of image args
	 *
	 * @var array
	 * @access private
	 */
	private $args;

	/**
	 * The file path the original image
	 *
	 * @var strin
	 * @access private
	 */
	private $file_path;

	/**
	 * Setup phpthumb, parse the args and generate the cache file
	 *
	 * @access public
	 * @param string $file_path. (default: null)
	 * @param array $args. (default: array())
	 */
	public function __construct( $file_path = null, $args = array() ) {

		// Load PHPThumb if it isn't already defined
		if ( ! class_exists( 'PhpThumbFactory' ) )
	    	include_once( WP_THUMB_PATH . '/phpthumb/src/ThumbLib.inc.php' );

		if ( $file_path )
			$this->setFilePath( $file_path );

		if ( $args )
			$this->setArgs( $args );

		$dimensions = array_slice( (array) @getimagesize( $this->getFilePath() ), 0, 2 );

		if ( ( $this->getArg( 'width' ) != $dimensions[0] || $this->getArg( 'height' ) != $dimensions[1] ) && $this->getFilePath() && $this->getArgs() && ( ! file_exists( $this->getCacheFilePath() ) || ! $this->args['cache'] ) )
			$this->generateCacheFile();

	}

	/**
	 * Set the file path of the original image
	 *
	 * Will convert URLS to paths.
	 *
	 * @param string $file_path
	 */
	public function setFilePath( $file_path ) {

		$upload_dir = wp_upload_dir();

		if ( strpos( $file_path, ABSPATH ) === 0 ) {
			$this->file_path = $file_path;
			return;
		}

		if ( strpos( $file_path, $upload_dir['baseurl'] ) !== false )
    		$this->file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_path );

    	else
    		$this->file_path = str_replace( get_bloginfo( 'url' ) . '/', ABSPATH, $file_path );

	}

	/**
	 * Parse the args and merge with defaults
	 *
	 * @param array $args
	 */
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
    		'background_fill'		=> null,
    		'output_file'			=> false
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

	/**
	 * Return the file path to the original image
	 *
	 * @return string
	 */
	public function getFilePath() {

		if ( strpos( $this->file_path, '/' ) === 0 && ! file_exists( $this->file_path ) && $this->args['default'] )
			$this->file_path = $this->args['default'];

		return reset( explode( '?', $this->file_path ) );
	}

	/**
	 * Return the array of args
	 *
	 * @return array
	 */
	public function getArgs() {
		return (array) $this->args;
	}

	/**
	 * Get a specific arg
	 *
	 * @access public
	 * @param string $arg
	 * @return bool
	 */
	public function getArg( $arg ) {

		if ( isset( $this->args[$arg] ) )
			return $this->args[$arg];

		return false;

	}

	/**
	 * Get the extension of the original image
	 *
	 * @return string
	 */
	public function getFileExtension() {

		$ext = pathinfo( $this->getFilePath(), PATHINFO_EXTENSION );

    	if ( ! $ext ) {
    		// Seems like we dont have an ext, lets guess at JPG
			$ext = 'jpg';
    	}

		return $ext;

	}

	/**
	 * Get the filepath to the cache file
	 *
	 * @access public
	 * @return string
	 */
	public function getCacheFilePath() {

		$path = $this->getFilePath();

		if ( ! $path )
			return '';

	    return trailingslashit( $this->getCacheFileDirectory() ) . $this->getCacheFileName();

	}

	/**
	 * Get the directory that the cache file should be saved too
	 *
	 * @return string
	 */
	public function getCacheFileDirectory() {

		if ( $this->getArg( 'output_file' ) )
			return dirname( $this->getArg( 'output_file' ) );

		$path = $this->getFilePath();

		if ( ! $path )
			return '';

		$original_filename = basename( $this->getFilePath() );

    	// If the image was remote, we want to store them in the remote images folder, not it's name
    	if ( strpos( $original_filename, '0_0_resize' ) === 0 )
    		$original_filename = end( explode( '/', str_replace( '/' . $original_filename, '', $this->getFilePath() ) ) );

		// TODO use pathinfo
		$parts = explode( '.', $original_filename );

		array_pop( $parts );

		$filename_nice = implode( '_', $parts );

    	$upload_dir = wp_upload_dir();

    	if ( strpos( $this->getFilePath(), $upload_dir['basedir'] ) === 0 ) :
    		$new_dir = $upload_dir['basedir'] . '/cache' . $upload_dir['subdir'] . '/' . $filename_nice;

		elseif ( strpos( $this->getFilePath(), ABSPATH ) === 0 ) :

			$new_dir = $upload_dir['basedir'] . '/cache/local';

    	else :
    		$parts = parse_url( $this->getFilePath() );

			if ( ! empty( $parts['host'] ) )
	    		$new_dir = $upload_dir['basedir'] . '/cache/remote/' . sanitize_title( $parts['host'] );

	    	else
	    		$new_dir = $upload_dir['basedir'] . '/cache/remote';

    	endif;

		// TODO unit test for whether this is needed or not
    	$new_dir = str_replace( '/cache/cache', '/cache', $new_dir );

    	return $new_dir;

	}

	/**
	 * Get the filename of the cache file
	 *
	 * @return string
	 */
	public function getCacheFileName() {

		if ( $this->getArg( 'output_file' ) )
			return basename( $this->getArg( 'output_file' ) );

		$path = $this->getFilePath();

		if ( ! $path )
			return '';

		// Generate a short unique filename
		$serialize = crc32( serialize( array_merge( $this->getArgs(), array( $this->getFilePath() ) ) ) );

		// Gifs are converted to pngs
		if ( $this->getFileExtension() == 'gif' )
			return $serialize . '.png';

    	return $serialize . '.' . $this->getFileExtension();

	}

	/**
	 * Generate the new cache file using the original image and args
	 *
	 * @return string new filepath
	 */
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

		wp_mkdir_p( $this->getCacheFileDirectory() );

		// Convert gif images to png before resizing
    	if ( $this->getFileExtension() == 'gif' ) :

    		// Don't resize animated gifs as the animations will be broken
    		if ( ! empty( $resize_animations ) !== true && $this->isAnimatedGif() ) {

    			$this->error = new WP_Error( 'animated-gif' );

    			return $this->returnImage();

    		}

    		// Save the converted image
    		$thumb->save( $new_filepath, 'png' );

    		unset( $thumb );

    		// Pass the new file back through the function so they are resized
    		return new WP_Thumb( $new_filepath, array_merge( $this->args, array( 'output_file' => $new_filepath, 'cache' => false ) ) );

    	endif;

    	// Watermarking (pre resizing)
    	if ( isset( $watermark_options['mask'] ) && $watermark_options['mask'] && isset( $watermark_options['pre_resize'] ) && $watermark_options['pre_resize'] === true ) {

    		// TODO why?
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

	}

	/**
	 * Is there an error
	 *
	 * @access public
	 * @return null
	 */
	public function errored() {
		return ! empty( $this->error );
	}

	/**
	 * Return the finished image
	 *
	 * If there was an error, return the original
	 *
	 * @access public
	 * @return null
	 */
	public function returnImage() {

		$dimensions = array_slice( (array) @getimagesize( $this->getFilePath() ), 0, 2 );

		if ( ! empty( $this->error ) || ( $this->getArg( 'width' ) == $dimensions[0] && $this->getArg( 'height' ) == $dimensions[1] ) )
			$path = $this->getFilePath();

		else
			$path = $this->getCacheFilePath();

		if ( $this->args['return'] == 'path' )
			return $path;

		return $path ? $this->getFileURLForFilePath( $path ) : $path;
	}

	/**
	 * Get the url for the cache file
	 *
	 * @return string
	 */
	public function getCacheFileURL() {
		return $this->getFileURLForFilePath( $this->getCacheFilePath() );
	}

	/**
	 * Get the url for the original file
	 *
	 * @access public
	 * @return null
	 */
	public function getFileURL() {
		return $this->getFileURLForFilePath( $this->getFilePath() );
	}

	/**
	 * Convert a path into a url
	 *
	 * @param string $path
	 * @return string url
	 */
	private function getFileURLForFilePath( $path ) {

		$upload_dir = wp_upload_dir();

		if ( strpos( $path, $upload_dir['basedir'] ) !== false ) {
    	    return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $path );

		} else {
			return str_replace( ABSPATH, get_bloginfo( 'url' ) . '/', $path );

		}

	}

	/**
	 * Check if an image is an animated gif
	 *
	 * @access private
	 * @return bool
	 */
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

    if ( ! $current_position )
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
    	'label' => __( 'Crop Position', 'wpthumb' ),
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

    // Check if is using legacy args
    if ( is_numeric( $args ) )
    	$legacy_args = array_combine( array_slice( array( 'width', 'height', 'crop', 'resize' ), 0, count( array_slice( func_get_args(), 1 ) ) ), array_slice( func_get_args(), 1, 4 ) );

    if ( isset( $legacy_args ) && $legacy_args )
    	$args = $legacy_args;

	$thumb = new WP_Thumb( $url, $args );

	return $thumb->returnImage();

}

/**
 * Hook WP Thumb into the WordPress image functions
 *
 * Usage `the_post_thumbnail( 'width=100&height=200&crop=1' );`
 *
 * @param null $null
 * @param int $id
 * @param array $args
 * @return null
 */
function wpthumb_post_image( $null, $id, $args ) {

    if ( ( ! strpos( (string) $args, '=' ) ) && ! ( is_array( $args ) && isset( $args[0] ) && $args[0] == $args[1] ) ) {

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

    	if ( ! $new_args )
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

    	if ( ! $image->errored() && $image_meta = @getimagesize( $image->getCacheFilePath() ) ) :

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

	if ( is_object( $post ) && ! empty( $post->ID ) )
	    $post_id = $post->ID;

    if ( ! is_numeric( $post_id ) )
    	return false;

    $images = array();

    foreach( (array) get_children( array( 'post_parent' => $post_id, 'post_type' => 'attachment', 'orderby' => 'menu_order', 'order' => 'ASC' ) ) as $attachment ) {

    	if ( ! wp_attachment_is_image( $attachment->ID ) || ! file_exists( get_attached_file( $attachment->ID ) ) )
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

    if ( ! file_exists( $path ) )
    	return false;

    $dimensions = @getimagesize( $path );

    if ( $both == true && ( $dimensions[0] < $width || $dimensions[1] < $height ) )
    	return true;

	return false;

}

/**
 * Hook into wp_delete_file and delete the assocated cache files
 *
 * @param string $file
 * @return string
 */
function wpthumb_delete_cache_for_file( $file ) {

	$upload_dir = wp_upload_dir();

	$wpthumb = new WP_Thumb( $upload_dir['basedir'] . $file );

	wpthumb_rmdir_recursive( $wpthumb->getCacheFileDirectory() );

	return $file;

}
add_filter( 'wp_delete_file', 'wpthumb_delete_cache_for_file' );

/**
 * Removes a dir tree. I.e. recursive rmdir
 *
 * @param string $dir
 * @return bool - success / failure
 */
function wpthumb_rmdir_recursive( $dir ) {

    if ( ! is_dir( $dir ) )
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

	<?php exit;

}

if ( isset( $_GET['wpthumb_test'] ) )
	add_action( 'init', 'wpthumb_test' );