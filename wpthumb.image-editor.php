<?php

class WP_Thumb_Image_Editor_GD extends WP_Image_Editor_GD {

	public function get_image() {
		return $this->image;
	}

	public function update_image( $image ) {
		$this->image = $image;
	}

	public function update_size( $width = null, $height = null ) {
		return parent::update_size( $width, $height );
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
		return parent::update_size( $width, $height );
	}
}
