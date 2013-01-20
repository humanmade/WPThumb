<?php

class WPThumbCropFromPositionTestCase extends WP_UnitTestCase {

	function testCropFromTopLeftNoResize() {
		
		// 10 x 10
		$path = dirname( __FILE__ ) . '/images/boxed.png';
				
		$image = new WP_Thumb( $path, array( 
			'width' => 5, 
			'height' => 5, 
			'cache' => false, 
			'return' => 'path',
			'crop' => true,
			'crop_from_position' => 'top,left',
			'resize' => false ) );
		
		$file = $image->returnImage();
		$this->assertImageRGBAtPoint( $file, array(0,0), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(4,4), array(0,0,0) );
		$this->assertImageRGBAtPoint( $file, array(0,1), array(255,255,255) );
	}

	function testCropFromTopLeftWithResize() {
		
		// 10x10 image
		$path = dirname( __FILE__ ) . '/images/boxed-rectangle.png';
				
		$image = new WP_Thumb( $path, array( 
			'width' => 5, 
			'height' => 5, 
			'cache' => false, 
			'return' => 'path',
			'crop' => true,
			'crop_from_position' => 'top,left',
			'resize' => true ) );
		
		$file = $image->returnImage();

		$this->assertImageRGBAtPoint( $file, array(0,0), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(0,4), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(1,1), array(0,0,0) );
	}

	function testCropFromBottomRightNoResize() {
		
		// 20x10 image
		$path = dirname( __FILE__ ) . '/images/boxed.png';
				
		$image = new WP_Thumb( $path, array( 
			'width' => 5, 
			'height' => 5, 
			'cache' => false, 
			'return' => 'path',
			'crop' => true,
			'crop_from_position' => 'bottom,right',
			'resize' => false ) );
		
		$file = $image->returnImage();
		$this->assertImageRGBAtPoint( $file, array(0,0), array(0,0,0) );
		$this->assertImageRGBAtPoint( $file, array(4,4), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(0,1), array(0,0,0) );
	}

	function testCropFromBottomRightWithResize() {
		
		// 20x10 image
		$path = dirname( __FILE__ ) . '/images/boxed-rectangle.png';
				
		$image = new WP_Thumb( $path, array( 
			'width' => 5, 
			'height' => 5, 
			'cache' => false, 
			'return' => 'path',
			'crop' => true,
			'crop_from_position' => 'bottom,right',
			'resize' => true ) );
		
		$file = $image->returnImage();
		
		$this->assertImageRGBAtPoint( $file, array(0,0), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(0,4), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(0,1), array(0,0,0) );
		$this->assertImageRGBAtPoint( $file, array(4,2), array(255,255,255) );
	}

	function testCropFromCenterCenterWithResize() {
		
		// 20x10 image
		$path = dirname( __FILE__ ) . '/images/boxed-rectangle.png';
				
		$image = new WP_Thumb( $path, array( 
			'width' => 5, 
			'height' => 5, 
			'cache' => false, 
			'return' => 'path',
			'crop' => true,
			'crop_from_position' => 'center,center',
			'resize' => true ) );
		
		$file = $image->returnImage();
		
		$this->assertImageRGBAtPoint( $file, array(0,0), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(0,4), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(0,1), array(0,0,0) );
		$this->assertImageRGBAtPoint( $file, array(4,2), array(0,0,0) );
	}

	function testCropFromTopCenterWithResize() {
		
		// 20x10 image
		$path = dirname( __FILE__ ) . '/images/boxed-rectangle.png';
				
		$image = new WP_Thumb( $path, array( 
			'width' => 10, 
			'height' => 4, 
			'cache' => false, 
			'return' => 'path',
			'crop' => true,
			'crop_from_position' => 'center,center',
			'resize' => true ) );
		
		$file = $image->returnImage();

		$this->assertImageRGBAtPoint( $file, array(0,0), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(0,1), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(9,0), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(9,1), array(255,255,255) );
		$this->assertImageRGBAtPoint( $file, array(1,1), array(0,0,0) );
	}
}