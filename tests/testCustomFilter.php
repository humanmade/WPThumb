<?php

class WPThumbCustomFilterTestCase extends WP_UnitTestCase {

	function testmakeImageGreyScale() {

		add_filter( 'wspthumb_image_post', function( WP_Image_Editor $editor, $args ) {

			if ( ! is_a( $editor, 'WP_Image_Editor_GD' ) || empty( $args['greyscale'] ) )
				return $editor;

			imagefilter( $editor->get_image(), IMG_FILTER_GRAYSCALE );

			return $editor;

		}, 10, 2 );

		$path = dirname( __FILE__ ) . '/images/google.png';

		error_log( wpthumb( 'http://hm-base.local/content/uploads/2013/01/ed6d35fe-5a9b-11e2-847b-fbb7185f3122.png', 'width=150&height=150&greyscale=1' ) );



	}
}

add_filter( 'wpthumb_image_post', 'pdw_bij_add_greyscale_filter', 10, 2 );

    function pdw_bij_add_greyscale_filter( WP_Image_Editor $editor, $args ) {

    if ( ! is_a( $editor, 'WP_Image_Editor_GD' ) || empty( $args['greyscale'] ) )
        return $editor;

    imagefilter( $editor->get_image(), IMG_FILTER_GRAYSCALE );

    return $editor;

    }