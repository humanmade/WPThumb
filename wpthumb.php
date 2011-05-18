<?php
/*
Plugin Name: WPThumb
Plugin URI: https://github.com/humanmade/WPThumb
Description: PHPThumb for WordPress
Author: humanmade limited, Joe Hoyle, Tom Wilmott, Matthew Haines-Young
Version: 0.1
Author URI: http://www.humanmade.co.uk/
*/

if( !function_exists('wpthumb') ) {

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
	
	if( !class_exists( 'PhpThumbFactory' ) )
		include_once( dirname( __FILE__ ) . '/phpthumb/src/ThumbLib.inc.php' );

	// Check if is using legacy args
	if( is_numeric( $args ) ) {
		$legacy_args = func_get_args();
		$legacy_args = array_slice( $legacy_args, 1 );
		$old_args = array( 'width', 'height', 'crop', 'resize' );
		$legacy_args = array_combine( array_slice($old_args, 0, count($legacy_args)), $legacy_args );
	}

	if( isset( $legacy_args ) && $legacy_args )
		$args = phpthumb_parse_args( $legacy_args );
	else
		$args = phpthumb_parse_args( $args );

	extract( $args );

	// If the url is blank, use the default
	if ( empty( $url ) && $default )
		$url = $default;

	elseif ( !$url && !$default )
		return false;
	
	// sort out the watermark args
	if( isset($watermark_options['mask']) && $watermark_options['mask'] ) {
		$wm_defaults = array( 'padding' => 0, 'position' => 'cc', 'pre_resize' => false );
		$watermark_options = wp_parse_args( $watermark_options, $wm_defaults );
	}

	$width = (int) $width;
	$height = (int) $height;

	//if the file already matches (or is less than) the resize features, just return the url	
	if( ( !defined('WPTHUMB_FORCE_ENABLED') || defined('WPTHUMB_FORCE_ENABLED') && !WPTHUMB_FORCE_ENABLED ) && !$args['custom'] && function_exists( 'getimagesize' ) && strpos( $url, ABSPATH ) === 0 && file_exists( $url ) && ( $dimensions = getimagesize( $url ) ) && $dimensions[0] <= $width && $dimensions[1] <= $height ) {
		return phpthumb_get_file_url_from_file_path( $url );
	}

	$file_path = phpthumb_get_file_path_from_file_url( $url );

	$new_filepath = phpthumb_calculate_image_cache_file_path( $file_path, $args );

	$ext = '.' . end( explode( '.', $new_filepath ) );

	if( $new_filepath )
		phpthumb_create_dir_for_file( $new_filepath );
	
	// Only create the resized version if one hasn't already been created - or $cached is set to false.
	if ( !file_exists( $new_filepath )  || $cache === false || $ext == '.gif') :

		// up the php memory limit
		if ( (int) ini_get( 'memory_limit' ) < 256 )
			ini_set('memory_limit', '256M');

		if( !class_exists( 'phpThumbFactory' )) {
			error_log('phpThumbFactory class not found.');
			return;
		}
			
		try {
		     $thumb = phpThumbFactory::create( $file_path, array( 'jpegQuality' => $jpeg_quality ) );
		}
		catch (Exception $e) {
			error_log( print_r( $e, true ) );
			return phpthumb_get_file_url_from_file_path( $file_path );
		}

		$thumb = apply_filters( 'wpthumb_image_filter', $thumb, $args );
		
		// Convert gif images to png before resizing
		if ( $ext == '.gif' ) :

			//don't resize animated gifs and the animations will be broken
			if( $args['resize_animations'] !== true && phpthumb_is_gif_animated( $file_path ) ) {

				return phpthumb_get_file_url_from_file_path( $file_path );

			}

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

		if ( $crop === true && $resize === true ) {
			if( $crop_from_position && count( $crop_from_position ) == 2 && method_exists( $thumb, 'adaptiveResizeFromPoint' ) )
				$thumb->adaptiveResizeFromPoint( $width, $height, $crop_from_position[0], $crop_from_position[1] );
			else
			$thumb->adaptiveResize( $width, $height );
		}

		elseif( $crop === true && $resize === false )
			$thumb->cropFromCenter( $width, $height );

		else
			$thumb->resize( $width, $height );

		// watermarking (post resizing)
		if( isset( $watermark_options['mask'] ) && $watermark_options['mask'] && isset( $watermark_options['pre_resize'] ) && $watermark_options['pre_resize'] === false )
			$thumb->createWatermark($watermark_options['mask'], $watermark_options['position'], $watermark_options['padding']);
		
		$thumb->save( $new_filepath );

		// Destroy the image
		unset( $thumb );

	endif;
		
	if ( $return == 'path' )
		return $new_filepath;
	
	return phpthumb_get_file_url_from_file_path( $new_filepath );
}

