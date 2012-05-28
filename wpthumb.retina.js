// Add Event Listener utility function
var wpthumbAddEvent=function(){return document.addEventListener?function(a,c,d){if(a&&a.nodeName||a===window)a.addEventListener(c,d,!1);else if(a&&a.length)for(var b=0;b<a.length;b++)wpthumbAddEvent(a[b],c,d)}:function(a,c,d){if(a&&a.nodeName||a===window)a.attachEvent("on"+c,function(){return d.call(a,window.event)});else if(a&&a.length)for(var b=0;b<a.length;b++)wpthumbAddEvent(a[b],c,d)}}();

/**
 * Wrap window.devicePixelRatio
 */
function wpthumbGetDevicePixelRatio() {

	if ( typeof( window.devicePixelRatio ) === undefined )
		return 1;

	return window.devicePixelRatio;

}

var wpthumbResponsiveEnhance = function( img ) {
		
	// If passed an array of images loop through calling this function again for each.
	if ( img.length ) {
	
		for ( var i = 0, len = img.length; i < len; i++ ) {
            wpthumbResponsiveEnhance( img[i] );
        }

	} else {
	
		// Only run once per image
		if ( img.getAttribute( 'data-retina-src') !== undefined && ( ( ' ' + img.className + ' ' ).replace( /[\n\t]/g, ' ' ).indexOf( ' image-retina ' ) === -1 ) ) {
	
			// Create a new image from
			// Once loaded, replace the original image src.
			// Add class image-retina.
			var fullimg = new Image();

			fullimg.src = img.getAttribute( 'data-retina-src' );    	

  			wpthumbAddEvent( fullimg, 'load', function( e ) {
  				img.src = this.src;
				img.className += ' image-retina';
			} );
 		
  		}

	}
		
};

// Call the function if we are on a high resoloution screen
if ( 1.5 < wpthumbGetDevicePixelRatio() )
	wpthumbResponsiveEnhance( document.getElementsByTagName( 'img' ) );