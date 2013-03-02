<?php

class WPThumbPostThumbnailTestCase extends WP_UnitTestCase {

	function testPostThumbnailResize() {
		
		// @todo rewrite to not rely on existing post
		return;


		require_once( ABSPATH . WPINC . '/post-thumbnail-template.php' );

		$post = reset( get_posts( 'showposts=1' ) );
		$this->assertNotNull( $post->ID, 'test post not found' );
		$_old_thumbnail = get_post_thumbnail_id( $post->ID );

		$attachment = reset( get_posts( 'post_type=attachment' ) );

		if ( is_null( $attachment->ID ) )
			$this->markTestSkipped( 'Attachment not found' );

		$this->assertFileExists( get_attached_file( $attachment->ID ) );

		set_post_thumbnail( $post->ID, $attachment->ID );

		$image_html = get_the_post_thumbnail( $post->ID );

		$this->assertContains( '/cache/', $image_html );

		$image_html = get_the_post_thumbnail( $post->ID, 'width=100&height=100&cache=0&crop=1' );

		$this->assertContains( 'width="100"', $image_html );
		$this->assertContains( 'height="100"', $image_html );

		set_post_thumbnail( $post->ID, $_old_thumbnail );

	}
}