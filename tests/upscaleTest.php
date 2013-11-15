<?php

/**
 * @group WPThumbUpscaleTestCase
 */
class WPThumbUpscaleTestCase extends WP_Thumb_UnitTestCase {

	function testUpscaleToMaxWidth() {

		$path = dirname( __FILE__ ) . '/images/google.png'; // 294x133
		list( $width, $height ) = getimagesize( $path );
		
		$image = new WP_Thumb( $path, "width=1000&height=1000&cache=0&return=path&upscale=1" );
		
		$file = $image->returnImage();
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_width, 1000, 'Width is not expected' );
		$this->assertEquals( $new_height, round( 1000 / $width * $height ), 'Height is not expcted' );

	}

	function testUpscaleToMaxHeight() {
		$path = dirname( __FILE__ ) . '/images/google.png'; // 294x133
		list( $width, $height ) = getimagesize( $path );
		
		$image = new WP_Thumb( $path, "width=1000&height=266&cache=0&return=path&upscale=1" );
		
		$file = $image->returnImage();
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_height, 266, 'Height is not expected' );
		$this->assertEquals( $new_width, round( 266 / $height * $width ), 'Width is not expcted' );
	}

	/**
	 * @group testUpscaleWithCrop
	 */
	function testUpscaleWithCrop() {

		$path = dirname( __FILE__ ) . '/images/google.png'; // 294x133
		list( $width, $height ) = getimagesize( $path );
		
		$image = new WP_Thumb( $path, "width=1000&height=1000&cache=0&return=path&upscale=1&crop=1" );
		
		$file = $image->returnImage();
		
		list( $new_width, $new_height ) = getimagesize( $file );
		
		$this->assertEquals( $new_height, 1000, 'Height is not expected' );
		$this->assertEquals( $new_width, 1000, 'Width is not expcted' );

	}

}

