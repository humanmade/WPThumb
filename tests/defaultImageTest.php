<?php

class WPThumbDefaultImageTestCase extends WP_UnitTestCase {

	function testDefaultImageInFilePath() {
		
		$file_path = ABSPATH . 'foo.png';
		$default = dirname( __FILE__ ) . '/images/google.png';
		
		$thumb = new WP_Thumb( $file_path, array( 'default' => $default, 'width' => 20, 'height' => 20 ) );

		$this->assertEquals( $thumb->getFilePath(), $default );
		$this->assertFalse( $thumb->errored() );
		
	}
	
}
	