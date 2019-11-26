=== Advanced Custom Fields: Image Aspect Ratio Crop Field ===
Contributors: joppuyo
Tags: acf, field, image, crop
Requires at least: 4.9
Tested up to: 5.3
Requires PHP: 5.5
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

== Frequently Asked Questions ==

= Can I access metadata in the original image from a cropped image? =

Yes, the original image data is saved under `original_image` key in the returned ACF array. You can access data such as alt text, description and title this way.

= I have an issue or I want to contribute code =

Please use the [GitHub repository](https://github.com/joppuyo/acf-image-aspect-ratio-crop) to raise [issues](https://github.com/joppuyo/acf-image-aspect-ratio-crop/issues) about the plugin. You are also free to send a pull request on GitHub.

= How is this different from the other plugin? =

[Advanced Custom Fields: Image Crop Add-on](https://wordpress.org/plugins/acf-image-crop-add-on/) is based on exact image dimensions (like 640x480). This plugin uses an aspect ratio such as 4:3 instead. Using an aspect ratio is is more convenient when working with responsive images since you care about the aspect ratio more than pixel dimensions.

Of course, nothing will stop you from using an aspect ratio like 1200:630 which is similar to a pixel amount with this plugin, if you want.

Also, as of 2019, the other plugin is not actively maintained anymore and does not work well with latest ACF versions. I try to maintain this plugin as best as I can when new versions of ACF and WordPress come out.

== Screenshots ==

1. Cropping an image to 16:9 aspect ratio
2. Cropping in progress
3. Option to re-crop the image after upload

== Changelog ==

= 3.1.11 =
* Remove ramsey/uuid dependency in favor of using native wp function since the dependency caused issues in some server
  configurations

= 3.1.10 =
* Fix issue where image was not visible in backed due to malformed URL

= 3.1.8 =
* Deployment fix

= 3.1.0 =
* Feature: Add new beta feature: delete unused crop images. You can enable this by going to
  Plugins -> ACF Image Aspect Ratio Crop -> Settings
* Fix: Update compatible version to WordPress 5.3
* Fix: Add automated tests

= 3.0.1 =
* Fix: bump plugin version

= 3.0.0 =
* Fix / Breaking change: If image was deleted, object with empty original_image field was returned. In 3.0.0 false is returned instead to keep compatibility with normal image field.

= 2.2.1 =
* Fix: Improve WPML compatibility

= 2.2.0 =
* Feature: Add compatibility with ACF Image Crop plugin
* Fix: Change default preview size to medium, as in ACF 5.8.1
* Fix: Remove image CSS shadow to match latest ACF image field styles

= 2.1.1 =
* Fix: Fix issue where crop coordinates persisted after deleting image

= 2.1.0 =
* Feature: Cropper now remembers last crop position when re-cropping image
* Feature: Add button to reset crop position to default (centered)
* Fix: Bump supported WordPress version to 5.2

= 2.0.3 =
* Fix: Allow cropping in cases when image is hosted remotely and is missing CORS headers

= 2.0.2 =
* Fix: Fix issue where saving in image modal replaced cropped image with original when "Original image" was selected in "Image displayed in attachment edit modal dialog"
* Fix: Actually save plugin version in the database for migration support
* Fix: Get file hash in debug mode from file path instead of URL

= 2.0.1 =
* Fix: Fix typo

= 2.0.0 =
* Feature: Compatibility with [WP Offload Media](https://deliciousbrains.com/wp-offload-media/) and similar plugins that move WordPress images to a remote location. Plugin will now attempt to fetch images from remote URLs if they are not found in the file system.
* Breaking change: Due to new dependencies, PHP 5.5 is now the minimum required version

= 1.3.1 =
* Fix: Fix deployment script

= 1.3.0 =
* Feature: Handle translation through w.org

= 1.2.3 =
* Fix: Update readme

= 1.2.2 =
* Fix: Update screenshots

= 1.2.0 =
* Feature: It's now possible to edit original image data instead of cropped image data when pressing the pencil button in the image field. This is handy if you have meta data such as alt text that you want to share between the original image and its cropped variants. Toggle this by selecting `Settings` in the plugin list.
* Feature: There is now a progress indicator (spinner) displayed while the image is being cropped
* Feature: If an error occurs while cropping an image, the error message is displayed inline in the modal instead of a browser alert window
* Feature: Improved styles for the cropper
* Feature: Modern and improved build process with webpack
* Feature: Make it possible to localize UI texts

= 1.1.2 =
* Fix: Bump supported WordPress version to 5.1

= 1.1.1 =
* Fix: Update readme

= 1.1.0 =
* Feature: Allow customizing file upload directory with filters `aiarc_pre_customize_upload_dir` and `aiarc_after_customize_upload_dir`

= 1.0.9 =
* Fix: Update screenshots
* Fix: Update WordPress compatibility information

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