/**
 * Calculates ful image path and new filename from an image path or src
 *
 * @param string $src
 * @param mixed $args
 * @return string - full image path
 */
function phpthumb_calculate_image_cache_file_path( $src, $args ) {

	$path = phpthumb_get_file_path_from_file_url( $src );
	
	$original_filename = end( explode( '/', $path ) );

	$new_dir = phpthumb_calculate_image_cache_dir( $path, $args );
	$new_filename = phpthumb_calculate_image_cache_filename( $original_filename, $args );

	return $new_dir . '/' . $new_filename;
}

function phpthumb_calculate_image_cache_dir( $path, $args = null ) {

	$original_filename = end( explode( '/', $path ) );

	//if the image was remote got, we want to store them in the remote images folder, not it's name
	if( strpos( $original_filename, '0_0_resize' ) === 0 ) {
		$original_filename = end( explode( '/', str_replace( '/' . $original_filename, '', $path ) ) );
	}

	$upload_dir = wp_upload_dir();
	$upload_dir_base = $upload_dir['basedir'];

	if( strpos( $path, ABSPATH ) !== 0 ) {
		$new_dir = $upload_dir_base . '/cache';
	} else if( strpos( $path, $upload_dir_base ) === 0 ) {
		$new_path = str_replace( $upload_dir_base, $upload_dir_base . '/cache', $path );
		$new_dir = str_replace( '/' . $original_filename, '', $new_path );
	} else {
		$new_dir = $upload_dir_base . '/cache';
	}

	$new_dir = str_replace( '/cache/cache', '/cache', $new_dir );

	// Append filename as a folder to keep track of cached files
	$new_dir .= '/' . sanitize_file_name( $original_filename );

	return $new_dir;
}

/**
 * Calcualtes the cached filename (not full path) from an image path
 *
 * @param string $path
 * @param mixed $args
 * @return string - filename
 */
function phpthumb_calculate_image_cache_filename( $filename, $args ) {
	extract( $args );

	$ext = strtolower( end( explode( '.', $filename ) ) );
	
	// Some files are converted to jpg by phpthumb
	if( in_array( $ext, array( 'bmp', 'tif', 'tiff' ) ) )
		$ext = 'jpg';
	
	$ext = '.' . $ext;

	//Plugins can append custom information to the end of the filename.
	$custom = false;
	$custom = apply_filters( 'wpthumb_filename_custom', $custom, $args ); 

	$new_name = $width . '_' . $height . ( $crop ? '_crop' : '') . ($resize ? '_resize' : '') . ( isset($watermark_options['mask']) && $watermark_options['mask'] ? '_watermarked_' . $watermark_options['position'] : '') . ( $custom ? '_'. $custom : '' ) . $ext;

	return $new_name;
}

function phpthumb_create_dir_for_file( $path ) {

	$filename = end( explode( '/', $path ) );
	$dir = str_replace( $filename, '', $path );

	if ( !is_dir( $dir ) )
		mkdir( $dir, 0755, true );

	return;
}

/**
 * Create a image from a path with $size args
 *
 * @param string $image_path
 * @param mixed $args - wp_args
 * @return string - image URL
 */
function phpthumb_image_from_args( $image_path, $args ) {

	$args = wp_parse_args( $args );

	extract( $args );

	$image = wpthumb( $image_path, $args );

	$crop = (bool) ( empty( $crop ) ) ? false : $crop;

	if ( $image_meta = getimagesize( phpthumb_get_file_path_from_file_url( $image ) ) ) {
	
	    $html_width = $image_meta[0];
	    $html_height = $image_meta[1];

	} else {
		$html_width = $html_height = false;

	}

	return array( $image, $html_width, $html_height, true );

}

