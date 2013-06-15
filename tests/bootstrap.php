<?php

require_once getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../wpthumb.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';

class WP_Thumb_UnitTestCase extends WP_UnitTestCase {

	function assertImageRGBAtPoint( $image_path, $point, $color ) {

		$im = imagecreatefrompng( $image_path );
		$rgb = imagecolorat($im, $point[0], $point[1]);

		$colors = imagecolorsforindex($im, $rgb);
		$colors = array( $colors['red'], $colors['green'], $colors['blue'] );
		//hm_log( $colors );
		$this->assertEquals( $colors, $color );
	}

	function assertImageAlphaAtPoint( $image_path, $point, $alpha ) {

		$im = imagecreatefrompng( $image_path );
		$rgb = imagecolorat($im, $point[0], $point[1]);

		$colors = imagecolorsforindex($im, $rgb);

		$this->assertEquals( $colors['alpha'], $alpha );
	}

}