# Advanced Custom Fields: Image Aspect Ratio Crop Field
Contributors: joppuyo
Tags: acf, field, image, crop
Requires at least: 4.9
Tested up to: 5.8
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://github.com/sponsors/joppuyo
Stable Tag: 6.0.0

ACF field that allows user to crop image to a specific aspect ratio or pixel size

## Description

A field for Advanced Custom Fields that forces the user to crop their image to specific aspect ratio or pixel size after uploading. Using an aspect ratio is especially useful in responsive image use cases.

After cropping, a new cropped image variant is created in the gallery and saved into the post. Thumbnails are also generated for the new image. User can re-crop the original image at any time from the post page.

The cropped image variants are hidden by default in the media browser and on the media page but you can view them by selecting the "list view" on the media page.

There are three modes of operation: aspect ratio, pixel size and free crop. You can select this option when creating the field in ACF field options.

### Aspect ratio

Use this option if you want the image to be of specific aspect ratio like 16:9 but the pixel size is not important.

After selecting an image, user can select an area from the image that matches this aspect ratio. When crop button is pressed, the area is cropped from the original image.

If you need a smaller image size, you make use of WordPress's thumbnail functionality to access a smaller version of the image.

### Pixel size

Use this option if you need a specific pixel size image like 640x480. User will not be able to select an image smaller than the defined pixel size.

After selecting an image, user can select an area from the image they want, which can be larger than the pixel size but may not be smaller. The aspect ratio of the selection is locked according to the pixel size.

When crop button is pressed, the area is cropped from the original image. After the crop is complete, the image will be automatically scaled down to the pixel size. This means the final image will always be the specified size.