function phpthumb_post_image( $null, $id, $args ) {

	if ( ( !strpos( (string) $args, '=' ) ) && !( is_array( $args ) && isset( $args[0] ) && $args[0] == $args[1] ) ) {
		
		// Convert keyword sizes to heights & widths. Will still use file wordpress saved unless you change the thumbnail dimensions. 
		// Might be ok to delete as I think it has been duplicated.  Needs testing.
		if( $args == 'thumbnail' ) 
			$new_args = array( 'width' => get_option('thumbnail_size_w'), 'height' => get_option('thumbnail_size_h'), 'crop' => get_option('thumbnail_crop') ); 
		elseif( $args == 'medium' ) 
			$new_args = array( 'width' => get_option('medium_size_w'), 'height' => get_option('medium_size_h') ); 
		elseif( $args == 'large' ) 
			$new_args = array( 'width' => get_option('large_size_w'), 'height' => get_option('large_size_h') );
		elseif( is_string( $args ) )
			$new_args = apply_filters( 'phpthumb_create_args_from_size', $args );
		elseif( is_array( $args ) )
			$new_args = $args;
		else
			$new_args = null;

		if( !$new_args ) {
		return null;
		}
		
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

	$args = phpthumb_parse_args( $args );
	
	if( $args['original_size'] == 'thumbnail' && $args['crop_from_position'] == array( 'center', 'center' ) ) {
		
		$intermediate = image_get_intermediate_size( $id, 'thumbnail' );
		$path = phpthumb_get_file_path_from_file_url( $intermediate['url'] );

		if( phpthumb_is_image_smaller_than_dimensions( $path, $args['width'], $args['height'] ) )
			$path = null;
		}

	if( !$path ) {
		$path = get_attached_file( $id );
	}

	if( file_exists( $path ) && !is_dir( $path ) && !$args['default'] )
	return phpthumb_image_from_args( $path, $args );
	
	else 
		return $null;
}
add_filter( 'image_downsize', 'phpthumb_post_image', 99, 3 );


function phpthumb_parse_args( $args ) {

	$arg_defaults = array(
		'width' 	=> 0,
		'height'	=> 0,
		'crop'		=> false,
		'crop_from_position' => 'center,center',
		'resize'	=> true,
		'watermark_options' => array(),
		'cache'		=> true,
		'skip_remote_check' => false,
		'default'	=> null,
		'jpeg_quality' => 80,
		'resize_animations' => true,
		'return' => 'url',
		'custom' => false
	);
	
	$args = wp_parse_args( $args, $arg_defaults );
	
	if( $args['width'] == 'thumbnail' ) 
		$new_args = array( 'width' => get_option('thumbnail_size_w'), 'height' => get_option('thumbnail_size_h'), 'crop' => get_option('thumbnail_crop') ); 
	elseif( $args['width'] == 'medium' ) 
		$new_args = array( 'width' => get_option('medium_size_w'), 'height' => get_option('medium_size_h') ); 
	elseif( $args['width'] == 'large' ) 
		$new_args = array( 'width' => get_option('large_size_w'), 'height' => get_option('large_size_h') );
	elseif( is_string( $args['width'] ) )
		$new_args = apply_filters( 'phpthumb_create_args_from_size', $args );
	elseif( is_array( $args['width'] ) )
		$new_args = $args;

	$args = wp_parse_args( $new_args, $args );
	
	// Cast some args
	$args['crop'] = (bool) $args['crop'];
	$args['resize'] = (bool) $args['resize'];
	$args['cache'] = (bool) $args['cache'];
	
	// Format the crop from position arg
	if( is_string( $args['crop_from_position'] ) ) {
		$args['crop_from_position'] = explode( ',', $args['crop_from_position'] );
	}
	
	return $args;
	
}


function phpthumb_get_file_path_from_file_url( $url ) {
	if( is_multisite() && !is_main_site() ) {
		$upload_dir = wp_upload_dir();
		
		return str_replace( get_bloginfo('wpurl') . '/files', $upload_dir['basedir'], $url );
	} else {
		return str_replace( get_bloginfo('wpurl') . '/', ABSPATH, $url );
	}
}

function phpthumb_get_file_url_from_file_path( $url ) {

	if( is_multisite() && !is_main_site() ) {
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
function phpthumb_is_gif_animated( $filename ) {

        $filecontents=file_get_contents($filename);

        $str_loc=0;
        $count=0;
        while ($count < 2) # There is no point in continuing after we find a 2nd frame
        	{

			$where1=strpos($filecontents,"\x00\x21\xF9\x04",$str_loc);
			if ($where1 === FALSE) {
			        break;
			}
			else {
			    $str_loc=$where1+1;
			    $where2=strpos($filecontents,"\x00\x2C",$str_loc);
			    if ($where2 === FALSE)
			    {
			            break;
			    }
			    else
			    {
			    	if ($where1+8 == $where2)
			    	{
			    	        $count++;
			    	}
			    	$str_loc=$where2+1;
			    }
			}
        }

        if ($count > 1)
        {
       		return(true);

        }
        else
        {
			return(false);
        }
}

/**
 * Returns all images attached to a given post
 *
 * @param object $post. (default: global $post)
 * @param string $return. (default: 'file' [file, array])
 * @return array
 */
function wpthumb_get_attached_images( $post = null, $return = 'file' ) {
	if ( $post === null )
		global $post;

	$post_id = $post->ID;

	if ( is_numeric( $post ) )
		$post_id = $post;

	if ( !is_numeric( $post_id ) )
		return false;

    $images = array();
    foreach( (array) get_children( array( 'post_parent' => $post_id, 'post_type' => 'attachment', 'orderby' => 'menu_order', 'order' => 'ASC' ) ) as $attachment ) {
    	if( !wp_attachment_is_image( $attachment->ID ) || !file_exists( get_attached_file( $attachment->ID ) ) )
    		continue;
    	if( $return === 'file' )
    		$images[] = get_attached_file( $attachment->ID );
    	elseif( $return === 'array' )
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
function phpthumb_is_image_smaller_than_dimensions( $path, $width, $height, $both = true ) {

	if( !file_exists( $path ) )
		return null;

	$dimensions = @getimagesize( $path );

	if ( $both == true && ( $dimensions[0] < $width || $dimensions[1] < $height ) ) {
		return true;
	}
}

function phpthumb_delete_cache_for_file( $file ) {
	$dir = phpthumb_calculate_image_cache_dir( $file );
	rmdirtree( $dir );
	return $file;
}
add_filter( 'wp_delete_file', 'phpthumb_delete_cache_for_file' );

//watermarking stuff


//check for submission

add_action( 'admin_head-media-upload-popup', 'wm_check_for_submitted' );
function wm_check_for_submitted() {

	if ( !empty( $_POST['wm_watermark_position'] ) ) {

		//is_multiple check
		preg_match( '/multiple=([A-z0-9_][^&]*)/', $_POST['_wp_http_referer'], $multiple_matches );
		$multiple = $multiple_matches[1];

		preg_match( '/button=([A-z0-9_][^&]*)/', $_POST['_wp_http_referer'], $matches );
		$button_id = $matches[1];

		// If the custom button was pressed
		if ( is_array( $_POST[$button_id] ) ) :

			$attach_id = key( $_POST[$button_id] );
			$attach_thumb_url = wp_get_attachment_thumb_url( $attach_id );

			update_post_meta( $attach_id, 'use_watermark', $_POST['wm_use_watermark'][$attach_id] );
			update_post_meta( $attach_id, 'wm_position', $_POST['wm_watermark_position'][$attach_id] );
			update_post_meta( $attach_id, 'wm_padding', (int) $_POST['wm_watermark_padding'][$attach_id] );
		endif;
	}
}


add_action( 'admin_head-media-upload-popup', 'wm_add_scripts' );
function wm_add_scripts() {

	if ( !isset( $_GET['button'] ) || !$_GET['button'] )
		return; ?>

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

			//show loading
			jQuery(optionsPane).next(".wm-watermark-preview").html('<span class="wm-loading">Generating Preview...</span>');

			if( typeof(WMCreatePreviewXHR) != 'undefined' )
				WMCreatePreviewXHR.abort();

			WMCreatePreviewXHR = jQuery.get(jQuery(optionsPane).find("a.preview-watermark").attr("href"), { action: 'wm_watermark_preview_image', position: position, padding: padding, image_id: jQuery(optionsPane).attr('rel') },
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

add_filter( 'attachment_fields_to_edit', 'wm_add_watermark_button', 100, 2 );
function wm_add_watermark_button( $form_fields, $media ) {

	if ( !isset( $form_fields['buttons'] ) || !strpos( $form_fields['buttons']['tr'], 'Set as Post Image' ) )
		return $form_fields;

	$button = '<a class="button add-watermark" rel="' . $media->ID . '">' . (wm_image_has_watermark($media->ID) ? 'Edit' : 'Add') .' Watermark</a>';
	$form_fields['buttons']['tr'] = substr( $form_fields['buttons']['tr'], 0, strlen($form_fields['buttons']['tr']) - 11) . $button . '</td></tr>';
	$form_fields['buttons']['tr'] .= '
		<tr style="display:none"><td></td><td>
			<div class="watermark">
			<div rel="' . $media->ID . '" class="wm-watermark-options">
				<p><label>
					<input class="wm_use_watermark" ' . (wm_image_has_watermark( $media->ID ) ? 'checked="checked"' : '') . ' type="checkbox" name="wm_use_watermark[' . $media->ID . ']" />
					<strong>Apply watermark</strong>
				</label></p>
				<p><label>Positition</label>
					<select class="wm_watermark_position" name="wm_watermark_position[' . $media->ID . ']">
						<option ' . ( wm_position($media->ID) == 'top-left' ? 'selected="selected"' : '' ) .' value="top-left">Top Left</option>
						<option ' . ( wm_position($media->ID) == 'top-right' ? 'selected="selected"' : '' ) .' value="top-right">Top Right</option>
						<option ' . ( wm_position($media->ID) == 'bottom-left' ? 'selected="selected"' : '' ) .' value="bottom-left">Bottom Left</option>
						<option ' . ( wm_position($media->ID) == 'bottom-left' ? 'selected="selected"' : '' ) .' value="bottom-right">Bottom Right</option>
					</select>
				</p>
				<p><label>Padding</label>
					<input class="wm_watermark_padding" type="text" value="' . wm_padding($media->ID) . '" style="width:30px" name="wm_watermark_padding[' . $media->ID . ']">px
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

function wm_watermark_preview_image( $position, $padding, $image_id ) {
	$image = get_attached_file($image_id);
	$watermark = array();
	$watermark['mask'] = get_template_directory() . '/images/watermark.png';

	if( $position == 'top-left' ) $watermark['position'] = 'lt';
	if( $position == 'top-right' ) $watermark['position'] = 'rt';
	if( $position == 'bottom-left' ) $watermark['position'] = 'lb';
	if( $position == 'bottom-right' ) $watermark['position'] = 'rb';

	$watermark['padding'] = (int) $padding;
	$watermark['pre_resize'] = true;

	return '<img src="' . wpthumb( $image, 200, 0, false, true, $watermark, false ) . '" /><a target="_blank" href="' . tw_phpthumb_it( $image, 1000, 0, false, true, $watermark, false ) . '">View Large</a>';
}

function wm_image_has_watermark( $image_id ) {
	return (bool) get_post_meta( $image_id, 'use_watermark', true );
}

function wm_position( $image_id ) {
	return get_post_meta( $image_id, 'wm_position', true );
}
function wm_padding( $image_id ) {
	return (int) get_post_meta( $image_id, 'wm_padding', true );
}
function wm_get_options( $id ) {
	if( !wm_image_has_watermark($id) ) return array();
	$options['mask'] = get_template_directory() . '/images/watermark.png';
	$options['padding'] = wm_padding($id);
	$position = wm_position( $id );
	if( $position == 'top-left' ) $options['position'] = 'lt';
	if( $position == 'top-right' ) $options['position'] = 'rt';
	if( $position == 'bottom-left' ) $options['position'] = 'lb';
	if( $position == 'bottom-right' ) $options['position'] = 'rb';
	$options['pre_resize'] = true;

	return $options;
}

/**
 * Removes a dir tree. I.e. recursive rmdir
 *
 * @param string $dirname
 * @return bool - success / failure
 */
function rmdirtree($dirname) {
    if (is_dir($dirname)) {    //Operate on dirs only
    	$result=array();
    	if (substr($dirname, -1)!='/') {$dirname.='/';}    //Append slash if necessary
    	$handle = opendir($dirname);
    	while (false !== ($file = readdir($handle))) {
    		if ($file!='.' && $file!= '..') {    //Ignore . and ..
    			$path = $dirname.$file;
    			if (is_dir($path)) {    //Recurse if subdir, Delete if file
    				$result=array_merge($result, rmdirtree($path));
    			}else {
    				unlink($path);
    				$result[].=$path;
    			}
    		}
    	}
    	closedir($handle);
    	rmdir($dirname);    //Remove dir
    	$result[] .= $dirname;
    	return $result;    //Return array of deleted items
    } else {
    	return false;    //Return false if attempting to operate on a file
    }
}

/**
 *
 * Error Messages 
 *
 */

function wpthumb_errors() {
    
   	$dir_upload = ABSPATH . 'wp-content/uploads/';	
    if ( file_exists( $dir_upload ) && !is_writable( $dir_upload ) )
		echo '<div id="wpthumb-warning" class="updated fade"><p><strong>' . __( 'WPThumb has detected a problem.', 'wpthumb' ) . '</strong> ' . sprintf( __( 'The directory <code>%s</code> is not writable.', 'wpthumb' ), $dir_upload ) . '</p></div>';    
}

add_action( 'admin_notices', 'wpthumb_errors' );


} //endif function_exists('wpthumb')
else {
	die( 'Looks like you are using another plugin that includes WPThumb already activated. Deactivate that plugin first.' );
}