<?php
/**
 * Plugin Name: AIARC Test: Blocks
 * Description: Registers an ACF block version 1 block with acf_register_block_type(), on ACF 6 and newer an ACF block version 2 block with block.json, and on ACF 6.6 and newer an ACF block version 3 block, each with a 16:9 crop field, for the end to end tests. Test fixture, do not use on a real site.
 * Version: 1.0.0
 * Author: Johannes Siipola
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit();
}

add_action('acf/init', function () {
    if (!function_exists('acf_register_block_type')) {
        return;
    }

    // acf_block_version only exists from ACF 6.0. On WordPress 7.1 ACF 6.8
    // defaults it to 3 even for blocks registered in PHP, so it is set
    // explicitly. The WordPress block API version is left to ACF: 1 on ACF
    // 5.10 and older, 2 from ACF 5.12.
    acf_register_block_type([
        'name' => 'aiarc-test-v1',
        'title' => 'AIARC Test Block V1',
        'category' => 'widgets',
        'icon' => 'format-image',
        'mode' => 'edit',
        'post_types' => ['post'],
        'supports' => ['mode' => true],
        'acf_block_version' => 1,
        'render_callback' => 'aiarc_test_blocks_render',
    ]);

    // ACF reads the acf key in block.json from version 6.0. On older versions
    // this would register a plain block without fields.
    if (version_compare(acf_get_setting('version'), '6.0', '>=')) {
        register_block_type(__DIR__ . '/blocks/v2');
    }

    // ACF block version 3 arrived in ACF 6.6
    if (version_compare(acf_get_setting('version'), '6.6', '>=')) {
        register_block_type(__DIR__ . '/blocks/v3');
    }

    // The location UI for blocks differs between ACF versions, so unlike the
    // other scenarios the block field group is registered in code
    acf_add_local_field_group([
        'key' => 'group_aiarc_test_blocks',
        'title' => 'AIARC test blocks',
        'fields' => [
            [
                'key' => 'field_aiarc_test_blocks_crop_image',
                'label' => 'Crop Image',
                'name' => 'crop_image',
                'type' => 'image_aspect_ratio_crop',
                'crop_type' => 'aspect_ratio',
                'aspect_ratio_width' => 16,
                'aspect_ratio_height' => 9,
            ],
        ],
        'location' => [
            [['param' => 'block', 'operator' => '==', 'value' => 'acf/aiarc-test-v1']],
            [['param' => 'block', 'operator' => '==', 'value' => 'acf/aiarc-test-v2']],
            [['param' => 'block', 'operator' => '==', 'value' => 'acf/aiarc-test-v3']],
        ],
    ]);
});

/**
 * Prints the block's field values as JSON so the tests can read them on the
 * front end, the same way aiarc-test-field-output does for post fields.
 */
function aiarc_test_blocks_render($block)
{
    $data = [
        'name' => $block['name'],
        'fields' => get_fields(),
        'raw' => get_fields(false, false),
    ];

    // ACF block version 3 strips script elements from the editor preview, so
    // the tests read the raw value from an attribute there
    $raw = get_field('crop_image', false, false);

    echo '<div class="aiarc-test-block" data-crop-image="' .
        esc_attr($raw ? $raw : '') .
        '">';
    echo '<p>' . esc_html($block['title']) . '</p>';
    // JSON_HEX_TAG keeps a value containing </script> from ending the element
    echo '<script type="application/json" class="aiarc-test-block-fields">' .
        wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP) .
        '</script>';
    echo '</div>';
}