This project adheres to the [Semantic Versioning](https://semver.org/spec/v2.0.0.html) standard.

### Free crop

Crop can be done freely, there are no aspect ratio limitations.

### Requirements

* WordPress 4.9 or later
* PHP 5.6 or later
* Advanced Custom Fields 5.9 or later (Pro or Free)

### Compatibility

* Polylang Pro 2.9 or later
* Enable Media Replace
* WP Offload Media, Media Cloud and other plugins that move media files to remote location
* WPML 4.3 or later

### ACF version support policy

ACF Image Aspect Ratio Crop will support the latest three minor versions of ACF, for example ACF 5.10.X, 5.9.X and ACF 5.8.X. Bugfix releases of this plugin releases will be exempt from this policy.

### Thanks

Special thanks to Anders Thorborg for [ACF Image Crop](https://github.com/andersthorborg/ACF-Image-Crop) which served as a inspiration for this plugin. Also, thanks to Fengyuan Chen for the [cropper.js](https://fengyuanchen.github.io/cropperjs/) library!

## Installation

1. Copy the `acf-image-aspect-ratio-crop` folder into your `wp-content/plugins` folder
2. Activate the Image Aspect Ratio Crop plugin via the plugins admin page
3. Create a new field via ACF and select the Image Aspect Ratio Crop type
4. Read the description above for usage instructions

## Frequently Asked Questions

### Can I use this plugin with a front-end acf_form?

Yes, this functionality has been added in version 5.0.0. Please test it and give feedback if you encounter any issues.

### Can I access metadata in the original image from a cropped image?

Yes, the original image data is saved under `original_image` key in the returned ACF array. You can access data such as alt text, description and title this way.

### Can I use this plugin with Elementor?

No, not really. Elementor only supports built-in ACF fields. Please contact Elementor support and ask them to add support for 3rd party fields. For some workarounds for limited Elementor support, see this [post](https://wordpress.org/support/topic/excellent-plugin-5518/).

### Can I use this plugin with Beaver Builder?

No, not really. Beaver Builder only supports built-in ACF fields. Please contact Beaver Builder support and ask them to add support for 3rd party fields. However, there is a work around this limitation by using a plugin called "Toolbox For Beaver Builder". Please [see their website](https://beaverplugins.com/) for more details.

### I have an issue or I want to contribute code

Please use the [GitHub repository](https://github.com/joppuyo/acf-image-aspect-ratio-crop) to raise [issues](https://github.com/joppuyo/acf-image-aspect-ratio-crop/issues) about the plugin. You are also free to send a pull request on GitHub.

### How is this different from the other plugin?

This plugin is similar to [Advanced Custom Fields: Image Crop Add-on](https://wordpress.org/plugins/acf-image-crop-add-on/). I originally created a fork of that plugin to add functionality I need: specifying an aspect ratio instead of pixel size. Unfortunately the plugin doesn't seem to be maintained anymore so my pull request was not merged.

So I created **ACF Image Aspect Ratio Crop** from scratch as an alternative to **ACF Image Crop**.

Possibility to use a pixel size instead of aspect ratio was added later on because I got so many requests for adding that feature.

The other plugin is not actively maintained and does not work well with latest ACF versions. I try to maintain this plugin as best as I can when new versions of ACF and WordPress come out.

## Screenshots

1. Cropping an image to 16:9 aspect ratio
2. Cropping in progress
3. Option to re-crop the image after upload

## Changelog

### 7.0.0 (20XX-XX-XX)
This version is a partial rewrite of the plugin. I've moved the plugin to more modular structure which allows it to be more easily developed and maintained. At the same time I'm updating some plugin requirements to be more modern. I know it may be a bother, but supporting very old versions hinders my ability to keep testing the plugin on the latest versions of PHP, WordPress and ACF. It's not realistic to support more than 3 PHPUnit versions and have test suites that take more than an hour to complete. With automated tests I can guarantee that the plugin works with the versions listed as dependencies.

PHP 7.0 was released on 2015-12-06 (over 5 years ago), WordPress 5.0 was released on 2018-12-06 (over two years ago) and ACF 1.9.0 was released on 2020-08-17 (over a year ago). I hope these versions will be old enough for most people. If not, you can stay on version 6 of the plugin.

* Breaking change: REST API is now required. If you run into problems with the plugin, please first check that REST API is working correctly. Please check the following article for more guidance: https://redirection.me/support/problems/rest-api/
* Breaking change: minimum required PHP version is 7.0
* Breaking change: minimum required WordPress version is 5.0
* Breaking change: minimum required ACF version is 1.9.0
* Breaking change: automatic image deletion functionality has been disabled since it does not work reliably with Gutenberg blocks. It will be added back once it's been re-implemented so it works correctly in all cases. The functionality was labeled "beta" so it was always subject to change. Follow [this GitHub issue](https://github.com/joppuyo/acf-image-aspect-ratio-crop/issues/55) for updates on this feature.
* Breaking change: Smush Pro backup compatibility support removed. If you have a Smush Pro license, feel free to contribute a PR
* Feature: improved plugin structure

### 6.0.0 (2021-10-20)

* Breaking change: Changed how minimum and maximum image pixel sizes work
* Minimum image size previously only affected the original uploaded file in aspect ratio mode. Now crop area must be larger than minimum dimensions.
* Maximum image size previously only affected the original uploaded file in aspect ratio mode. Now if cropped area is larger than maximum dimensions, final image will be scaled down to maximum dimensions.
* These changes do not apply to free crop mode, free crop minimum and maximum dimensions are disabled.
* Pixel size mode has always had minimum dimensions. Enforcing maximum dimensions are now disabled for pixel size mode since the image is always scaled down to pixel size.

### 5.1.4 (2021-09-18)
* Fix: Bump compatibility to WP 5.8

### 5.1.3 (2021-09-18)
* Fix: Fix potential issue with translations, see [this thread](https://wordpress.org/support/topic/get_plugin_data-called-too-early-breaks-translations-2/) for more information

### 5.1.2 (2021-03-11)
* Fix: Bump stable tag

### 5.1.1 (2021-03-11)
* Fix: Bump compatiblity to WP 5.7

### 5.1.0 (2021-01-30)
* Feature: Added REST API compatibility mode. This enables you to crop images in the WordPress administration interface with admin-ajax.php instead of the REST API. You can enable this by going to Plugins -> ACF Image Aspect Ratio Crop -> Settings. Please note that this is a temporary fix for sites that don't have REST API enabled. The compatibility mode will be removed in a future major release of the plugin.
* Fix: Improved error logging

### 5.0.6 (2021-01-28)
* Fix: Fix error caused by wrong parameter order in PHP 8

### 5.0.5 (2020-12-22)
* Fix: Fixed compatibility issue with Polylang Pro 2.9

### 5.0.4 (2020-12-13)
* Fix: Fixed compatibility issue with WordPress 5.6 REST API changes
* Fix: Fixed issue with front-end image crop where crop image field overwrite another crop image field
* Change: Bumped tested up to WordPress 5.6

### 5.0.3 (2020-12-03)
* Fix: Fixed bug where cropping didn't work in a multisite subsite

### 5.0.2 (2020-11-30)
* Fix: Updated translation strings

### 5.0.1 (2020-11-30)
* Fix: Fixed compatibility issue with Yoast SEO that caused a bug where media modal didn't open

### 5.0.0 (2020-11-30)
* Breaking change: REST API is now used for cropping image instead of admin-ajax
* Feature: Added frontend image crop. This allows you to use crop functionality with [ACF forms](https://www.advancedcustomfields.com/resources/create-a-front-end-form/) on the front-end, even if the user does not have access to the media library.

I'd like to take this moment to announce that I have a [GitHub Sponsors](https://github.com/sponsors/joppuyo/) page. Maintaining this plugin is a lot of work and front-end image crop is one of those features I don't use personally but I was requested so many times had to add it to the plugin, which took multiple days of work.

If you find this feature useful or if you otherwise want to support the development of this plugin, please consider [supporting me on GitHub Sponsors](https://github.com/sponsors/joppuyo/). Thank you!
