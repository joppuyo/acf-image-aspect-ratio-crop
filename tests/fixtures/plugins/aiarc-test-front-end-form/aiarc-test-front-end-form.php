<?php
/**
 * Plugin Name: AIARC Test: Front End Form
 * Description: Renders an ACF front end form for the current post after the content of every single post, so the end to end tests can upload and crop from the front end. Test fixture, do not use on a real site.
 * Version: 1.0.0
 * Author: Johannes Siipola
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit();
}

// acf_form_head() handles the submission and may redirect, so it has to run
// before any output. template_redirect works for both classic and block themes.
add_action(
    'template_redirect',
    function () {
        if (function_exists('acf_form_head') && is_singular()) {
            acf_form_head();
        }
    },
    1
);

add_filter(
    'the_content',
    function ($content) {
        static $rendered = false;

        if (
            $rendered ||
            !function_exists('acf_form') ||
            !is_singular() ||
            !is_main_query() ||
            get_the_ID() !== get_queried_object_id()
        ) {
            return $content;
        }

        $rendered = true;

        ob_start();
        acf_form(['id' => 'aiarc-test-form']);

        return $content . ob_get_clean();
    },
    20
);

// Smooth scrolling in the theme would make scrolling asynchronous, which the
// tests do not expect
add_action(
    'wp_head',
    function () {
        echo '<style>html { scroll-behavior: auto !important; }</style>';
    },
    100
);
