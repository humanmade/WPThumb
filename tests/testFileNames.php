<?php

class WPThumbFileNameTestCase extends WP_UnitTestCase {

	function testFileURLWithQueryParam() {

		$path = 'http://google.com/logo.png?foo=123';

		$image = new WP_Thumb;
		$image->setFilePath( $path );

		$this->assertFalse( $image->errored() );
		$this->assertContains( 'google', $image->getCacheFilePath() );

		$this->assertEquals( 'png', $image->getFileExtension() );

	}

	function testFileWithURL() {

		$path = 'http://google.com/logo.png';

		$image = new WP_Thumb;
		$image->setFilePath( $path );

		$this->assertFalse( $image->errored() );
		$this->assertContains( ABSPATH, $image->getCacheFileDirectory() );
	}

	function testFileWithDoubleSlashUrl() {

		$path = '//google.com/logo.png';

		$image = new WP_Thumb;
		$image->setFilePath( $path );

		$this->assertFalse( $image->errored() );
		$this->assertContains( ABSPATH, $image->getCacheFileDirectory() );
		$this->assertContains( 'remote', $image->getCacheFileDirectory() );
	}

	function testFileURLWithNoExtension() {

		$path = 'http://google.com/logo';

		$image = new WP_Thumb;
		$image->setFilePath( $path );

		$this->assertFalse( $image->errored() );
		$this->assertContains( ABSPATH, $image->getCacheFileDirectory() );
		$this->assertContains( 'remote', $image->getCacheFileDirectory() );
		$this->assertEquals( 'jpg', $image->getFileExtension() );

	}

	function testFileURLWithSpecialChars() {

		$path = 'http://google.com/logo~foo.png';

		$image = new WP_Thumb;
		$image->setFilePath( $path );

		$this->assertFalse( $image->errored() );
		$this->assertContains( ABSPATH, $image->getCacheFileDirectory() );
		$this->assertNotContains( '~', $image->getCacheFileDirectory() );

	}

	function testFileURLWithDotInPath() {

		$path = 'http://google.com/logo~foo.png';

		$image = new WP_Thumb;
		$image->setFilePath( $path );

		$this->assertFalse( $image->errored() );
		$this->assertContains( home_url(), $image->getCacheFileURL() );
		$this->assertNotContains( '.', str_replace( array( home_url(), '.' . $image->getFileExtension() ), array( '', '' ), $image->getCacheFileURL() ) );

	}

	function testFileWithPath() {

		$path = dirname( __FILE__ ) . '/images/google.png';

		$image = new WP_Thumb;
		$image->setFilePath( $path );

		$this->assertFalse( $image->errored() );
		$this->assertContains( ABSPATH, $image->getCacheFileDirectory() );
		$this->assertEquals( 'png', $image->getFileExtension() );

	}

	function testFileWithPathNoExtension() {

		$path = dirname( __FILE__ ) . '/images/google';

		$image = new WP_Thumb;
		$image->setFilePath( $path );

		$this->assertFalse( $image->errored() );
		$this->assertContains( ABSPATH, $image->getCacheFileDirectory() );
		$this->assertEquals( 'jpg', $image->getFileExtension() );

	}

	function testFileWithLocalURL() {

		$path = dirname( __FILE__ ) . '/images/google.png';
		$url = str_replace( ABSPATH, get_bloginfo( 'url' ) . '/', $path );

		$image = new WP_Thumb;
		$image->setFilePath( $url );

		$this->assertFalse( $image->errored() );
		$this->assertEquals( $path, $image->getFilePath() );

	}

	function testFilePathFromLocalFileUrlWithDifferentUploadDirNoMultiSite() {

		if ( is_multisite() )
			$this->markTestSkipped( 'Only runs on a MultiSite setup' );

		// For this test we need to change the upload URL to something other than uplaod path
		add_filter( 'upload_dir', $f = function( $args ) {
			$args['url'] = str_replace( 'wp-content/uploads', 'files', $args['url'] );
			$args['baseurl'] = str_replace( 'wp-content/uploads', 'files', $args['baseurl'] );

			return $args;
		} );

		$upload_dir = wp_upload_dir();

		if ( file_exists( $upload_dir['basedir'] . '/google.png' ) )
			unlink( $upload_dir['basedir'] . '/google.png' );

		copy( dirname( __FILE__ ) . '/images/google.png', $upload_dir['basedir'] . '/google.png' );

		$this->assertFileExists( $upload_dir['basedir'] . '/google.png' );

		$test_url = $upload_dir['baseurl'] . '/google.png';

		$image = new WP_Thumb( $test_url, 'width=50&height=50&crop=1' );

		$this->assertFalse( $image->errored() );

		remove_filter( 'upload_dir', $f );
	}

	function testGifIsConvertedToPNGInCacheFileName() {

		$path = dirname( __FILE__ ) . '/images/google.gif';
		$url = str_replace( ABSPATH, get_bloginfo( 'url' ) . '/', $path );

		$image = new WP_Thumb;
		$image->setFilePath( $path );

		$this->assertFalse( $image->errored() );
		$this->assertEquals( end( explode( '.', $image->getCacheFileName() ) ), 'png' );

	}

	function testLocalFileCacheFilePath() {

		$image = new WP_Thumb;
		$image->setFilePath( ABSPATH . 'foo.jpg' );

		$this->assertFalse( strpos( $image->getCacheFilePath(), '//' ) );

	}

	function testMultipleRemotePathCacheFileNames() {

		$files = array(

			'http://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/276941_243774452360675_675769393_n.jpg',
			'http://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/372977_301165979915058_1266923901_n.jpg',
			'http://fbcdn-profile-a.akamaihd.net/hprofile-ak-snc4/373643_305978336102241_1099286630_n.jpg'

		);

		$wp_thumb_files = array();

		foreach ( $files as $url ) {

			$url = wpthumb( $url, 'width=100&crop=1&height=100' );
			$this->assertNotContains( $url, $wp_thumb_files );

		}

	}

	function testResizeWithSameDimensions() {

		$path = dirname( __FILE__ ) . '/images/google';

		$dimensions = getimagesize( $path );

		$image = new WP_Thumb( $path, array( 'width' => $dimensions[0], 'height' => $dimensions[1], 'return' => 'path' ) );

		$this->assertFalse( $image->errored() );

		$this->assertEquals( $path, $image->returnImage() );

		$this->assertFileNotExists( $image->getCacheFilePath() );

	}

	function testNonExistingLocalPath() {

		$path = '/foo/bar.jpg';
		$image = new WP_Thumb( $path, array( 'width' => 10, 'height' => 10 ) );

		$this->assertTrue( $image->errored() );
	}
}