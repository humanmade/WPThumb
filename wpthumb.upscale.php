<?php

class WP_Thumb_Upscale {

	private $args = array();
	private $editor;

	public function __construct( $editor, $args ) {

		$this->editor = $editor;

		$this->args = $args;

		$this->upscale_image();
	}

	/**
	 * 
	 */
	public function upscale_image() {

		$size = $this->editor->get_size();
		$args = $this->args;
		$new_size = array();

		//check if we need to upscale at all
		if ( $args['width'] <= $size['width'] && $args['height'] <= $size['height'] )
			return;

		if ( empty( $args['crop'] ) ) {
			// need to scale based off height
			if ( $size['width'] >= $args['width'] ) {

				$new_size['height'] = $args['height'];
				$new_size['width'] = ($size['width']/$size['height'])*$new_size['height'];
			} 

			// need to scale based off width 
			else {

				$new_size['width'] = $args['width'];
				$new_size['height'] = ($size['height']/$size['width'])*$new_size['width'];

			}	
		} else {
			// need to scale based off height
			if ( $size['width'] < $args['width'] ) {

				$new_size['height'] = $args['height'];
				$new_size['width'] = ($size['width']/$size['height'])*$new_size['height'];
			} 

			// need to scale based off width 
			else {

				$new_size['width'] = $args['width'];
				$new_size['height'] = ($size['height']/$size['width'])*$new_size['width'];

			}
		}	

		$new_image = imagecreatetruecolor( $new_size['width'], $new_size['height'] );

		// This is needed to support alpha
		imagesavealpha( $new_image, true );
		imagealphablending( $new_image, false );

		imagecopyresized( 
			$new_image, 
			$this->editor->get_image(), 
			0, 0, 0, 0, 
			$new_size['width'], $new_size['height'],
			$size['width'], $size['height'] );

		$this->editor->update_image( $new_image );
		$this->editor->update_size();
	}

}

function wpthumb_upscale( $editor, $args ) {

	// currently only supports GD
	if ( is_a( $editor, 'WP_Thumb_Image_Editor_GD') == false || empty( $args['upscale'] ) )
		return $editor;

	$bg = new WP_Thumb_Upscale( $editor, $args );

	return $editor;
}
add_filter( 'wpthumb_image_pre', 'wpthumb_upscale', 9, 2 );