=== WPThumb ===
Contributors: humanmade, joehoyle, mattheu, tcrsavage, willmot
Tags: image resize
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 0.6

An on-demand image generation replacement for WordPress' image resizing.

== Description ==

WP Thumb is a simple plugin that makes use of the PHPThumb library. It seamlessly intergrates with the WordPress image functions. You can specify height, width and crop values, and an image will be generated, which is then cached for future use. 

= Features =

* Automatic images resizing, cropping and caching. 
* Ideal for when you want to change the dimensions of default image sizes.
* Can extend using filters to make use of any of the phpThumb Library functions by manipulating the image object.

== Changelog ==

=0.5=
* Added more tests
* Fix minor bugs

=0.4=
* Rewrote core functionality to use a Class
* Added 22 Unit Tests (can be run using WP Unit)

=0.3=
* Ability to extend functionality using filters to manipulate image object.

=0.2=
* Add some error messages and try to prevent some conflicts with our other plugins. 
* Swap Joe Hoyles helper for HM Core.

= 0.1 =
* Add Joe Hoyles helper. 

== Installation ==

1. Upload the 'WPThumb' folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

= The plugin doesn't work, what do I do? =

Visit the Issues page of the plugin homepage at: https://github.com/humanmade/WPThumb
