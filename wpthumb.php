<?php
/*
Plugin Name: WP Thumb
Plugin URI: https://github.com/humanmade/WPThumb
Description: An on-demand image generation replacement for WordPress' image resizing.
Author: Human Made Limited
Version: 0.9
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

define( 'WP_THUMB_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WP_THUMB_URL', plugin_dir_url( __FILE__ ) );

// TODO wpthumb_create_args_from_size filter can pass string or array which makes it difficult to hook into


// Load the watermarking class
include_once( WP_THUMB_PATH . '/wpthumb.watermark.php' );
include_once( WP_THUMB_PATH . '/wpthumb.background-fill.php' );
include_once( WP_THUMB_PATH . '/wpthumb.crop-from-position.php' );

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

	private static $wp_upload_dir;

	private static function uploadDir() {

		if ( empty( self::$wp_upload_dir ) )
			self::$wp_upload_dir = wp_upload_dir();

		return self::$wp_upload_dir;
	}

	private static function get_home_path() {
		return str_replace( str_replace( home_url(), '', site_url() ), '', ABSPATH );
	}

	/**
	 * Setup phpthumb, parse the args and generate the cache file
	 *
	 * @access public
	 * @param string $file_path. (default: null)
	 * @param array $args. (default: array())
	 */
	public function __construct( $file_path = null, $args = array() ) {

		if ( $file_path )
			$this->setFilePath( $file_path );

		if ( $args )
			$this->setArgs( $args );

		if ( $this->getFilePath() && ! $this->errored() ) {

			if ( ! file_exists( $this->getCacheFilePath() ) || ! $this->args['cache'] ) {

				$this->generateCacheFile();
			}
		}

	}

	/**
	 * Set the file path of the original image
	 *
	 * Will convert URLS to paths.
	 *
	 * @param string $file_path
	 */
	public function setFilePath( $file_path ) {

		$upload_dir = self::uploadDir();
		$this->_file_path = null;
		
		if ( strpos( $file_path, self::get_home_path() ) === 0 ) {
			  $this->file_path = $file_path;
			  return;
		}

		// If it's an uploaded file
		if ( strpos( $file_path, $upload_dir['baseurl'] ) !== false )
			$this->file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_path );

		else
			$this->file_path = str_replace( trailingslashit( home_url() ), self::get_home_path(), $file_path );

		// if it's a local path, lets check it now
		if ( strpos( $this->file_path , '/' ) === 0 && strpos( $this->file_path , '//' ) !== 0 && ! file_exists( $this->file_path ) )
			$this->error = new WP_Error( 'file-not-found' );
	}

	/**
	 * Parse the args and merge with defaults
	 *
	 * @param array $args
	 */
	public function setArgs( $args ) {

		 $arg_defaults = array(
			'width' 				    => 0,
			'height'				    => 0,
			'crop'					    => false,
			'crop_from_position' 	    => 'center,center',
			'resize'				    => true,
			'watermark_options' 	    => array(),
			'cache'					    => true,
			'skip_remote_check' 	    => false,
			'default'				    => null,
			'jpeg_quality' 			    => 90,
			'resize_animations' 	    => true,
			'return' 				    => 'url',
			'custom' 				    => false,
			'background_fill'		    => null,
			'output_file'			    => false,
            'cache_with_query_params'   => false
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
		if ( is_string( $args['crop_from_position'] ) && $args['crop_from_position'] )
			$args['crop_from_position'] = explode( ',', $args['crop_from_position'] );

		$this->args = $args;

	}

	/**
	 * Return the file path to the original image
	 *
	 * @return string
	 */
	public function getFilePath() {

		if ( ! empty( $this->_file_path ) )
			return $this->_file_path;

		if ( strpos( $this->file_path, '/' ) === 0 && ! file_exists( $this->file_path ) && $this->args['default'] )
			$this->file_path = $this->args['default'];

		elseif ( ( ! $this->file_path ) && $this->args['default'] && file_exists( $this->args['default'] ) )
			$this->file_path = $this->args['default'];			

        if ( $this->getArg( 'cache_with_query_params' ) )
            return $this->file_path;

        $this->_file_path = reset( explode( '?', $this->file_path ) );

		return $this->_file_path;
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
	 * @return mixed
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

		return apply_filters( 'wpthumb_cache_file_path', trailingslashit( $this->getCacheFileDirectory() ) . $this->getCacheFileName(), $this );

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

		// TODO use pathinfo
		$parts = explode( '.', $original_filename );

		array_pop( $parts );

		$filename_nice = implode( '_', $parts );

		$upload_dir = self::uploadDir();

		if ( strpos( $this->getFilePath(), $upload_dir['basedir'] ) === 0 ) :
			
			$subdir = dirname( str_replace( $upload_dir['basedir'], '', $this->getFilePath() ) );
			$new_dir = $upload_dir['basedir'] . '/cache' . $subdir . '/' . $filename_nice;

		elseif ( strpos( $this->getFilePath(), WP_CONTENT_DIR ) === 0 ) :

			$subdir = dirname( str_replace( WP_CONTENT_DIR, '', $this->getFilePath() ) );
			$new_dir = $upload_dir['basedir'] . '/cache' . $subdir . '/' . $filename_nice;

		elseif ( strpos( $this->getFilePath(), self::get_home_path() ) === 0 ) :
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

	public function isRemote() {

		return strpos( $this->getFilePath(), self::get_home_path() ) !== 0;

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
		$editor = wp_get_image_editor( $file_path, array( 'methods' => array( 'get_image' ) ) );

		/**
		 * Workaround to preserve image blending when images are not specifically resized (smaller than dimentions for example)
		 */
		if ( is_a( $editor, 'WP_Thumb_Image_Editor_GD' ) ) {
			imagealphablending( $editor->get_image(), false);
			imagesavealpha( $editor->get_image(), true );
		}

		if ( is_wp_error( $editor ) ) {
			$this->error = $editor;

			return $this->returnImage();
		}
		
		wp_mkdir_p( $this->getCacheFileDirectory() );

		// Convert gif images to png before resizing
		if ( $this->getFileExtension() == 'gif' ) :

			// Save the converted image
			$editor->save( $new_filepath, 'image/png' );

			// Pass the new file back through the function so they are resized
			return new WP_Thumb( $new_filepath, array_merge( $this->args, array( 'output_file' => $new_filepath, 'cache' => false ) ) );

		endif;

		apply_filters( 'wpthumb_image_pre', $editor, $this->args );

		extract( $this->args );

		// Cropping
		if ( $crop && $crop_from_position && $crop_from_position !== array( 'center', 'center' ) ) :

			$this->crop_from_position( $editor, $width, $height, $crop_from_position, $resize );

		elseif ( $crop === true && $resize === true ) :

			$editor->resize( $width, $height, true );

		elseif ( $crop === true && $resize === false ) :
			$this->crop_from_center( $editor, $width, $height );

		else :
			
			$editor->resize( $width, $height );
		endif;

		apply_filters( 'wpthumb_image_post', $editor, $this->args );

		$editor->save( $new_filepath );

		do_action( 'wpthumb_saved_cache_image', $this );
	}

	private function crop_from_center( $editor, $width, $height ) {

		$size = $editor->get_size();

		$crop = array( 'x' => 0, 'y' => 0, 'width' => $size['width'], 'height' => $size['height'] );

		if ( $width < $size['width'] ) {
			$crop['x'] = intval( ( $size['width'] - $width ) / 2 );
			$crop['width'] = $width;
		}

		if ( $height < $size['height'] ) {
			$crop['y'] = intval( ( $size['height'] - $height ) / 2 );
			$crop['height'] = $height;
		}

		return $editor->crop( $crop['x'], $crop['y'], $crop['width'], $crop['height'] );
	}

	private function crop_from_position( $editor, $width, $height, $position, $resize = true ) {

		$size = $editor->get_size();

		// resize to the largest dimension
		if ( $resize ) {

			$ratio1 = $size['width'] / $size['height'];
			$ratio2 = $width / $height;

			if ( $ratio1 < $ratio2 ) {
				$_width = $width;
			    $_height = $width / $ratio1;
			} else {
				$_height = $height;
			    $_width = $height * $ratio1;
			}
			
			$editor->resize( $_width, $_height );
		}

		$size = $editor->get_size();
		$crop = array( 'x' => 0, 'y' => 0 );

		if ( $position[0] == 'right' )
			$crop['x'] = absint( $size['width'] - $width );
		else if ( $position[0] == 'center' )
			$crop['x'] = intval( absint( $size['width'] - $width ) / 2 );

		if ( $position[1] == 'bottom' )
			$crop['y'] = absint( $size['height'] - $height );
		else if ( $position[1] == 'center' )
			$crop['y'] = intval( absint( $size['height'] - $height ) / 2 );

		
		return $editor->crop( $crop['x'], $crop['y'], $width, $height );
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

		if ( $this->errored() ) {

			$path = $this->getFilePath();

		} else {

			$path = $this->getCacheFilePath();
		}

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

		$upload_dir = self::uploadDir();

		if ( strpos( $path, $upload_dir['basedir'] ) !== false ) {
			return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $path );

		} else {
			return str_replace( self::get_home_path(), trailingslashit( home_url() ), $path );

		}

	}

}

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

	$thumb = new WP_Thumb( $url, $args );

	$return = $thumb->returnImage();

	return $return;
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

	// check if $args is a WP Thumb argument list, or native WordPress one
	// wp thumb looks like this: 'width=300&height=120&crop=1'
	// native looks like 'thumbnail'...|array( 300, 300 )
	if ( is_string( $args ) && ! strpos( (string) $args, '=' ) ) {

		global $_wp_additional_image_sizes;

		// Convert keyword sizes to heights & widths.
		if ( $args == 'thumbnail' )
			$new_args = array( 'width' => get_option('thumbnail_size_w'), 'height' => get_option('thumbnail_size_h'), 'crop' => get_option('thumbnail_crop') );

		elseif ( $args == 'medium' )
			$new_args = array( 'width' => get_option('medium_size_w'), 'height' => get_option('medium_size_h') );

		elseif ( $args == 'large' )
			$new_args = array( 'width' => get_option('large_size_w'), 'height' => get_option('large_size_h') );

		elseif( ! empty( $_wp_additional_image_sizes ) && array_key_exists( $args, $_wp_additional_image_sizes ) )
			$new_args = array( 'width' => $_wp_additional_image_sizes[$args]['width'], 'height' => $_wp_additional_image_sizes[$args]['height'], 'crop' => $_wp_additional_image_sizes[$args]['crop'], 'image_size' => $args );

		elseif ( $args != ( $new_filter_args = apply_filters( 'wpthumb_create_args_from_size', $args ) ) )
			$new_args = $new_filter_args;

		else
			$new_args = null;

		if ( ! $new_args )
			return $null;

		$args = $new_args;

	}

	$args = wp_parse_args( $args );

	if ( ! empty( $args[0] ) )
		$args['width'] = $args[0];

	if ( ! empty( $args[1] ) )
		$args['height'] = $args[1];

	if ( ! empty( $args['crop'] ) && $args['crop'] && empty( $args['crop_from_position'] ) )
		 $args['crop_from_position'] = get_post_meta( $id, 'wpthumb_crop_pos', true );

	if ( empty( $path ) )
		$path = get_attached_file( $id );

	$path = apply_filters( 'wpthumb_post_image_path', $path, $id, $args );
	$args = apply_filters( 'wpthumb_post_image_args', $args, $id );
	
	$image = new WP_Thumb( $path, $args );

	$args = $image->getArgs();

	extract( $args );

	if ( ! $image->errored() ) {

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

function wpthumb_add_image_editors( $editors ) {

	require_once( WP_THUMB_PATH . '/wpthumb.image-editor.php' );

	$editors[] = 'WP_Thumb_Image_Editor_GD';
	$editors[] = 'WP_Thumb_Image_Editor_Imagick';

	return $editors;
}
add_filter( 'wp_image_editors', 'wpthumb_add_image_editors' );
