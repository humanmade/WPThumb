<?php

class WP_Thumb_Background_Fill {

	private $args = array();
	private $editor;

	public function __construct( $editor, $args ) {

		$this->editor = $editor;

		$defaults = array(
			'background_fill' => false,
		);

		$this->args = wp_parse_args( $args, $defaults );

		if ( $this->args['background_fill'] && $this->args['background_fill'] !== 'auto' ) {

			$this->fill_with_color( $this->args['background_fill'] );
		}
	}

	/**
	 * Background fill an image using the provided color
	 */
	public function fill_with_color( $color ) {

		if ( ! is_array( $color ) && strlen( $color ) == 3 )
			$color = (float) str_pad( (string) $color, 9, $color ) . '000';

		if ( ! is_array( $color ) )
			$color = array( 'top' => $color, 'bottom' => $color, 'left' => $color, 'right' => $color );

		$this->fill_color( $color );

	}

	/**
	 * Background fill an image using the provided color
	 *
	 * @param int $width The desired width of the new image
	 * @param int $height The desired height of the new image
	 * @param Array the desired pad colors in RGB format, array should be array( 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' );
	 */
	private function fill_color( array $colors ) {

		$current_size = $this->editor->get_size();

		$size = array( 'width' => $this->args['width'], 'height' => $this->args['height'] );

		$offsetLeft = ( $size['width'] - $current_size['width'] ) / 2;
		$offsetTop = ( $size['height'] - $current_size['height'] ) / 2;

		$new_image = imagecreatetruecolor( $size['width'], $size['height'] );

		// This is needed to support alpha
		imagesavealpha( $new_image, true );
		imagealphablending( $new_image, false );

		// Check if we are padding vertically or horizontally
		if ( $current_size['width'] != $size['width'] ) {

			$colorToPaint = imagecolorallocatealpha( $new_image, substr( $colors['left'], 0, 3 ), substr( $colors['left'], 3, 3 ), substr( $colors['left'], 6, 3 ), substr( $colors['left'], 9, 3 ) );

			// Fill left color
	        imagefilledrectangle( $new_image, 0, 0, $offsetLeft + 5, $size['height'], $colorToPaint );

			$colorToPaint = imagecolorallocatealpha( $new_image, substr( $colors['right'], 0, 3 ), substr( $colors['right'], 3, 3 ), substr( $colors['right'], 6, 3 ), substr( $colors['left'], 9, 3 ) );

			// Fill right color
	        imagefilledrectangle( $new_image, $offsetLeft + $current_size['width'] - 5, 0, $size['width'], $size['height'], $colorToPaint );

		} elseif ( $current_size['height'] != $size['height'] ) {

			$colorToPaint = imagecolorallocatealpha( $new_image, substr( $colors['top'], 0, 3 ), substr( $colors['top'], 3, 3 ), substr( $colors['top'], 6, 3 ), substr( $colors['left'], 9, 3 ) );

			// Fill top color
	        imagefilledrectangle( $new_image, 0, 0, $size['width'], $offsetTop + 5, $colorToPaint );

			$colorToPaint = imagecolorallocatealpha( $new_image, substr( $colors['bottom'], 0, 3 ), substr( $colors['bottom'], 3, 3 ), substr( $colors['bottom'], 6, 3 ), substr( $colors['left'], 9, 3 ) );

			// Fill bottom color
	        imagefilledrectangle( $new_image, 0, $offsetTop - 5 + $current_size['height'], $size['width'], $size['height'], $colorToPaint );

		}

		imagecopy( $new_image, $this->editor->get_image(), $offsetLeft, $offsetTop, 0, 0, $current_size['width'], $current_size['height'] );

		$this->editor->update_image( $new_image );
		$this->editor->update_size();
	}

}

function wpthumb_background_fill( $editor, $args ) {

	// currently only supports GD
	if ( ! is_a( $editor, 'WP_Thumb_Image_Editor_GD') )
		return $editor;

	$bg = new WP_Thumb_Background_Fill( $editor, $args );

	return $editor;
}
add_filter( 'wpthumb_image_post', 'wpthumb_background_fill', 10, 2 );