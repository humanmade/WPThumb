<?php 

class WP_Thumb_Image_Editor_GD extends WP_Image_Editor_GD {

	public function get_image() {
		return $this->image;
	}

	public function update_image( $image ) {
		$this->image = $image;
	}

	public function update_size( $width = null, $height = null ) {
		parent::update_size( $width, $height);
	}
}

class WP_Thumb_Image_Editor_Imagick extends WP_Image_Editor_Imagick {

	public function get_image() {
		return $this->image;
	}

	public function update_image( $image ) {
		$this->image = $image;
	}

	public function update_size( $width = null, $height = null ) {
		parent::update_size( $width, $height);
	}
}

function wpthumb_add_image_editors( $editors ) {
	$editors[] = 'WP_Thumb_Image_Editor_GD';
	$editors[] = 'WP_Thumb_Image_Editor_Imagick';

	return $editors;
}
add_filter( 'wp_image_editors', 'wpthumb_add_image_editors' );