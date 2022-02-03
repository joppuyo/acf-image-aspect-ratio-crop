<?php

// Plugin name: Post ACF Fields Debug

add_action('wp_footer', function () {
    $image = get_fields();
    echo '<pre>';
    echo json_encode(
        $image,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    echo '</pre>';
    echo '<textarea id="jsondata">' . json_encode($image) . '</textarea>';
});
