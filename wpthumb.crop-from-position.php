<?php

/**
 * Only add the watermarking admin options if the current theme supports it, as we don't want to clutter
 * for people who don't care
 *
 */
function wpthumb_add_crop_from_position_admin_hooks() {

	if ( current_theme_supports( 'wpthumb-crop-from-position' ) ) {
		add_filter( 'attachment_fields_to_edit', 'wpthumb_media_form_crop_position', 10, 2 );
		add_filter( 'attachment_fields_to_save', 'wpthumb_media_form_crop_position_save', 10, 2 );
	}
}
add_action( 'init', 'wpthumb_add_crop_from_position_admin_hooks' );

/**
 * wpthumb_media_form_crop_position function.
 *
 * Adds a back end for selecting the crop position of images.
 *
 * @access public
 *
 * @param array $fields
 * @param array $post
 * @return $post
 */
function wpthumb_media_form_crop_position( $fields, $post ) {

	if ( ! wp_attachment_is_image( $post->ID ) ) {
		return $fields;
	}

	$current_position = get_post_meta( $post->ID, 'wpthumb_crop_pos', true );

	if ( ! $current_position ) {
		$current_position = 'center,center';
	}

	$html = '<style>#wpthumb_crop_pos { padding: 5px; } #wpthumb_crop_pos input { margin: 5px; width: auto; }</style>';
	$html .= '<div id="wpthumb_crop_pos">';
	$html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="left,top" title="Left, Top" ' . checked( 'left,top', $current_position, false ) . '/>';
	$html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="center,top" title="Center, Top" ' . checked( 'center,top', $current_position, false ) . '/>';
	$html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="right,top" title="Right, Top" ' . checked( 'right,top', $current_position, false ) . '/><br/>';
	$html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="left,center" title="Left, Center" ' . checked( 'left,center', $current_position, false ) . '/>';
	$html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="center,center" title="Center, Center"' . checked( 'center,center', $current_position, false ) . '/>';
	$html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="right,center" title="Right, Center" ' . checked( 'right,center', $current_position, false ) . '/><br/>';
	$html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="left,bottom" title="Left, Bottom" ' . checked( 'left,bottom', $current_position, false ) . '/>';
	$html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="center,bottom" title="Center, Bottom" ' . checked( 'center,bottom', $current_position, false ) . '/>';
	$html .= '<input type="radio" name="attachments[' . $post->ID . '][wpthumb_crop_pos]" value="right,bottom" title="Right, Bottom" ' . checked( 'right,bottom', $current_position, false ) . '/>';
	$html .= '</div>';

	$fields['crop-from-position'] = array(
		'label' => __( 'Crop Position', 'wpthumb' ),
		'input' => 'html',
		'html'  => $html
	);

	return $fields;

}

/**
 * wpthumb_media_form_crop_position_save function.
 *
 * Saves crop position in post meta.
 *
 * @access public
 *
 * @param array $post
 * @param array $attachment
 * @return $post
 */
function wpthumb_media_form_crop_position_save( $post, $attachment ) {

	if ( ! isset( $attachment['wpthumb_crop_pos'] ) ) {
		return;
	}

	if ( $attachment['wpthumb_crop_pos'] == 'center,center' ) {
		delete_post_meta( $post['ID'], 'wpthumb_crop_pos' );
	} else {
		update_post_meta( $post['ID'], 'wpthumb_crop_pos', $attachment['wpthumb_crop_pos'] );
	}

	return $post;

}
