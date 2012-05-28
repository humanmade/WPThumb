// Add Event Listener wrapper function
var addEvent=function(){return document.addEventListener?function(a,c,d){if(a&&a.nodeName||a===window)a.addEventListener(c,d,!1);else if(a&&a.length)for(var b=0;b<a.length;b++)addEvent(a[b],c,d)}:function(a,c,d){if(a&&a.nodeName||a===window)a.attachEvent("on"+c,function(){return d.call(a,window.event)});else if(a&&a.length)for(var b=0;b<a.length;b++)addEvent(a[b],c,d)}}();


function getDevicePixelRatio() {

	if ( typeof( window.devicePixelRatio ) === undefined )
		return 1;

	return window.devicePixelRatio;

}


var responsiveEnhance = function( img, monitor ) {
		
	// Can pass an array of images. 
	// If so, loop through calling this function again for each.
	if (img.length) {
	
		for (var i=0, len=img.length; i<len; i++) {
            responsiveEnhance(img[i], img.clientWidth, monitor);
        }

	} else {
	
		// Don't do this if we have done it already ( has class large )
		if ( img.getAttribute( 'data-retina-src') !== undefined || ( ( ' ' + img.className + ' ' ).replace( /[\n\t]/g, ' ' ).indexOf(' image-retina ') !== -1 ) ) {
	
			// Create a new image from
			// Once loaded, replace the original image src.
			// Add class image-retina.
			var fullimg = new Image();
			fullimg.src = img.getAttribute( 'data-retina-src' );    	
  			addEvent( fullimg, 'load', function(e) {
  			
  				console.log( this.src );
  				img.src = this.src;
				img.className += ' image-retina';
			});
  		
  		}
  		
	}
		
};

console.log( getDevicePixelRatio() );

// Call the function if we are on a high resoloution screen.
if( 1.5 <= getDevicePixelRatio() ) {
	responsiveEnhance( document.getElementsByTagName('img') );
}
