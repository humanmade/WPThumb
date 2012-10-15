<?php


class WPThumb_Picture {

	// The default image - used as a default/fallback.
	private $default;

	// Array of all the different images used to build the picture element.
	private $images = array();
	
	public $multiplier = 2;	

	/**
	 * Helper function for adding image sources for picture element.
	 * 
	 * @param int $attachment_id 	The ID of the wordpress attachment.
	 * @param string $size          string or array. The image size of the required source.
	 * @param string $media_query   The media query used by this souce.
	 */
	public function add_picture_source( $attachment_id, $size = 'post-thumbnail', $media_query = '' ) {

		$this->images[] = array( 
			'attachment_id' => $attachment_id, 
			'size'          => $size, 
			'media_query'	=> $media_query 
		);

	}

	/**
	 * Get the alt attribute value.
	 * @return [type] [description]
	 */
	public function get_alt() {

		$default = $this->get_default_image();
		return trim( strip_tags( get_post_meta( $default['attachment_id'], '_wp_attachment_image_alt', true ) ) );
	
	}


	/**
	 * Get the default picture source image. Used for default/fallback when picture is not supported.
	 * Defaults to the first.
	 * 
	 * @return string the def
	 */
	public function get_default_image() {

		if ( ! $this->default )
			$this->default = reset( $this->images );

		return $this->default;

	}


	public function get_picture() {

		if ( empty( $this->images ) )
			return;

		$default = $this->get_default_image();
		
		$picture = "\n" . '<div data-picture data-alt="' . $this->get_alt() . '" class="attachment-' . $default['size'] . '">' . "\n";
		
		foreach ( $this->images as $image ) {

			$picture .= $this->get_picture_source( $image );

		}

		
		$picture .= "\t" . '<noscript>' . wp_get_attachment_image( $default['attachment_id'], $default['size'] ) . '</noscript>' . "\n";

		$picture .= '</div>';

		return $picture;

	}

	/**
	 * Get the source element for each image.
	 *
	 * @param  [type] $image [description]
	 * @return [type]        [description]
	 */
	private function get_picture_source( $image ) {

		$image_defaults = array(
			//'attachment_id' => (int),
			'size' => 'thumbnail',
			'media_query' => null
		); 

		$image = wp_parse_args( $image, $image_defaults );
		
		// The source element for the requested image
		$requested = wp_get_attachment_image_src( $image['attachment_id'], $image['size'] );
		$r = "\t<div data-src=\"" . $requested[0] . "\" data-media=\"" . $this->get_picture_source_media_attr( $image['media_query'], false ) . "\"></div>\n";

		// The source element for the high res version of the requested image.
		// Calculate the size args for the high resoloution image & If possible to create high res version.
		$original = wp_get_attachment_image_src( $image['attachment_id'], 'full' );
		$size_high_res = array(
			0      => (int) $requested[1] * $this->multiplier,
			1      => (int) $requested[2] * $this->multiplier,
			'crop' => $requested[3]
		);

		if ( $original[1] >= $size_high_res[0] && $original[2] >= $size_high_res[1] ) {
			
			$requested_high_res = wp_get_attachment_image_src( $image['attachment_id'], $size_high_res );
			$r .= "\t<div data-src=\"" . $requested_high_res[0] . "\" data-media=\"" . $this->get_picture_source_media_attr( $image['media_query'], true ) . "\"></div>\n";

		}

		return $r;
		
	}

	/**
	 * Process the media attribute value. Adds retina args if required.
	 * 
	 * @param  string  $media_query media query. Currently only supports a single query. ('and' is ok, but ',' is not)
	 * @param  boolean $retina      If this is for a high res image source, pass true to append the high res media query args.
	 * @return string               full string to add as data-media attribute.
	 */
	private function get_picture_source_media_attr( $media_query, $high_res = false ) {

		if ( $high_res )
			if ( empty( $media_query ) )
				return "(min-device-pixel-ratio: $this->multiplier)";
			else 
				return "$media_query and (min-device-pixel-ratio: $this->multiplier), $media_query and (-webkit-min-device-pixel-ratio: $this->multiplier)"; 
		
		else 
			return $media_query;

	}

}


/**
 *	Enqueue the picturefill scripts
 */
add_action( 'wp_enqueue_scripts', function() {

	wp_enqueue_script( 'wpthumb_matchmedia', WP_THUMB_URL . 'picturefill/picturefill.js', false, false, true );
	wp_enqueue_script( 'wpthumb_picturefill', WP_THUMB_URL . 'picturefill/external/matchmedia.js', array('wpthumb_matchmedia' ), false, true );

} );

/**
 * Returns a picture element for the passed args.
 * 
 * @param  array $images An array of image args. Each image should be passed as an array of args: array( 'attachment_id' => int, 'size' => string or array, 'media_query' => string )
 * @return [type]         [description]
 */
function wpthumb_get_picture( $images ) {

	$picture = new WPThumb_Picture();

	foreach ( $images as $image )
		$picture->add_picture_source( $image['attachment_id'], $image['size'], $image['media_query'] );

	return $picture->get_picture();

}

/**
 * Filter the post thumbnail output.
 * 
 * @param  string       $html
 * @param  int          $post_id
 * @param  int          $post_thumbnail_id
 * @param  string/array $size
 * @param  array        $attr
 * @return string html for the picture element.
 */
function _wpthumb_picture_post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {

	$html = wpthumb_get_the_post_thumbnail_picture( $post_id, 'thumbnail', $attr );

	return $html;

}
add_filter( 'post_thumbnail_html', '_wpthumb_picture_post_thumbnail_html', 10, 5 );

/**
 * Returns the post thumbnail in the picture element markup with high resoloution version.
 *
 * @param int $post_id Optional. Post ID.
 * @param string $size Optional. Image size. Defaults to 'post-thumbnail'.
 * @param string|array $attr Optional. Query string or array of attributes.
 */
function wpthumb_get_the_post_thumbnail_picture( $post_id = null, $size = 'post-thumbnail', $attr = '' ) {

	$post_id = ( null === $post_id ) ? get_the_ID() : $post_id;
	$post_thumbnail_id = get_post_thumbnail_id( $post_id );
	$size = apply_filters( 'post_thumbnail_size', $size );
	
	return wpthumb_get_attachment_picture( $post_thumbnail_id, $size, $attr );	

}

/**
 * Output the post thumbnail in the picture element markup with high resoloution version.
 *
 * @param int $post_id Optional. Post ID.
 * @param string $size Optional. Image size. Defaults to 'post-thumbnail'.
 * @param string|array $attr Optional. Query string or array of attributes.
 */
function wpthumb_the_post_thumbnail_picture( $size = 'post-thumbnail', $attr = '' ) {

	echo wpthumb_get_the_post_thumbnail_picture( get_the_ID(), $size, $attr );

}

/**
 * Returns the <picture> element for the attachment. 
 *
 * Return the markup required for the proposed picture html element as implemented by the picturefill polyfill.
 * https://github.com/scottjehl/picturefill
 *
 * @param int $post_id Optional. Post ID.
 * @param string $size Optional. Image size. Defaults to 'post-thumbnail'.
 * @param string|array $attr Optional. Query string or array of attributes.
 */
function wpthumb_get_attachment_picture( $attachment_id, $size, $attr = '' ) {

	if ( empty( $attachment_id ) )
		return;

	$picture = new WPThumb_Picture();
	$picture->add_picture_source( $attachment_id, $size );
	return $picture->get_picture();

}