<?php

include_once( '../../../../../wp-load.php' );
include_once( '../../wpthumb.php' );

$args = array(
	'width' => 200,
	'height' => 200,
	'default' => dirname( dirname( __FILE__ ) ). '/images/google.png'
);

?>
Should be Google:
<img src="<?php echo wpthumb( ABSPATH . 'foo.jpg', $args ) ?>"><br />

Should be Yahoo
<img src="<?php echo wpthumb( 'http://blogs.edgehill.ac.uk/webservices/files/2010/12/yahoo-logo.jpg', $args ) ?>">