<?php

class WPThumbRemoteImageTestCase extends WP_UnitTestCase {

	function testFetchRemoteImage() {
		
		// Image is 275x95
		$url = 'http://www.google.com/intl/en_com/images/srpr/logo3w.png';
		
		$image = new WP_Thumb( $url, "crop=0&return=path" );
		
		$file = $image->returnImage();
		
		$this->assertContains( '/cache/', $file );
		$this->assertContains( ABSPATH, $file );
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_width, 275, 'Width is not expected' );
		$this->assertEquals( $new_height, 95, 'Height is not expcted' );
	
	}
	
	function testResizeRemoteImage() {
		
		// Image is 275x95
		$url = 'http://www.google.com/intl/en_com/images/srpr/logo3w.png';
		
		$image = new WP_Thumb( $url, "crop=1&return=path&width=80&height=80&cache=0" );
		
		$file = $image->returnImage();
		
		$this->assertContains( '/cache/', $file );
		$this->assertContains( ABSPATH, $file );
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_width, 80, 'Width is not expected' );
		$this->assertEquals( $new_height, 80, 'Height is not expcted' );
	
	}
	
	function testResizeRemote404Image() {
		
		// Image is 275x95
		$url = 'http://www.google.com/intl/en_com/images/srpr/logoawd3w.png';
		
		$image = new WP_Thumb( $url, "crop=1&return=path&width=80&height=80&cache=0" );
		
		$file = $image->returnImage();
		
		$this->assertEquals( $url, $file );
		$this->assertTrue( $image->errored() );
		
	}
	
	function testDifferentFileURLSWithSameArgs() {
	
		$url_1 = 'http://www.google.com/images/srpr/logo3w.png';
		$image_1 = new WP_Thumb( $url_1, "crop=1&return=path&width=80&height=80&cache=0" );

		$url_2 = 'http://www.google.co.uk/images/srpr/logo3w.png';
		$image_2 = new WP_Thumb( $url_2, "crop=1&return=path&width=80&height=80&cache=0" );
		
		$this->assertNotEquals( $image_1->getCacheFilePath(), $image_2->getCacheFilePath() );

	}
	
}