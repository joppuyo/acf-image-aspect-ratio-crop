=== Advanced Custom Fields: Image Aspect Ratio Crop Field ===
Contributors: joppuyo
Tags: acf, field, image, crop
Requires at least: 4.9.0
Tested up to: 4.9.8
Requires PHP: 5.3
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

ACF field that allows user to crop image to a specific aspect ratio

== Description ==

A field for Advanced Custom Field that forces the user to crop their image to specific aspect ratio after uploading. This is especially useful in responsive image use cases.

After cropping, a new cropped image variant is created in the gallery and saved into the post. Thumbnails are also generated for the new image. User can re-crop the original image at any time from the post page.

The cropped image variants are hidden by default in the media browser and on the media page but you can view them by selecting the "list view" on the media page.

= Compatibility =

This ACF field type is compatible with:

* ACF 5

= Thanks =

Special thanks to Anders Thorborg for [ACF Image Crop](https://github.com/andersthorborg/ACF-Image-Crop) which served as a inspiration for this plugin. Also, thanks to Fengyuan Chen for the [cropper.js](https://fengyuanchen.github.io/cropperjs/) library!

== Installation ==

1. Copy the `acf-image-aspect-ratio-crop` folder into your `wp-content/plugins` folder
2. Activate the Image Aspect Ratio Crop plugin via the plugins admin page
3. Create a new field via ACF and select the Image Aspect Ratio Crop type
4. Read the description above for usage instructions

== Screenshots ==

1. Cropping an image to 16:9 aspect ratio
2. Option to re-crop the image after upload

== Changelog ==

= 1.0.8 =
* Fix bug with detecting the same aspect ratio

= 1.0.7 =
* Update compatibility information

= 1.0.6 =
* Fix bug with detecting the same aspect ratio

= 1.0.5 =
* User interface improvements
* Fixed issue where cropper sometimes showed a wrong image
* Improved performance in cases uploaded image had the correct aspect ratio. Thanks to @hrohh on w.org for the tip!

= 1.0.4 =
* Fix incompatibility with ACF 5.7

= 1.0.3 =
* Release on w.org

= 1.0.2 =
* Update readme

= 1.0.1 =
* Fix issue where the cropper broke if multiple images were selected inside a repeater
* Cropper is now disabled while cropping is in progress
* Fix issue where is was not possible to re-crop image before saving the post

= 1.0.0 =
* Initial Release.
