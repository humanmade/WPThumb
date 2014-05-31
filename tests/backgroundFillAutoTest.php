<?php

/**
 * @group WPThumbBackgroundFillAutoTestCase
 */
class WPThumbBackgroundFillAutoTestCase extends WP_Thumb_UnitTestCase {

	function testBackgroundFillOnWhiteImage() {
		
		$path = dirname( __FILE__ ) . '/images/google.png';
		list( $width, $height ) = getimagesize( $path );
		
		$this->assertNotNull( $width );
		$this->assertNotNull( $height );
				
		$image = new WP_Thumb( $path, "width=1000&height=1000&crop=1&cache=0&return=path&background_fill=auto" );
		
		$file = $image->returnImage();
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_width, 1000, 'Width is not expected' );
		$this->assertEquals( $new_height, 1000, 'Height is not expected' );

		$this->assertImageRGBAtPoint( $file, array( 0, 0 ), array( 255, 255, 255 ) );
	}
	
	/**
	 * @group testBackgroundFillOnMixedColourImage
	 */
	function testBackgroundFillOnMixedColourImage() {
		
		$path = dirname( __FILE__ ) . '/images/checked.png';
		
		// How background fill the cropped imageg (which is mixed colours)
		
		$image = new WP_Thumb( $path, "width=400&height=100&crop=1&cache=0&return=path&background_fill=auto" );
			
		$file = $image->returnImage();

		$this->assertContains( '/cache/', $file );
		$this->assertContains( WP_CONTENT_DIR, $file );
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_width, 10, 'Width is not expected' );
		$this->assertEquals( $new_height, 10, 'Height is not expected' );
	
	}
	
	function testBackgroundFillOnTransparentImage() {
		
		$path = dirname( __FILE__ ) . '/images/transparent.png';
		
		// check the image is transparent
		$this->assertImageAlphaAtPoint( $path, array( 0, 0 ), 127 );
		
		$image = new WP_Thumb( $path, 'width=400&height=100&crop=1&background_fill=auto&cache=0&return=path' );
		
		$file = $image->returnImage();
		
		$this->assertImageAlphaAtPoint( $file, array( 0, 0 ), 127 );
	
	}

}
