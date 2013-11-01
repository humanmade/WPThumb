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


	/**
	 * 100x100 black image.
	 * Auto fill to 200x200 with red background
	 * Expect red at mid point of each side.
	 */
	function testBackgroundFillRedForBlackImg() {
		
		$path = dirname( __FILE__ ) . '/images/black.png';
		list( $width, $height ) = getimagesize( $path );
		
		$this->assertNotNull( $width );
		$this->assertNotNull( $height );
				
		$image = new WP_Thumb( $path, "width=200&height=200&cache=0&return=path&background_fill=255000000" );
		
		$file = $image->returnImage();
		
		$this->assertContains( '/cache/', $file );
		$this->assertContains( WP_CONTENT_DIR, $file );
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_width, 200, 'Width is not expected' );
		$this->assertEquals( $new_height, 200, 'Height is not expcted' );

		$this->assertImageRGBAtPoint( $file, array(0,100), array(255,000,000), 'Left middle is wrong' );
		$this->assertImageRGBAtPoint( $file, array(100,0), array(255,000,000), 'Middle top is wrong' );
		$this->assertImageRGBAtPoint( $file, array(199,100), array(255,000,000), 'Bottom center is wrong' );
		$this->assertImageRGBAtPoint( $file, array(100,199), array(255,000,000), 'Middle bottom is wrong' );

	}

}
