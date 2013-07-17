<?php 

if ( file_exists( '../../../../wp-load.php' ) )
	include_once( '../../../../wp-load.php' );
else
	include_once( '../../../wordpress/wp-load.php' );

if ( $_GET['action'] === 'wpthumb_wm_watermark_preview_image' )
	echo wpthumb_wm_watermark_preview_image( $_GET['position'], $_GET['padding'], $_GET['image_id'], $_GET['mask'] );

exit;