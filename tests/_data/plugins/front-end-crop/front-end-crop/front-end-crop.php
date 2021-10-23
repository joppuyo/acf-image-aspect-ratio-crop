<?php

// Plugin name: Front end crop

add_action('get_header', 'acf_form_head');

add_filter('the_content', function () {
    if (!is_admin() && is_singular()) {
        acf_form();
    }
});

add_action(
    'wp_head',
    function () {
        echo '<style>html{ scroll-behavior: auto !important; }</style>';
    },
    100
);
