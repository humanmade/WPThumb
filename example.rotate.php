<?php
/*
Plugin Name: WPThumb-rotator
Plugin URI: https://github.com/humanmade/WPThumb
Description: Extends WPThumb to rotate the image. 
Author: humanmade limited, Matthew Haines-Young
Version: 0.1
Author URI: http://www.humanmade.co.uk/
*/

// This makes use of filters in WPThumb that allow you to manipulate the image object using any of the phpThumb functions.
// In this case - I have set a custom argument when calling the attachment eg wp_get_attachment_image( $id ,array( 'thumbnail', 'custom' => 'rotator' ));

add_filter( 'wpthumb_filename_custom', 'rotator_filename', 10, 2 );
add_filter( 'wpthumb_image_filter', 'rotator_action', 10, 2 );

function rotator_action( $thumb, $args ) {

	if( $args['custom'] == 'rotator' ) {
		$thumb->rotateImage('CCW');	
		$thumb->rotateImage('CCW');
	}
	return $thumb;
	
}

//Filename must also be different from standard. 
function rotator_filename( $custom, $args ){	
	$custom = $args['custom'];
	return $custom;
}