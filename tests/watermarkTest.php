<?php

class WPThumbWatermarkTestCase extends WP_Thumb_UnitTestCase {

	function testWatermarkTopLeft() {
		
		// black 100 x 100 image
		$path = dirname( __FILE__ ) . '/images/black.png';
		$white = dirname( __FILE__ ) . '/images/white-10.png';
				
		$image = new WP_Thumb( $path, array( 'width' => 100, 'height' => 100, 'cache' => false, 'return' => 'path', 'watermark_options' => array(
			'mask' => $white,
			'position' => 'top,left',
			'padding' => 0
		) ) );
		
		$file = $image->returnImage();

		$this->assertFalse( $image->errored());
		$this->assertImageRGBAtPoint( $file, array(0,0), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(10,0), array(0,0,0) );
		$this->assertImageRGBAtPoint( $file, array(0,10), array(0,0,0) );
	}

	function testWatermarkBottomLeft() {
		
		// black 100 x 100 image
		$path = dirname( __FILE__ ) . '/images/black.png';
		$white = dirname( __FILE__ ) . '/images/white-10.png';
				
		$image = new WP_Thumb( $path, array( 'width' => 100, 'height' => 100, 'cache' => false, 'return' => 'path', 'watermark_options' => array(
			'mask' => $white,
			'position' => 'bottom,left',
			'padding' => 0
		) ) );
		
		$file = $image->returnImage();
		$this->assertImageRGBAtPoint( $file, array(0,90), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(10,90), array(0,0,0) );
	}

	function testWatermarkBottomRight() {
		
		// black 100 x 100 image
		$path = dirname( __FILE__ ) . '/images/black.png';
		$white = dirname( __FILE__ ) . '/images/white-10.png';
				
		$image = new WP_Thumb( $path, array( 'width' => 100, 'height' => 100, 'cache' => false, 'return' => 'path', 'watermark_options' => array(
			'mask' => $white,
			'position' => 'bottom,right',
			'padding' => 0
		) ) );
		
		$file = $image->returnImage();

		$this->assertImageRGBAtPoint( $file, array(90,90), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(89,90), array(0,0,0) );
	}

	function testWatermarkTopRight() {
		
		// black 100 x 100 image
		$path = dirname( __FILE__ ) . '/images/black.png';
		$white = dirname( __FILE__ ) . '/images/white-10.png';
				
		$image = new WP_Thumb( $path, array( 'width' => 100, 'height' => 100, 'cache' => false, 'return' => 'path', 'watermark_options' => array(
			'mask' => $white,
			'position' => 'top,right',
			'padding' => 0
		) ) );
		
		$file = $image->returnImage();

		$this->assertImageRGBAtPoint( $file, array(90,0), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(89,0), array(0,0,0) );
		$this->assertImageRGBAtPoint( $file, array(90,11), array(0,0,0) );
	}

	function testWatermarkPaddingTopLeft() {
		
		// black 100 x 100 image
		$path = dirname( __FILE__ ) . '/images/black.png';
		$white = dirname( __FILE__ ) . '/images/white-10.png';
				
		$image = new WP_Thumb( $path, array( 'width' => 100, 'height' => 100, 'cache' => false, 'return' => 'path', 'watermark_options' => array(
			'mask' => $white,
			'position' => 'top,left',
			'padding' => 10
		) ) );
		
		$file = $image->returnImage();

		$this->assertImageRGBAtPoint( $file, array(10,10), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(19,19), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(0,0), array(0,0,0) );
		$this->assertImageRGBAtPoint( $file, array(9,9), array(0,0,0) );
	}

	function testWatermarkPaddingBottomRight() {
		
		// black 100 x 100 image
		$path = dirname( __FILE__ ) . '/images/black.png';
		$white = dirname( __FILE__ ) . '/images/white-10.png';
				
		$image = new WP_Thumb( $path, array( 'width' => 100, 'height' => 100, 'cache' => false, 'return' => 'path', 'watermark_options' => array(
			'mask' => $white,
			'position' => 'bottom,right',
			'padding' => 10
		) ) );
		
		$file = $image->returnImage();

		$this->assertImageRGBAtPoint( $file, array(80,80), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(89,89), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(90,90), array(0,0,0) );
		$this->assertImageRGBAtPoint( $file, array(99,99), array(0,0,0) );
	}

	function testWatermarkPostResizeDefault() {
		
		// black 100 x 100 image
		$path = dirname( __FILE__ ) . '/images/black.png';
		$white = dirname( __FILE__ ) . '/images/white-10.png';
				
		$image = new WP_Thumb( $path, array( 'width' => 10, 'height' => 10, 'cache' => false, 'return' => 'path', 'watermark_options' => array(
			'mask' => $white,
			'position' => 'top,left',
			'padding' => 0
		) ) );
		
		$file = $image->returnImage();

		$this->assertImageRGBAtPoint( $file, array(0,0), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(9,9), array(255,255,255) );
	}

	function testWatermarkPreResize() {
		
		// black 100 x 100 image
		$path = dirname( __FILE__ ) . '/images/black.png';
		$white = dirname( __FILE__ ) . '/images/white-10.png';
				
		$image = new WP_Thumb( $path, array( 'width' => 10, 'height' => 10, 'cache' => false, 'return' => 'path', 'watermark_options' => array(
			'mask' => $white,
			'position' => 'top,left',
			'padding' => 0,
			'pre_resize' => true
		) ) );
		
		$file = $image->returnImage();

		$this->assertImageRGBAtPoint( $file, array(0,0), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(3,3), array(0,0,0) );
	}

	function testWatermarkPostResize() {
		
		// black 100 x 100 image
		$path = dirname( __FILE__ ) . '/images/black.png';
		$white = dirname( __FILE__ ) . '/images/white-10.png';
				
		$image = new WP_Thumb( $path, array( 'width' => 10, 'height' => 10, 'cache' => false, 'return' => 'path', 'watermark_options' => array(
			'mask' => $white,
			'position' => 'top,left',
			'padding' => 0,
			'pre_resize' => false
		) ) );
		
		$file = $image->returnImage();

		$this->assertImageRGBAtPoint( $file, array(0,0), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(9,9), array(255,255,255) );
	}
}
