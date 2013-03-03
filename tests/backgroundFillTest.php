<?php

/**
 * @group WPThumbBackgroundFillTestCase
 */
class WPThumbBackgroundFillTestCase extends WP_Thumb_UnitTestCase {

	function testBackgroundFillOnWhiteImage() {
		
		$path = dirname( __FILE__ ) . '/images/google.png';
		list( $width, $height ) = getimagesize( $path );
		
		$this->assertNotNull( $width );
		$this->assertNotNull( $height );
				
		$image = new WP_Thumb( $path, "width=100&height=100&cache=0&return=path&background_fill=255255255" );
		
		$file = $image->returnImage();
		
		$this->assertContains( '/cache/', $file );
		$this->assertContains( WP_CONTENT_DIR, $file );
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_width, 100, 'Width is not expected' );
		$this->assertEquals( $new_height, 100, 'Height is not expcted' );
		
		$this->assertImageRGBAtPoint( $file, array(1,1), array(255,255,255) );
	}
}
