<?php

/*
Plugin Name: Advanced Custom Fields: Image Aspect Ratio Crop
Plugin URI: https://github.com/joppuyo/acf-image-aspect-ratio-crop
Description: ACF field that allows user to crop image to a specific aspect ratio or pixel size
Version: 6.0.5
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

class npx_acf_plugin_image_aspect_ratio_crop
{
    // vars
    public $settings;
    public $user_settings;
    public $temp_path;

    /*
     *  __construct
     *
     *  This function will setup the class functionality
     *
     *  @type    function
     *  @date    17/02/2016
     *  @since   1.0.0
     *
     *  @param   n/a
     *  @return  n/a
     */

    function __construct()
    {
        // settings
        // - these will be passed into the field class.

        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $this->settings = [
            'version' => get_plugin_data(__FILE__, false, false)['Version'],
            'url' => plugin_dir_url(__FILE__),
            'path' => plugin_dir_path(__FILE__),
        ];
        $this->temp_path = null;

        // set text domain
        // https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
        add_action('init', function () {
            load_plugin_textdomain('acf-image-aspect-ratio-crop');
        });

        add_action('plugins_loaded', [$this, 'initialize_settings']);

        // include field
        add_action('acf/include_field_types', [$this, 'include_field_types']); // v5

        add_action('rest_api_init', [$this, 'rest_api_init']);

        add_action(
            'acf/save_post',
            function ($post_id) {
                if ($post_id === 'options' && !empty($_GET['page'])) {
                    // Options page needs an unique id
                    $post_id = $_GET['page'];
                }

                $temp_post_id = !empty($_POST['aiarc_temp_post_id']) ? $_POST['aiarc_temp_post_id'] : null;

                // Bail early if we don't have data to process
                if (empty($temp_post_id)) {
                    return;
                }

                // Let's find all posts with temp post id
                $temp_attachments = get_posts([
                    'post_type' => 'attachment',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key' => 'acf_image_aspect_ratio_crop_temp_post_id',
                            'value' => $temp_post_id,
                            'compare' => '=',
                        ],
                    ],
                ]);

                foreach ($temp_attachments as $attachment) {
                    // Attach parent post id to temporary attachments
                    update_post_meta(
                        $attachment->ID,
                        'acf_image_aspect_ratio_crop_parent_post_id',
                        $post_id
                    );
                    // Remove temporary data
                    delete_post_meta(
                        $attachment->ID,
                        'acf_image_aspect_ratio_crop_temp_post_id'
                    );
                    delete_post_meta(
                        $attachment->ID,
                        'acf_image_aspect_ratio_crop_timestamp'
                    );
                }

                // Bail early if unused attachment deletion is disabled
                if (!$this->user_settings['delete_unused']) {
                    return;
                }

                $post_attachments = get_posts([
                    'post_type' => 'attachment',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key' =>
                            'acf_image_aspect_ratio_crop_parent_post_id',
                            'value' => $post_id,
                            'compare' => '=',
                        ],
                    ],
                ]);

                // Find crop field names
                // Compare crop field names to post input
                // Delete unused posts

                $current_post = get_post($post_id);

                if (function_exists('parse_blocks') && $current_post) {
                    $this->debug('parse blocks');
                    $blocks = parse_blocks($current_post->post_content);
                    $this->debug($blocks);
                }

                $this->debug('found following post attachments');
                $this->debug($post_attachments);

                $this->debug('found following fields');
                $fields = $_POST['acf'];
                $this->debug($fields);

                $preserve_ids = [];

                $this->check_fields($fields, $preserve_ids);

                $post_attachment_ids = array_map(function ($attachment) {
                    return $attachment->ID;
                }, $post_attachments);

                $delete_ids = array_diff($post_attachment_ids, $preserve_ids);

                $this->debug('preserve ids');
                $this->debug($preserve_ids);
                $this->debug('all ids');
                $this->debug($post_attachment_ids);
                $this->debug('delete ids');
                $this->debug($delete_ids);

                foreach ($delete_ids as $delete_id) {
                    wp_delete_attachment($delete_id, true);
                }
            },
            15
        );
    }
}