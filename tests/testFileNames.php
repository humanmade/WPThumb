<?php

class WPThumbFileNameTestCase extends WP_UnitTestCase {

	function testFileURLWithQueryParam() {
		
		$path = 'http://google.com/logo.png?foo=123';
		
		$image = new WP_Thumb;
		$image->setFilePath( $path );
		
		$this->assertNull( $image->error );
		$this->assertContains( 'google', $image->getCacheFilePath() );
		
		$this->assertEquals( 'png', $image->getFileExtension() );
		
	}
	
	function testFileWithURL() {
		
		$path = 'http://google.com/logo.png';
		
		$image = new WP_Thumb;
		$image->setFilePath( $path );
		
		$this->assertNull( $image->error );
		$this->assertContains( ABSPATH, $image->getCacheFileDirectory() );
	}
	
	function testFileWithDoubleSlashUrl() {
		
		$path = '//google.com/logo.png';
		
		$image = new WP_Thumb;
		$image->setFilePath( $path );
		
		$this->assertNull( $image->error );
		$this->assertContains( ABSPATH, $image->getCacheFileDirectory() );
		$this->assertContains( 'remote', $image->getCacheFileDirectory() );
	}
	
	function testFileURLWithNoExtension() {
	
		$path = 'http://google.com/logo';
		
		$image = new WP_Thumb;
		$image->setFilePath( $path );
		
		$this->assertNull( $image->error );
		$this->assertContains( ABSPATH, $image->getCacheFileDirectory() );
		$this->assertContains( 'remote', $image->getCacheFileDirectory() );
		$this->assertEquals( 'jpg', $image->getFileExtension() );
	
	}
	
	function testFileURLWithSpecialChars() {
	
		$path = 'http://google.com/logo~foo.png';
		
		$image = new WP_Thumb;
		$image->setFilePath( $path );
		
		$this->assertNull( $image->error );
		$this->assertContains( ABSPATH, $image->getCacheFileDirectory() );
		$this->assertNotContains( '~', $image->getCacheFileDirectory() );
	
	}
	
	function testFileURLWithDotInPath() {
		
		$path = 'http://google.com/logo~foo.png';
		
		$image = new WP_Thumb;
		$image->setFilePath( $path );
		
		$this->assertNull( $image->error );
		$this->assertContains( ABSPATH, $image->getCacheFileDirectory() );
		$this->assertNotContains( '.', $image->getCacheFileDirectory() );
		
	}
	
	function testFileWithPath() {
	
		$path = dirname( __FILE__ ) . '/images/google.png';
		
		$image = new WP_Thumb;
		$image->setFilePath( $path );
		
		$this->assertNull( $image->error );
		$this->assertContains( ABSPATH, $image->getCacheFileDirectory() );
		$this->assertEquals( 'png', $image->getFileExtension() );

	}
	
	function testFileWithLocalURL() {
	
		$path = dirname( __FILE__ ) . '/images/google.png';
		$url = str_replace( ABSPATH, get_bloginfo( 'url' ) . '/', $path );
		
		$image = new WP_Thumb;
		$image->setFilePath( $url );
		
		$this->assertNull( $image->error );
		$this->assertEquals( $path, $image->getFilePath() );

	}
}