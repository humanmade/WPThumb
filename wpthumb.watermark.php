<?php

class WP_Thumb_Watermark {

	private $args = array();
	private $editor;

	public function __construct( $editor, $args ) {

		$this->editor = $editor;

		$defaults = array(
			'padding' => 0,
			'position' => 'top,left',
			'mask' => ''
		);

		$this->args = wp_parse_args( $args['watermarking_options'], $defaults );

		$this->fill_watermark();
	}

	public function fill_watermark() {

		$image = $this->editor->get_image();
		$size = $this->editor->get_size();

		list( $mask_width, $mask_height, $mask_type, $mask_attr) = getimagesize( $this->args['mask'] );

		switch ($mask_type) {
			case 1:
				$mask = imagecreatefromgif( $this->args['mask'] );
			break;
			case 2:
				$mask = imagecreatefromjpeg( $this->args['mask'] );
			break;
			case 3:
				$mask = imagecreatefrompng( $this->args['mask'] );
			break;
		}

		imagealphablending( $image, true );

		if ( strpos( $this->args['position'], 'left' ) !== false )
			$left = $this->args['padding'];
		else
			$left = $size['width'] - $mask_width - $this->args['padding'];


		if ( strpos( $this->args['position'], 'top' ) !== false )
			$top = $this->args['padding'];
		else
			$top = $size['height'] - $mask_height - $this->args['padding'];

		imagecopy( 
			$image,
			$mask,
			$left,
			$top,
			0,
			0,
			$mask_width,
			$mask_height
		);

		$this->editor->update_image( $image );
		
		imagedestroy( $mask );
	}

}

function wpthumb_watermark_add_args_to_post_image( $args, $id ) {

	if ( wpthumb_wm_image_has_watermark( $id ) )
		$args['watermarking_options'] = wpthumb_wm_get_options( $id );

	return $args;
}
add_filter( 'wpthumb_post_image_args', 'wpthumb_watermark_add_args_to_post_image', 10, 2 );

/**
 * Hook into WP Thumb before it resizes an image to possible apply a watermatk 
 * 
 * @param  WP_IMageEditor $editor
 * @param  array $args
 */
function wpthumb_watermark_pre( $editor, $args ) {

	// currently only supports GD
	if ( ! is_a( $editor, 'WP_Thumb_Image_Editor_GD') || empty( $args['watermarking_options'] ) )
		return $editor;

	// we only want pre
	if ( empty( $args['watermarking_options']['pre_resize'] ) )
		return;

	new WP_Thumb_Watermark( $editor, $args );

	return $editor;
}
add_filter( 'wpthumb_image_pre', 'wpthumb_watermark_pre', 10, 2 );

