<?php

class WP_Thumb_Save_Location_Database extends WP_Thumb_Save_Location_Cache_Directory {

	public function fileExists() {
		

		$args = $this->wp_thumb->getArgs();

		$stored = $args['attachment_id'] ? get_post_meta( $args['attachment_id'], 'wp_thumb_saved_images', true ) : get_option( 'wp_thumb_saved_images', array() );

		if ( $args['attachment_id'] )
			$cached_images = $stored ? $stored : array();
		else
			$cached_images = ( ! empty( $stored[$this->wp_thumb->getFilePath()] ) ) ? $stored[$this->wp_thumb->getFilePath()] : array();

		//var_export( $cached_images[md5( serialize( $args ))] );
		//exit;
		return ! empty( $cached_images[md5( serialize( $args ))] );
	}

	public function save( $file_path ) {

		parent::save( $file_path );
		$this->saveFileExists( $this->getPath(), true );
	}

	public function delete() {

		parent::delete();
		$this->deleteFileExists();
	}

	public function saveFileExists( $file_path ) {

		$args = $this->wp_thumb->getArgs();

		$cached_images = ( $args['attachment_id'] ) ? get_post_meta( $args['attachment_id'], 'wp_thumb_saved_images', true ) : get_option( 'wp_thumb_saved_images', array() );

		if ( $args['attachment_id'] ) {

			$cached_images[md5( serialize( $args ))] = $file_path;

			update_post_meta( $args['attachment_id'], 'wp_thumb_saved_images', $cached_images );

		} else {

			if ( empty( $cached_images[$this->wp_thumb->getFilePath()] ) )
				$cached_images[$this->wp_thumb->getFilePath()] = array();

			$cached_images[$this->wp_thumb->getFilePath()][md5( serialize( $args ))] = $file_path;

			update_option( 'wp_thumb_saved_images', $cached_images );
		}
	}

	public function deleteFileExists() {

		$args = $this->wp_thumb->getArgs();

		$cached_images = ( $args['attachment_id'] ) ? get_post_meta( $args['attachment_id'], 'wp_thumb_saved_images', true ) : get_option( 'wp_thumb_saved_images', array() );

		if ( $args['attachment_id'] ) {

			if ( ! empty( $cached_images[md5( serialize( $args ))]  ) )
				unset( $cached_images[md5( serialize( $args ))] );

			update_post_meta( $args['attachment_id'], 'wp_thumb_saved_images', $cached_images );

		} else {

			if ( ! empty( $cached_images[$this->wp_thumb->getFilePath()][md5( serialize( $args ))] ) )
				unset( $cached_images[$this->wp_thumb->getFilePath()][md5( serialize( $args ))] );

			update_option( 'wp_thumb_saved_images', $cached_images );
		}
	}
}

