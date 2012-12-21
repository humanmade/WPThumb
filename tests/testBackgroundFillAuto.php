<?php

class WPThumbBackgroundFillAutoTestCase extends WP_UnitTestCase {

	function testBackgroundFillOnWhiteImage() {
		
		$path = dirname( __FILE__ ) . '/images/google.png';
		list( $width, $height ) = getimagesize( $path );
		
		$this->assertNotNull( $width );
		$this->assertNotNull( $height );
				
		$image = new WP_Thumb( $path, "width=100&height=100&crop=1&cache=0&return=path&background_fill=auto" );
		
		$file = $image->returnImage();
		
		$this->assertContains( '/cache/', $file );
		$this->assertContains( WP_CONTENT_DIR, $file );
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_width, 100, 'Width is not expected' );
		$this->assertEquals( $new_height, 100, 'Height is not expcted' );
	
	}
	
	function testBackgroundFillOnMixedColourImage() {
		
		$path = dirname( __FILE__ ) . '/images/google.png';
		list( $width, $height ) = getimagesize( $path );
		
		$this->assertNotNull( $width );
		$this->assertNotNull( $height );
				
		$image = new WP_Thumb( $path, "width=100&height=100&crop=1&cache=0&return=path" );
		
		$file = $image->returnImage();
		
		$this->assertContains( '/cache/', $file );
		$this->assertContains( WP_CONTENT_DIR, $file );
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_width, 100, 'Width of text image is not expected' );
		$this->assertEquals( $new_height, 100, 'Height of text image is not expcted' );
		
		// How bacbkground fill the cropped imageg (which is mixed colours)
		
		$image = new WP_Thumb( $file, "width=400&height=100&crop=1&cache=0&return=path&background_fill=auto" );
		
		$file = $image->returnImage();
		
		$this->assertContains( '/cache/', $file );
		$this->assertContains( WP_CONTENT_DIR, $file );
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_width, 100, 'Width is not expected' );
		$this->assertEquals( $new_height, 100, 'Height is not expcted' );
	
	}
	
	function testBackgroundFillOnTransparentImage() {
	
		$path = dirname( __FILE__ ) . '/images/transparent.png';
		
		// check the iamge is transparent
		$this->assertImageAlphaAtPoint( $path, array( 0, 0 ), 127 );
		
		$image = new WP_Thumb( $path, 'width=400&height=100&crop=1&background_fill=auto&cache=0&return=path' );
		
		$file = $image->returnImage();

		$this->assertImageAlphaAtPoint( $file, array( 0, 0 ), 127 );
	
	}

}
