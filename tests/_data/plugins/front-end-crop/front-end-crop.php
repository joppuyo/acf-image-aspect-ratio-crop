<?php

// Plugin name: Front end crop

add_filter(
    'wp_head',
    function () {
        if (!is_admin() && !defined('REST_REQUEST')) {
            acf_form_head();
        }
    },
    1
);

add_filter('the_content', function () {
    if (!is_admin() && !defined('REST_REQUEST')) {
        acf_form();
    }
});
