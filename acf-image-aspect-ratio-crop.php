<?php

/*
Plugin Name: Advanced Custom Fields: Image Aspect Ratio Crop
Plugin URI: https://github.com/joppuyo/acf-image-aspect-ratio-crop
Description: ACF field that allows user to crop image to a specific aspect ratio or pixel size
Version: 6.0.0
Author: Johannes Siipola
Author URI: https://siipo.la
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: acf-image-aspect-ratio-crop
*/

// Load c3 in CI environment for code coverage
if (file_exists(__DIR__ . '/c3.php')) {
    require_once __DIR__ . '/c3.php';
}

// exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

require_once __DIR__ . '/vendor/autoload.php';

$aiarc = \Joppuyo\AcfImageAspectRatioCrop\Plugin::get_instance();
