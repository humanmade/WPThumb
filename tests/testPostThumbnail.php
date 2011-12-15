<?php

class WPThumbPostThumbnailTestCase extends WP_UnitTestCase {

	function testPostThumbnailResize() {
	
		require_once( ABSPATH . WPINC . '/post-thumbnail-template.php' );
		
		$post = reset( get_posts( 'showposts=1' ) );
		$this->assertNotNull( $post->ID, 'test post not found' );
		$_old_thumbnail = get_post_thumbnail_id( $post->ID );
		
		$attachment = reset( get_posts( 'post_type=attachment' ) );
		
		$this->assertFileExists( get_attached_file( $attachment->ID ) );
		
		$this->assertNotNull( $attachment->ID, 'test attachment not found' );
		
		set_post_thumbnail( $post->ID, $attachment->ID );
		
		$this->assertEquals( $attachment->ID, get_post_thumbnail_id( $post->ID ) );
		
		$image_html = get_the_post_thumbnail( $post->ID );
		$this->assertContains( '/cache/', $image_html );
		
		$image_html = get_the_post_thumbnail( $post->ID, 'width=100&height=100&cache=0&crop=1' );
		
		$this->assertContains( 'width="100"', $image_html );
		$this->assertContains( 'height="100"', $image_html );
		
		set_post_thumbnail( $post->ID, $_old_thumbnail );
		
	}
}