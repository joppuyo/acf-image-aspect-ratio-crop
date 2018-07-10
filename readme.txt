=== Advanced Custom Fields: Image Aspect Ratio Crop Field ===
Contributors: joppuyo
Tags: acf, field, image, crop
Requires at least: 4.9.0
Tested up to: 4.9.0
Requires PHP: 5.3
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

ACF field that allows user to crop image to a specific aspect ratio

== Description ==

A field for Advanced Custom Field that forces the user to crop their image to specific aspect ratio after uploading.

= Compatibility =

This ACF field type is compatible with:
* ACF 5

== Installation ==

1. Copy the `acf-image-aspect-ratio-crop` folder into your `wp-content/plugins` folder
2. Activate the Image Aspect Ratio Crop plugin via the plugins admin page
3. Create a new field via ACF and select the Image Aspect Ratio Crop type
4. Read the description above for usage instructions

== Changelog ==

= 1.0.2 =
* Update readme

= 1.0.1 =
* Fix issue where the cropper broke if multiple images were selected inside a repeater
* Cropper is now disabled while cropping is in progress
* Fix issue where is was not possible to re-crop image before saving the post

= 1.0.0 =
* Initial Release.