function wpthumb_watermark_post( $editor, $args ) {

	// currently only supports GD
	if ( ! is_a( $editor, 'WP_Thumb_Image_Editor_GD') || empty( $args['watermarking_options'] ) )
		return $editor;

	// we only want pre
	if ( isset( $args['watermarking_options']['pre_resize'] ) && $args['watermarking_options']['pre_resize'] === true )
		return;

	new WP_Thumb_Watermark( $editor, $args );

	return $editor;
}
add_filter( 'wpthumb_image_post', 'wpthumb_watermark_post', 10, 2 );

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
function wpthumb_media_form_watermark_position( $fields, $post ) {

	if ( ! wp_attachment_is_image( $post->ID ) )
		return $fields;

	$current_position = get_post_meta( $post->ID, 'wpthumb_wm_position', true );

	if ( ! $current_position )
		$current_position = 'top-left';

	ob_start();

	?>

	<style>
		#wpthumb_crop_pos {  } 
		.go-left { margin-left: -93px; width: 80px; display: inline-block; margin-right: 10px; text-align: right; color: #999; }
		#wpthumb_watermark_pos { margin: 10px 0 } 
		#wpthumb_watermark_pos input { margin: 3px; width: auto; }
	</style>

	<div id="wm-options-<?php echo $post->ID ?>">
	<p>
		<label>
			<input class="wm-toggle-watermark" type="checkbox" style="width: 20px" <?php checked( wpthumb_wm_image_has_watermark( $post->ID ) ) ?>  name="attachments[<?php echo $post->ID ?>][wpthumb_wm_use_watermark]" /> Apply Watermark
		</label>
	</p>
	<div class="wpthumb_watermark_options <?php echo wpthumb_wm_image_has_watermark( $post->ID ) ? '' : 'hidden' ?>">

		<div>
			<label class="go-left">Mask</label>
			<select name="attachments[<?php echo $post->ID ?>][wm_watermark_mask]">
				<?php foreach( wpthumb_wm_get_watermark_masks() as $mask_id => $watermark_mask ) : ?>
        			<option value="<?php echo $mask_id ?>" <?php selected( wpthumb_wm_mask( $post->ID ) == $mask_id ) ?>><?php echo $watermark_mask['label'] ?></option>
    			<?php endforeach; ?>
    		</select>
    	</div>

		<div id="wpthumb_watermark_pos">
			<label class="go-left">Position</label>
			<input type="radio" name="attachments[<?php echo $post->ID ?>][wpthumb_wm_watermark_position]" value="top-left" title="Left, Top" <?php checked( 'top-left', $current_position ) ?>/>
			<input type="radio" name="attachments[<?php echo $post->ID ?>][wpthumb_wm_watermark_position]" value="top-right" title="Center, Top" <?php checked( 'top-right', $current_position ) ?> /><br />
			<input type="radio" name="attachments[<?php echo $post->ID ?>][wpthumb_wm_watermark_position]" value="bottom-left" title="Right, Top" <?php checked( 'bottom-left', $current_position ) ?> />
			<input type="radio" name="attachments[<?php echo $post->ID ?>][wpthumb_wm_watermark_position]" value="bottom-right" title="Left, Center" <?php checked( 'bottom-right', $current_position  ) ?> />
		</div>

		<div id="">
			<label class="go-left">Padding</label>
			<input type="number" value="<?php echo wpthumb_wm_padding( $post->ID ) ?>" name="attachments[<?php echo $post->ID ?>][wpthumb_wm_watermark_padding]" style="width: 40px" />
		</div>
	</div>
	</div>
	<script>

		jQuery( '#wm-options-<?php echo $post->ID ?>' ).live( 'change', '.wm-toggle-watermark', function(e) {

			jQuery( e.target ).closest( 'p' ).next().toggle();
		});
	</script>
	<?php
	$html = ob_get_clean();

	$fields['watermark-position'] = array(
		'label' => __( 'Watermark', 'wpthumb' ),
		'input' => 'html',
		'html' => $html
	);

	return $fields;

}

/**
 * Only add the watermkaring admin optins if the current theme suports it, as we don;t want to clutter
 * for poeple who don't care
 * 
 */
function wpthumb_add_watermarking_admin_hooks() {

	if ( current_theme_supports( 'wpthumb-watermarking' ) ) {
		add_filter( 'attachment_fields_to_edit', 'wpthumb_media_form_watermark_position', 10, 2 );
		add_filter( 'attachment_fields_to_save', 'wpthumb_media_form_watermark_save', 10, 2);
	}
}
add_action( 'init', 'wpthumb_add_watermarking_admin_hooks' );

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

	if ( ! empty( $attachment['wpthumb_wm_use_watermark'] ) ) {
		update_post_meta( $post['ID'], 'use_watermark', true );
		update_post_meta( $post['ID'], 'wpthumb_wm_position', $attachment['wpthumb_wm_watermark_position'] );
		update_post_meta( $post['ID'], 'wpthumb_wm_padding', (int) $attachment['wpthumb_wm_watermark_padding'] );
		update_post_meta( $post['ID'], 'wpthumb_wm_pre_resize', '0' );
		update_post_meta( $post['ID'], 'wpthumb_wm_mask', $attachment['wm_watermark_mask'] );

	} else {
		delete_post_meta( $post['ID'], 'use_watermark' );
		delete_post_meta( $post['ID'], 'wpthumb_wm_position' );
		delete_post_meta( $post['ID'], 'wpthumb_wm_padding' );
		delete_post_meta( $post['ID'], 'wpthumb_wm_pre_resize' );
		delete_post_meta( $post['ID'], 'wpthumb_wm_mask' );
	}
	
	return $post;
}	

/**
 * wpthumb_wm_get_options function.
 *
 * @access public
 * @param mixed $id
 * @return array
 */
function wpthumb_wm_get_options( $id ) {

	if ( ! wpthumb_wm_image_has_watermark( $id ) )
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

	$options['position'] = $position;

	return $options;
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
 * Returns the default watermask array ( file => string, label => string )
 *
 * @return array
 */
function wpthumb_wm_get_default_watermark_mask() {
	$masks = wpthumb_wm_get_watermark_masks();
	return $masks['default'];
}