<?php

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
				
		$orig = new WP_Thumb( get_attached_file( $id ), 'width=600&height=900' );
		
		return wpthumb( $orig->getCacheFilePath(), $args );

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
 * wpthumb_media_form_crop_position function.
 *
 * Adds a back end for selecting the crop position of images.
 *
 * @access public
 * @param array $fields
 * @param array $post
 * @return $post
 */
function wpthumb_media_form_watermark( $fields, $post ) {

    $current_position = wpthumb_wm_position( $post->ID );
	
	$watermark_masks_options_html = '';
    foreach( wpthumb_wm_get_watermark_masks() as $mask_id => $watermark_mask ) {
        $watermark_masks_options_html .= '<option value="' . $mask_id . '" ' . ( wpthumb_wm_mask( $post->ID ) == $mask_id ? 'selected="selected"' : '' ) . '>' . $watermark_mask['label'] . '</option>' . "\n";
    }
    
    if ( !$current_position )
    	$current_position = 'top,left';
    	
    if ( isset( $_GET['post_id'] ) )
		$calling_post_id = absint( $_GET['post_id'] );
	elseif ( isset( $_POST ) && count( $_POST ) ) // Like for async-upload where $_GET['post_id'] isn't set
		$calling_post_id = $post->post_parent;
	else
		$calling_post_id = false;

    $html = '<style>.watermark_pos { } .watermark_pos input { margin: 5px; } .wpthumb_wrap { display: inline-block; padding: 0 5px; }</style>';
    $html .= '<div rel="' . $post->ID . '" class="wm-watermark-options"><label><input class="wpthumb_apply_watermark" name="attachments[' . $post->ID . '][wpthumb_wm_use_watermark]" type="checkbox"' . checked( wpthumb_wm_image_has_watermark( $post->ID ), true, false ) . ' /> Apply Watermark</label>';
    $html .= '<div class="watermark_pos" style="display:' . ( wpthumb_wm_image_has_watermark( $post->ID ) ? 'block' : 'none' ) . '; ">';
    $html .= '<br /><span class="wpthumb_wrap"><label>Position</label><select class="wm_watermark_position" name="attachments[' . $post->ID . '][wpthumb_wm_watermark_position]">
         	    <option ' . ( wpthumb_wm_position($post->ID) == 'top-left' ? 'selected="selected"' : '' ) .' value="top-left">Top Left</option>
         	    <option ' . ( wpthumb_wm_position($post->ID) == 'top-right' || wpthumb_wm_position($post->ID) == '' ? 'selected="selected"' : '' ) .' value="top-right">Top Right</option>
         	    <option ' . ( wpthumb_wm_position($post->ID) == 'bottom-left' ? 'selected="selected"' : '' ) .' value="bottom-left">Bottom Left</option>
         	    <option ' . ( wpthumb_wm_position($post->ID) == 'bottom-right' ? 'selected="selected"' : '' ) .' value="bottom-right">Bottom Right</option>
         	</select></span>
         	
         	<span class="wpthumb_wrap"><label>Padding</label><input class="wm_watermark_padding" type="text" value="' . wpthumb_wm_padding($post->ID) . '" style="width:30px" name="attachments[' . $post->ID . '][wpthumb_wm_watermark_padding]">px</span>
           	
           	<span class="wpthumb_wrap"><label>Select Watermark</label>
            <select name="attachments[' . $post->ID . '][wm_watermark_mask]" class="wm_watermark_mask">
            	' . $watermark_masks_options_html . '
			</select></span>
			
			<span class="wpthumb_wrap">
				<a class="button preview-watermark" href="' . str_replace( ABSPATH, get_bloginfo('url') . '/', dirname( __FILE__ )) . '/watermark-actions.php">Preview</a> 
				' . ( $calling_post_id ? '<a class="button-primary save-watermark" href="#">Save</a>' : '' ) . '
			</span>
			
			<br /><span class="wm-watermark-preview"></span>
                ';
	
	$html .= '</div></div>';
	
	ob_start();
	
	?>
    <script type="text/javascript">
    	
    	jQuery( document ).ready( function() { 
    		
    		jQuery( "div[rel='<?php echo $post->ID ?>'] .wpthumb_apply_watermark" ).change( function() { 
    			jQuery(this).parent().next( ".watermark_pos" ).toggle() 
    		
    		} ) 
    		
    		jQuery("div[rel='<?php echo $post->ID ?>'].wm-watermark-options a.preview-watermark").live("click", function(e) {
    		    e.preventDefault();
    		    WMCreatePreview( jQuery(this).closest(".wm-watermark-options") );
    		});
    		
    		jQuery("div[rel='<?php echo $post->ID ?>'].wm-watermark-options a.save-watermark").live("click", function(e) {
    		    e.preventDefault();
    		    WMSaveWatermark( jQuery(this).closest(".wm-watermark-options"), this );
    		});

    		function WMCreatePreview( optionsPane ) {
    		    position = jQuery(optionsPane).find("select.wm_watermark_position").val();
    		    padding = jQuery(optionsPane).find("input.wm_watermark_padding").val();
    		    mask = jQuery(optionsPane).find("select.wm_watermark_mask").val();
    		    
    		    //show loading
    		    jQuery(optionsPane).next(".wm-watermark-preview").html("<span class=\"wm-loading\">Generating Preview...</span>");
    		    
    		    if( typeof(WMCreatePreviewXHR) != "undefined" )
    		        WMCreatePreviewXHR.abort();
    		        
    		    WMCreatePreviewXHR = jQuery.get(jQuery(optionsPane).find("a.preview-watermark").attr("href"), { action: "wpthumb_wm_watermark_preview_image", position: position, padding: padding, image_id: jQuery(optionsPane).attr("rel"), mask: mask },
    		    function(data){    
    		        jQuery(optionsPane).find(".wm-watermark-preview").html(data).show();
    		    });
    		}
    		
    		function WMSaveWatermark( optionsPane, elem ) {
    		    position = jQuery(optionsPane).find("select.wm_watermark_position").val();
    		    padding = jQuery(optionsPane).find("input.wm_watermark_padding").val();
    		    mask = jQuery(optionsPane).find("select.wm_watermark_mask").val();
    		    elem = jQuery( elem );
    		    
    		    //show loading
    		   	elem.text( 'Saving...' );
    		    
    		    if( typeof(WMCreatePreviewXHR) != "undefined" )
    		        WMCreatePreviewXHR.abort();
    		        
    		    WMSaveWatermarkXHR = jQuery.post( ajaxurl, 
    		    	{ 
    		    		action: "wpthumb_watermark_save", 
    		    		position: position, 
    		    		padding: padding, 
    		    		image_id: jQuery(optionsPane).attr("rel"), 
    		    		mask: mask,
    		    		post_id: <?php echo $calling_post_id ?>
    		    	},
    		    	
    		    	function(data){    
    		    		elem.text( 'Saved!' );
    		    		
    		    		setTimeout( function() {
    		    			elem.text( 'Save' );
    		    		}, 2000 );
    		    		
    		    		// if this was the featured image, lets call an update on the post thumbnail
    		    		// so it shows the watermark in the Featured Image box
    		    		var win = window.dialogArguments || opener || parent || top;
    		    		<?php if ( function_exists( 'get_post_thumbnail_id' ) && get_post_thumbnail_id( $calling_post_id ) ) : ?>
    		    			if ( jQuery(optionsPane).attr("rel") == <?php echo get_post_thumbnail_id( $calling_post_id ) ?> )
	    		    			win.WPSetThumbnailHTML( data );
	    		    	<?php endif; ?>
    		    	}
    		    );
    		}
    	} );
    	</script>
    	<style>
    		    /* .A1B1 input[type=button] { display: none; } */
    		    .wm-watermark-preview img { padding: 3px; border: 1px solid #a1a1a1; }
    		    .wm-watermark-preview a {font-size: 11px; text-decoration: none; text-align: center; display :block; width: 200px; }
    		    .wm-loading { line-height: 16px; text-align: center; width: 120px; background: url(<?php bloginfo('url') ?>/wp-admin/images/loading.gif) no-repeat; padding-left: 20px; padding-top: 1px; padding-bottom: 2px; font-size: 11px; color: #999; }
    	</style>
    
    <?php
    
    $html .= ob_get_contents();
    ob_end_clean();

    $fields['watermark'] = array(
    	'label' => __('Watermark', 'wpthumb'),
    	'input' => 'html',
    	'html' => $html
    );
    return $fields;
}
add_filter( 'attachment_fields_to_edit', 'wpthumb_media_form_watermark', 10, 2 );

/**
 * wpthumb_media_form_watermark_save function.
 *
 * Saves watermark in post meta.
 *
 * @access public
 * @param array $post
 * @param array $attachment
 * @return $post
 */
function wpthumb_media_form_watermark_save( $post, $attachment ){

    update_post_meta( $post['ID'], 'use_watermark', ! empty( $attachment['wpthumb_wm_use_watermark'] ) );
    update_post_meta( $post['ID'], 'wpthumb_wm_position', $attachment['wpthumb_wm_watermark_position'] );
    update_post_meta( $post['ID'], 'wpthumb_wm_padding', (int) $attachment['wpthumb_wm_watermark_padding'] );
	update_post_meta( $post['ID'], 'wpthumb_wm_pre_resize', '0' );
	update_post_meta( $post['ID'], 'wpthumb_wm_mask', $attachment['wm_watermark_mask'] );
	
    return $post;
}
add_filter( 'attachment_fields_to_save', 'wpthumb_media_form_watermark_save', 10, 2);

/**
 * Handles the ajax save button in edit form fields
 * 
 * @access public
 * @return null
 */
function wpthumb_save_watermark_ajax_action() {
	
	$attachment_id = (int) $_POST['image_id'];
	$position = (string) $_POST['position'];
	$padding = (int) $_POST['padding'];
	$mask = (string) $_POST['mask'];
	
	update_post_meta( $attachment_id, 'use_watermark', true);
    update_post_meta( $attachment_id, 'wpthumb_wm_position', $position );
    update_post_meta( $attachment_id, 'wpthumb_wm_padding', $padding );
	update_post_meta( $attachment_id, 'wpthumb_wm_pre_resize', '0' );
	update_post_meta( $attachment_id, 'wpthumb_wm_mask', $mask );
	
	// if the attachment is the post thumbnail, return the post thubmnail html
	// to update the featured image box with
	die( _wp_post_thumbnail_html( $attachment_id ) );
	
}
add_action( 'wp_ajax_wpthumb_watermark_save', 'wpthumb_save_watermark_ajax_action' );