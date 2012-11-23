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

function wpthumb_watermark_pre( $editor, $args ) {

	// currently only supports GD
	if ( ! is_a( $editor, 'WP_Thumb_Image_Editor_GD') || empty( $args['watermarking_options'] ) )
		return $editor;

	// we only want pre
	if ( isset( $args['watermarking_options']['pre_resize'] ) && $args['watermarking_options']['pre_resize'] != true )
		return;

	$bg = new WP_Thumb_Watermark( $editor, $args );

	return $editor;
}
add_filter( 'wpthumb_image_pre', 'wpthumb_watermark_pre', 10, 2 );

function wpthumb_watermark_post( $editor, $args ) {

	// currently only supports GD
	if ( ! is_a( $editor, 'WP_Thumb_Image_Editor_GD') || empty( $args['watermarking_options'] ) )
		return $editor;

	// we only want pre
	if ( isset( $args['watermarking_options']['pre_resize'] ) && $args['watermarking_options']['pre_resize'] == false )
		$bg = new WP_Thumb_Watermark( $editor, $args );

	return $editor;
}
add_filter( 'wpthumb_image_post', 'wpthumb_watermark_post', 10, 2 );