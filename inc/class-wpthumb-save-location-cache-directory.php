<?php 

class WP_Thumb_Save_Location_Cache_Directory extends WP_Thumb_Save_Location {

	public function fileExists() {

		return file_exists( $this->getPath() );
	}

	public function save( $file_path ) {

		wp_mkdir_p( $this->getCacheFileDirectory() );

		copy( $file_path, $this->getPath() );

	}

	public function delete() {

		$this->wpthumb_rmdir_recursive( $this->getCacheFileDirectory() );
	}

	public function getPath() {
		return $this->getCacheFileDirectory() . '/' . $this->getCacheFileName();
	}

	/**
	 * Get the directory that the cache file should be saved too
	 *
	 * @return string
	 */
	public function getCacheFileDirectory() {

		if ( $this->wp_thumb->getArg( 'output_file' ) )
			return dirname( $this->wp_thumb->getArg( 'output_file' ) );

		$path = $this->wp_thumb->getFilePath();

		if ( ! $path )
			return '';

		$original_filename = basename( $this->wp_thumb->getFilePath() );

		// TODO use pathinfo
		$parts = explode( '.', $original_filename );

		array_pop( $parts );

		$filename_nice = implode( '_', $parts );

		$upload_dir = $this->wp_thumb->uploadDir();

		if ( strpos( $this->wp_thumb->getFilePath(), $upload_dir['basedir'] ) === 0 ) :

			$subdir = dirname( str_replace( $upload_dir['basedir'], '', $this->wp_thumb->getFilePath() ) );
			$new_dir = $upload_dir['basedir'] . '/cache' . $subdir . '/' . $filename_nice;

		elseif ( strpos( $this->wp_thumb->getFilePath(), WP_CONTENT_DIR ) === 0 ) :

			$subdir = dirname( str_replace( WP_CONTENT_DIR, '', $this->wp_thumb->getFilePath() ) );
			$new_dir = $upload_dir['basedir'] . '/cache' . $subdir . '/' . $filename_nice;

		elseif ( strpos( $this->wp_thumb->getFilePath(), $this->wp_thumb::get_home_path() ) === 0 ) :
			$new_dir = $upload_dir['basedir'] . '/cache/local';

		else :

			$parts = parse_url( $this->wp_thumb->getFilePath() );

			if ( ! empty( $parts['host'] ) )
				$new_dir = $upload_dir['basedir'] . '/cache/remote/' . sanitize_title( $parts['host'] );

			else
				$new_dir = $upload_dir['basedir'] . '/cache/remote';

		endif;

		// TODO unit test for whether this is needed or not
		$new_dir = str_replace( '/cache/cache', '/cache', $new_dir );

		return $new_dir;
	}

	/**
	 * Get the filename of the cache file
	 *
	 * @return string
	 */
	public function getCacheFileName() {

		if ( $this->wp_thumb->getArg( 'output_file' ) )
			return basename( $this->wp_thumb->getArg( 'output_file' ) );

		$path = $this->wp_thumb->getFilePath();

		if ( ! $path )
			return '';

		// Generate a short unique filename
		$serialize = crc32( serialize( array_merge( $this->wp_thumb->getArgs(), array( $this->wp_thumb->getFilePath() ) ) ) );

		// Gifs are converted to pngs
		if ( $this->wp_thumb->getFileExtension() == 'gif' )
			return $serialize . '.png';

		return $serialize . '.' . $this->wp_thumb->getFileExtension();

	}


	/**
	 * Removes a dir tree. I.e. recursive rmdir
	 *
	 * @param string $dir
	 * @return bool - success / failure
	 */
	private function wpthumb_rmdir_recursive( $dir ) {

		if ( ! is_dir( $dir ) )
			return false;

		$dir = trailingslashit( $dir );

		$handle = opendir( $dir );

		while ( false !== ( $file = readdir( $handle ) ) ) {

			if ( $file == '.' || $file == '..' )
				continue;

			$path = $dir . $file;

			if ( is_dir( $path ) )
				$this->wpthumb_rmdir_recursive( $path );

			else
				unlink( $path );

		}

		closedir( $handle );

		rmdir( $dir );

	}
}

