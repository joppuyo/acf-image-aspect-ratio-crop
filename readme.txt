=== Advanced Custom Fields: Image Aspect Ratio Crop Field ===
Contributors: joppuyo
Tags: acf, field, image, crop
Requires at least: 4.9
Tested up to: 5.4
Requires PHP: 5.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

ACF field that allows user to crop image to a specific aspect ratio or pixel size

== Description ==

A field for Advanced Custom Field that forces the user to crop their image to specific aspect ratio or pixel size after uploading. Using an aspect ratio is especially useful in responsive image use cases.

After cropping, a new cropped image variant is created in the gallery and saved into the post. Thumbnails are also generated for the new image. User can re-crop the original image at any time from the post page.

The cropped image variants are hidden by default in the media browser and on the media page but you can view them by selecting the "list view" on the media page.

There are two modes of operation: aspect ratio and pixel size. You can select this option when creating the field in ACF field options.

= Aspect ratio =

Use this option if you want the image to be of specific aspect ratio like 16:9 but the pixel size is not important.

After selecting an image, user can select an area from the image that matches this aspect ratio. When crop button is pressed, the area is cropped from the original image.

If you need a smaller image size, you make use of WordPress's thumbnail functionality to access a smaller version of the image.

= Pixel size =

Use this option if you need a specific pixel size image like 640x480. User will not be able to select an image smaller than the defined pixel size.

After selecting an image, user can select an area from the image they want, which can be larger than the pixel size but may not be smaller. The aspect ratio of the selection is locked according to the pixel size.

When crop button is pressed, the area is cropped from the original image. After the crop is complete, the image will be automatically scaled down to the pixel size. This means the final image will always be the specified size.

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

= Can I use this plugin with a front-end acf_form? =

Unfortunately this is not supported right now since the plugin requires `upload_files` capability to access the media library. If user does not have this permission, a basic upload dialog will be displayed without a cropper. You can enable cropping by assigning  `upload_files`  capability to the user role but this means that users are able to access the media library like admin users. I will look into implementing front-end form cropping without needing this capability in a future release of this plugin.

= Can I access metadata in the original image from a cropped image? =

Yes, the original image data is saved under `original_image` key in the returned ACF array. You can access data such as alt text, description and title this way.

= I have an issue or I want to contribute code =

Please use the [GitHub repository](https://github.com/joppuyo/acf-image-aspect-ratio-crop) to raise [issues](https://github.com/joppuyo/acf-image-aspect-ratio-crop/issues) about the plugin. You are also free to send a pull request on GitHub.

= How is this different from the other plugin? =

This plugin is similar to [Advanced Custom Fields: Image Crop Add-on](https://wordpress.org/plugins/acf-image-crop-add-on/). I originally created a fork of that plugin to add functionality I need: specifying an aspect ratio instead of pixel size. Unfortunately the plugin doesn't seem to be maintained anymore so my pull request was not merged.

So I created **ACF Image Aspect Ratio Crop** from scratch as an alternative to **ACF Image Crop**.

Possibility to use a pixel size instead of aspect ratio was added later on because I got so many requests for adding that feature.

The other plugin is not actively maintained and does not work well with latest ACF versions. I try to maintain this plugin as best as I can when new versions of ACF and WordPress come out.

== Screenshots ==

1. Cropping an image to 16:9 aspect ratio
2. Cropping in progress
3. Option to re-crop the image after upload

== Changelog ==

= 3.2.0 =
* Feature: Added an option to use a pixel size instead of aspect ratio. Check the [readme](https://wordpress.org/plugins/acf-image-aspect-ratio-crop/) for more information about how it works.
* Fix: Fixed images displaying in wrong rotation in WP < 5.3
* Fix: Visual bug fixes to cropper and field edit buttons to prevent overflowing of elements
* Change: updated tested WP version to 5.4

= 3.1.12 =
* Fix: Improved compatibility with WordPress 5.3 large image handing
* Fix: Allow closing crop modal with escape key
* Change: change file name suffix aspect ratio from x to dash because this caused some issues with WP 5.3.
  Now file my-image-aspect-ratio-16x9.jpeg will be called my-image-aspect-ratio-16-9.jpeg instead
* Fix: Fix problem where "delete unused cropped images" did not work properly with nested fields

= 3.1.11 =
* Fix: Remove ramsey/uuid dependency in favor of using native wp function since the dependency caused issues in some
  server configurations

= 3.1.10 =
* Fix: Fix issue where image was not visible in backed due to malformed URL

= 3.1.8 =
* Fix: Deployment fix

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
