<?php

class WP_Thumb_Upscale {

	private $args = array();
	private $editor;

	public function __construct( $editor, $args ) {

		$this->editor = $editor;

		$defaults = array();

		$this->args = wp_parse_args( $args, $defaults );
		$size = $this->editor->get_size();

		if ( $args['crop'] ) {

			if ( $size['width'] < $args['width'] || $size['height'] < $args['height'] )
				$this->upscale_gd();

		} else if ( $size['width'] < $args['width'] && $size['height'] < $args['height'] ) {

		}
	}

	private function upscale_gd() {

		$size = $this->editor->get_size();

		$image = imagecreatetruecolor( $this->args['width'], $this->args['height'] );

		imagecopyresampled( $image, $this->editor->get_image(), 0, 0, 100, 0, $this->args['width'], $this->args['height'], $size['width'], $size['height'] );

		$this->editor->update_image( $image );
		$this->editor->update_size( $this->args['width'], $this->args['height'] );
	}

}

function wpthumb_upscale( $editor, $args ) {

	// currently only supports GD
	if ( ! is_a( $editor, 'WP_Thumb_Image_Editor_GD') )
		return $editor;

	if ( empty( $args['upscale'] ) )
		return $editor;

	$bg = new WP_Thumb_Upscale( $editor, $args );

	return $editor;
}
add_filter( 'wpthumb_image_pre', 'wpthumb_upscale', 10, 2 );