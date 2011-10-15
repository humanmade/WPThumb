<?php

class WPThumbPostThumbnailTestCase extends WP_UnitTestCase {

	function testPostThumbnailResize() {
		
		$post_id = 1;
		$post = get_post( $post_id );
		$this->assertNotNull( $post->ID, 'test post not found' );
		$_old_thumbnail = get_post_thumbnail_id( $post->ID );
		
		$attachment = reset( get_posts( 'post_type=attachment' ) );
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