/*! Picturefill - Responsive Images that work today. (and mimic the proposed Picture element with divs). Author: Scott Jehl, Filament Group, 2012 | License: MIT/GPLv2 */

(function( w ){
	
	// Enable strict mode
	"use strict";

	w.picturefill = function() {
		var ps = [],
			pdiv = w.document.getElementsByTagName( "div" ),
			pimg = w.document.getElementsByTagName( "img" );

		for( var i = 0, il = pdiv.length; i < il; i++ ){
			if( pdiv[ i ].getAttribute( "data-picture" ) !== null ){
				ps.push( pdiv[ i ] );
			}
		}
		for( var i = 0, il = pimg.length; i < il; i++ ){
			if( pimg[ i ].getAttribute( "data-srcset" ) !== null ) {
				ps.push( pimg[ i ] );
			}
		}

		// Loop the pictures
		for( var i = 0, il = ps.length; i < il; i++ ){
			var sources = ps[ i ].getElementsByTagName( "div" ),
				matches = [];

			matches.push( ps[ i ] );

			// See which sources match
			for( var j = 0, jl = sources.length; j < jl; j++ ){
				var media = sources[ j ].getAttribute( "data-media" );
				// if there's no media specified, OR w.matchMedia is supported and matches.
				if( !media || ( w.matchMedia && w.matchMedia( media ).matches ) ){
					matches.push( sources[ j ] );
				}
			}

			// If we’re dealing with an `img` using `srcset`, that’s our target. If not, find any images inside the pseudo-`picture` element.
			var picImg = ps[ i ].nodeName == "IMG" ? ps[ i ] : ps[ i ].getElementsByTagName( "img" )[ 0 ];

			if( matches.length ){
				var match = matches.pop(),
					srcset = match.getAttribute( "data-srcset" );

				if( !picImg ){
					picImg = w.document.createElement( "img" );
					picImg.alt = ps[ i ].getAttribute( "data-alt" );
					ps[ i ].appendChild( picImg );
				}
				if( srcset ) {
					var screenRes = w.devicePixelRatio || 1,
						sources = srcset.split( "," ); // Split comma-separated `srcset` sources into an array.

					for( var res = sources.length, r = res - 1; r >= 0; r-- ) { // Loop through each source/resolution in `srcset`.
						var source = sources[ r ].replace( /^\s*/, '' ).replace( /\s*$/, '' ).split( " " ), // Remove any leading whitespace, split on spaces.
							resMatch = parseFloat( source[ 1 ], 10 ); // Parse out the resolution for each source in `srcset`.

						if( screenRes >= resMatch ) {
							if( picImg.getAttribute( "src" ) !== source[ 0 ] ) {
								var newImg = document.createElement( "img" );

								newImg.src = source[ 0 ];

								// When the image is loaded, set a width equal to that of the original’s intrinsic width divided by the screen resolution:
								newImg.onload = function( e ) {
										this.style.maxWidth = this.cloneNode( true ).width / resMatch + "px";
										//alert( window.getComputedStyle( this.cloneNode( true ) ).width );
										// Clone the original image into memory so the width is unaffected by page styles:
								};
								picImg.parentNode.replaceChild( newImg, picImg );
							}
							break; // We’ve matched, so bail out of the loop here.
						}
					}
				} else {
					// No `srcset` in play, so just use the `src` value:
					picImg.src = match.getAttribute( "data-src" );
				}
			}
			else if( picImg ){
				ps[ i ].removeChild( picImg );
			}
		}
	};

	// Run on resize and domready (w.load as a fallback)
	if( w.addEventListener ){
		var throttle;
		w.addEventListener( "resize", function() {
			// Throttling the resize event prevents iOS from freaking out when it occasionally triggers a couple of resizes, as 2x images are loaded in.
			if( throttle ) { w.clearTimeout( throttle ); }
			throttle = w.setTimeout(function () {
				w.picturefill();
			}, 150 );
		}, false );
		w.addEventListener( "DOMContentLoaded", function(){
			w.picturefill();
			// Run once only
			w.removeEventListener( "load", w.picturefill, false );
		}, false );
		w.addEventListener( "load", w.picturefill, false );
	}
	else if( w.attachEvent ){
		w.attachEvent( "onload", w.picturefill );
	}
	
}( this ));