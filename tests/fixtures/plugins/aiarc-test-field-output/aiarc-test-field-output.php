<?php
/**
 * Plugin Name: AIARC Test: Field Output
 * Description: Prints the ACF field values of the current post as JSON in the front end footer so the end to end tests can read them. Test fixture, do not use on a real site.
 * Version: 1.0.0
 * Author: Johannes Siipola
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit();
}

add_action('wp_footer', function () {
    if (!is_singular() || !function_exists('get_fields')) {
        return;
    }

    $post_id = get_queried_object_id();

    $data = [
        'post_id' => $post_id,
        'fields' => get_fields($post_id),
        'raw' => get_fields($post_id, false),
    ];

    // JSON_HEX_TAG keeps a value containing </script> from ending the element
    echo '<script type="application/json" id="aiarc-test-fields">' .
        wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP) .
        '</script>';
});
