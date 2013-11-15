<?php

abstract class WP_Thumb_Save_Location {

	public function __construct( WP_Thumb $wp_thumb ) {
		$this->wp_thumb = $wp_thumb;
	}
	
	/**
	 * @return bool
	 */
	abstract function fileExists();

	abstract function save( $file_path );

	abstract function delete();

	abstract function getPath();
}