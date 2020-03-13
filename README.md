# ACF Image Aspect Ratio Crop Field

[![Build Status](https://img.shields.io/github/workflow/status/joppuyo/acf-image-aspect-ratio-crop/PHP%20Composer?logo=github)](https://github.com/joppuyo/acf-image-aspect-ratio-crop/actions)
[![Test status](https://img.shields.io/github/workflow/status/joppuyo/acf-image-aspect-ratio-crop/Test?label=tests&logo=github)](https://github.com/joppuyo/acf-image-aspect-ratio-crop/actions)
[![WordPress plugin](https://img.shields.io/wordpress/plugin/v/acf-image-aspect-ratio-crop?logo=wordpress)](https://wordpress.org/plugins/acf-image-aspect-ratio-crop/)
[![Active installs](https://img.shields.io/wordpress/plugin/installs/acf-image-aspect-ratio-crop.svg?logo=wordpress)](https://wordpress.org/plugins/acf-image-aspect-ratio-crop/advanced/)

A field for Advanced Custom Fields that forces the user to crop their image to specific aspect ratio after uploading. This is especially useful in responsive image use cases.

After cropping, a new cropped image variant is created in the gallery and saved into the post. Thumbnails are also generated for the new image. User can re-crop the original image at any time from the post page.

The cropped image variants are hidden by default in the media browser and on the media page but you can view them by selecting the "list view" on the media page.

## Cropping an image to 16:9 aspect ratio

![Screenshot of cropping an image](assets/images/screenshot-1.jpg?v=1552838494)

## Cropping in progress

![Screenshot of cropping in progress](assets/images/screenshot-2.jpg?v=1552838494)


## Option to re-crop the image after upload

![Screenshot of the image field](assets/images/screenshot-3.png?v=1552838494)

## Download

You can download the plugin from the [WordPress plugin directory](https://wordpress.org/plugins/acf-image-aspect-ratio-crop/), or download the latest release as a zip file from [GitHub releases](https://github.com/joppuyo/acf-image-aspect-ratio-crop/releases).

## Frequently Asked Questions

##  Can I use this plugin with a front-end acf_form?

Unfortunately this is not supported right now since the plugin requires `upload_files` capability to access the media library. If user does not have this permission, a basic upload dialog will be displayed without a cropper. You can enable cropping by assigning  `upload_files`  capability to the user role but this means that users are able to access the media library like admin users. I will look into implementing front-end form cropping without needing this capability in a future release of this plugin.

### Can I access metadata in the original image from a cropped image? 

Yes, the original image data is saved under `original_image` key in the returned ACF array. You can access data such as alt text, description and title this way.

### How is this different from the other plugin?

[Advanced Custom Fields: Image Crop Add-on](https://wordpress.org/plugins/acf-image-crop-add-on/) is based on exact image dimensions (like 640x480). This plugin uses an aspect ratio such as 4:3 instead. Using an aspect ratio is is more convenient when working with responsive images since you care about the aspect ratio more than pixel dimensions.

Of course, nothing will stop you from using an aspect ratio like 1200:630 which is similar to a pixel amount with this plugin, if you want.

Also, as of 2019, the other plugin is not actively maintained anymore and does not work well with latest ACF versions. I try to maintain this plugin as best as I can when new versions of ACF and WordPress come out.

## Thanks

Special thanks to Anders Thorborg for [ACF Image Crop](https://github.com/andersthorborg/ACF-Image-Crop) which served as a inspiration for this plugin. Also, thanks to Fengyuan Chen for the [cropper.js](https://fengyuanchen.github.io/cropperjs/) library!

## License

GPL v2 or later
