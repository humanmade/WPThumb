<?php

class WPThumbResizeTestCase extends WP_Thumb_UnitTestCase {

	function testResizeProportional() {
	
		$path = dirname( __FILE__ ) . '/images/google.png';
		list( $width, $height ) = getimagesize( $path );
		
		$this->assertNotNull( $width );
		$this->assertNotNull( $height );
		
		$width = floor( $width / 2 );
		
		$image = new WP_Thumb( $path, "width=$width&crop=0&cache=0&return=path" );
		
		$file = $image->returnImage();
		
		$this->assertContains( '/cache/', $file );
		$this->assertContains( WP_CONTENT_DIR, $file );
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_width, $width, 'Width is not expected' );
		$this->assertEquals( $new_height, floor( $height / 2 ), 'Height is not expcted' );
	}
	
	function testResizeProportionalLargeThanSourceImage() {
		
		$path = dirname( __FILE__ ) . '/images/google.png';
		list( $width, $height ) = getimagesize( $path );
		
		$this->assertNotNull( $width );
		$this->assertNotNull( $height );
		
		$width = $width * 2;
		
		$image = new WP_Thumb( $path, "width=$width&crop=0&cache=0&return=path" );
		
		$file = $image->returnImage();
		
		$this->assertContains( '/cache/', $file );
		$this->assertContains( WP_CONTENT_DIR, $file );
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_width, $width / 2, 'Width is not expected' );
		$this->assertEquals( $new_height, floor( $height ), 'Height is not expcted' );
	}

}

