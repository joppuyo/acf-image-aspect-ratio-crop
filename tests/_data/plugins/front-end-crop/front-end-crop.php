<?php

// Plugin name: Front end crop

add_action('get_header', 'acf_form_head');

add_filter('the_content', function () {
    if (!is_admin() && is_singular()) {
        acf_form();
    }
});
