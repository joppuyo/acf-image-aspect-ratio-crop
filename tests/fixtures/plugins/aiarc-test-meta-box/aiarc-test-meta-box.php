<?php
/**
 * Plugin Name: AIARC Test: Meta Box
 * Description: Registers a 16:9 crop field in a meta box on posts, so the end to end tests can put a crop field in a meta box and another in a block on the same post. Test fixture, do not use on a real site.
 * Version: 1.0.0
 * Author: Johannes Siipola
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit();
}

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_aiarc_test_meta_box',
        'title' => 'AIARC test meta box',
        'fields' => [
            [
                'key' => 'field_aiarc_test_meta_box_crop_image',
                'label' => 'Meta Box Crop Image',
                'name' => 'meta_crop_image',
                'type' => 'image_aspect_ratio_crop',
                'crop_type' => 'aspect_ratio',
                'aspect_ratio_width' => 16,
                'aspect_ratio_height' => 9,
            ],
        ],
        'location' => [
            [['param' => 'post_type', 'operator' => '==', 'value' => 'post']],
        ],
    ]);
});
