<?php
/**
 * Bootstrap the testing environment
 * Uses wordpress tests (http://github.com/nb/wordpress-tests/) which uses PHPUnit
 * @package wordpress-plugin-tests
 *
 * Usage: change the below array to any plugin(s) you want activated during the tests
 *        value should be the path to the plugin relative to /wp-content/
 *
 * Note: Do note change the name of this file. PHPUnit will automatically fire this file when run.
 *
 */

$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'wpthumb/wpthumb.php' ),
);

require dirname( __FILE__ ) . '/lib/bootstrap.php';

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