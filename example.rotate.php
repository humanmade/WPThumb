<?php
/*
Plugin Name: WPThumb-rotator
Plugin URI: https://github.com/humanmade/WPThumb
Description: Extends WPThumb to rotate the image. 
Author: humanmade limited, Matthew Haines-Young
Version: 0.1
Author URI: http://www.humanmade.co.uk/
*/





add_filter( 'wpthumb_filename_custom', 'rotator_filename', 10, 2 );
add_filter( 'wpthumb_image_filter', 'rotator_action', 10, 2 );

function rotator_action( $thumb, $args ) {

	if( $args['custom'] == 'rotator' ) {
		$thumb->rotateImage('CCW');	
		$thumb->rotateImage('CCW');
	}
	return $thumb;
	
}

function rotator_filename( $custom, $args ){	
	$custom = $args['custom'];
	return $custom;
}