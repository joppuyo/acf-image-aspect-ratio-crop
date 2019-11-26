<?php

/*
Plugin Name: Advanced Custom Fields: Image Aspect Ratio Crop
Plugin URI: https://github.com/joppuyo/acf-image-aspect-ratio-crop
Description: ACF field that allows user to crop image to a specific aspect ratio
Version: 3.1.11
Author: Johannes Siipola
Author URI: https://siipo.la
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: acf-image-aspect-ratio-crop
Stable Tag: 3.1.11
*/

// exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

// Check if we are using local Composer
if (file_exists(__DIR__ . '/vendor')) {
    require 'vendor/autoload.php';
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
     *  @type	function
     *  @date	17/02/2016
     *  @since	1.0.0
     *
     *  @param	n/a
     *  @return	n/a
     */

    function __construct()
    {
        // settings
        // - these will be passed into the field class.

        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $this->settings = [
            'version' => get_plugin_data(__FILE__)['Version'],
            'url' => plugin_dir_url(__FILE__),
            'path' => plugin_dir_path(__FILE__),
        ];
        $this->temp_path = null;

        // set text domain
        // https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
        load_plugin_textdomain('acf-image-aspect-ratio-crop');

        add_action('plugins_loaded', [$this, 'initialize_settings']);

        // include field
        add_action('acf/include_field_types', [
            $this,
            'include_field_types',
        ]); // v5

        add_action('acf/save_post', function ($post_id) {

            $this->debug('post_id');
            $this->debug($post_id);
            $this->debug('POST');
            $this->debug($_POST);

            if ($post_id === 'options' && !empty($_GET['page'])) {
                // Options page needs an unique id
                $post_id = $_GET['page'];
            }

            $temp_post_id = $_POST['aiarc_temp_post_id'];

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
                update_post_meta($attachment->ID, 'acf_image_aspect_ratio_crop_parent_post_id', $post_id);
                // Remove temporary data
                delete_post_meta($attachment->ID, 'acf_image_aspect_ratio_crop_temp_post_id');
                delete_post_meta($attachment->ID, 'acf_image_aspect_ratio_crop_timestamp');
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
                        'key' => 'acf_image_aspect_ratio_crop_parent_post_id',
                        'value' => $post_id,
                        'compare' => '=',
                    ],
                ],
            ]);

            // Find crop field names
            // Compare crop field names to post input
            // Delete unused posts

            $this->debug('found following post attachments');
            $this->debug($post_attachments);

            $this->debug('found following fields');
            $fields = $_POST['acf'];
            $this->debug($fields);

            $preserve_ids = [];

            foreach ($fields as $key => $field) {
                $definition = get_field_object($key);
                if (!empty($field) && $definition['type'] === 'image_aspect_ratio_crop') {
                    array_push($preserve_ids, $field);
                }
            }

            $post_attachment_ids = array_map(function ($attachment){
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
        }, 15);

        add_action('wp_ajax_acf_image_aspect_ratio_crop_crop', function () {
            // WTF WordPress
            $post = array_map('stripslashes_deep', $_POST);

            $data = json_decode($post['data'], true);

            $image_data = wp_get_attachment_metadata($data['id']);

            // If the difference between the images is less than half a percentage, use the original image
            // prettier-ignore
            if ($image_data['height'] - $data['height'] < $image_data['height'] * 0.005 &&
                $image_data['width'] - $data['width'] < $image_data['width'] * 0.005
            ) {
                wp_send_json(['id' => $data['id']]);
                wp_die();
            }

            do_action('aiarc_pre_customize_upload_dir');

            $media_dir = wp_upload_dir();

            do_action('aiarc_after_customize_upload_dir');

            // WP Smush compat: use original image if it exists
            $file = $media_dir['basedir'] . '/' . $image_data['file'];
            $parts = explode('.', $file);
            $extension = array_pop($parts);
            $backup_file = implode('.', $parts) . '.bak.' . $extension;

            $image = null;

            if (file_exists($backup_file)) {
                $image = wp_get_image_editor($backup_file);
            } else if (file_exists($file)) {
                $image = wp_get_image_editor($file);
            } else {
                // Let's attempt to get the file by URL
                $temp_name = wp_generate_uuid4();
                $temp_directory = get_temp_dir();
                $this->temp_path = $temp_directory . $temp_name;
                try {
                    $guzzle = new \GuzzleHttp\Client();
                    $fetched_image = $guzzle->get(wp_get_attachment_url($data['id']));
                    $result = @file_put_contents($this->temp_path, $fetched_image->getBody());
                    if ($result === false) {
                        throw new Exception('Failed to save image');
                    }
                    $image = wp_get_image_editor($this->temp_path);
                } catch (Exception $exception) {
                    $this->cleanup();
                    wp_send_json('Failed fetch remote image', 500);
                    wp_die();
                }
            }

            if (is_wp_error($image)) {
                $this->cleanup();
                wp_send_json('Failed to open image', 500);
                wp_die();
            }

            $image->crop(
                $data['x'],
                $data['y'],
                $data['width'],
                $data['height']
            );

            // Retrieve original filename and seperate it from its file extension
            $original_file_name = explode(
                '.',
                basename($image_data['file'])
            );

            // Retrieve and remove file extension from array
            $original_file_extension = array_pop($original_file_name);

            // Generate new base filename
            $target_file_name =
                implode('.', $original_file_name) .
                '-aspect-ratio-' .
                $data['aspectRatioWidth'] .
                'x' .
                $data['aspectRatioHeight'] .
                '.' .
                $original_file_extension;

            // Generate target path new file using existing media library
            $target_file_path =
                $media_dir['path'] .
                '/' .
                wp_unique_filename($media_dir['path'], $target_file_name);

            // Get the relative path to save as the actual image url
            $target_relative_path = str_replace(
                $media_dir['basedir'] . '/',
                '',
                $target_file_path
            );

            //$save = $image->save('test.jpg');
            $save = $image->save($target_file_path);
            if (is_wp_error($save)) {
                $this->cleanup();
                wp_send_json('Failed to crop', 500);
                wp_die();
            }

            $wp_filetype = wp_check_filetype($target_relative_path, null);

            $attachment = [
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => preg_replace(
                    '/\.[^.]+$/',
                    '',
                    $target_file_name
                ),
                'post_content' => '',
                'post_status' => 'publish',
            ];

            $attachment_id = wp_insert_attachment(
                $attachment,
                $target_relative_path
            );

            if (is_wp_error($attachment_id)) {
                $this->cleanup();
                wp_send_json('Failed to save attachment', 500);
                wp_die();
            }

            require_once ABSPATH . "wp-admin" . '/includes/image.php';
            $attachment_data = wp_generate_attachment_metadata(
                $attachment_id,
                $target_file_path
            );
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            add_post_meta(
                $attachment_id,
                'acf_image_aspect_ratio_crop',
                true,
                true
            );
            add_post_meta(
                $attachment_id,
                'acf_image_aspect_ratio_crop_original_image_id',
                $data['id'],
                true
            );
            add_post_meta(
                $attachment_id,
                'acf_image_aspect_ratio_crop_coordinates',
                [
                    'x' => $data['x'],
                    'y' => $data['y'],
                    'width' => $data['width'],
                    'height' => $data['height']
                ],
                true
            );

            /* Timestamp so we can purge unattached crop attachments periodically after specific time
               (like a week or so) */
            add_post_meta(
                $attachment_id,
                'acf_image_aspect_ratio_crop_timestamp',
                (new DateTime())->format('U'),
                true
            );

            $this->debug('data');
            $this->debug($data);

            $this->debug('temp post id');
            $this->debug($data['temp_post_id']);

            add_post_meta(
                $attachment_id,
                'acf_image_aspect_ratio_crop_temp_post_id',
                $data['temp_post_id'],
                true
            );

            // WPML compat
            do_action('wpml_sync_all_custom_fields', $attachment_id);

            $this->cleanup();
            wp_send_json(['id' => $attachment_id]);
            wp_die();
        });

        // WPML compat
        add_action('wpml_media_create_duplicate_attachment', function($attachment_id, $duplicate_attachment_id) {
            $keys = [
                'acf_image_aspect_ratio_crop',
                'acf_image_aspect_ratio_crop_original_image_id',
                'acf_image_aspect_ratio_crop_coordinates'
            ];
            foreach ($keys as $key) {
                $value = get_post_meta($attachment_id, $key, true);
                if ($value) {
                    update_post_meta($duplicate_attachment_id, $key, $value);
                }
            }
        }, 25, 2);


        // Enable Media Replace compat: if file is replaced using Enable Media Replace, wipe the coordinate data
        add_filter('wp_handle_upload', function ($data) {
            $id = attachment_url_to_postid($data['url']);
            if ($id !== 0) {
                $posts = get_posts([
                    'post_type' => 'attachment',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key'     => 'acf_image_aspect_ratio_crop_original_image_id',
                            'value'   => $id,
                            'compare' => '=',
                        ],
                        [
                            'key'     => 'acf_image_aspect_ratio_crop_coordinates',
                            'compare' => 'EXISTS',
                        ],
                    ],
                ]);
                if (!empty($posts)) {
                    foreach ($posts as $post) {
                        delete_post_meta($post->ID, 'acf_image_aspect_ratio_crop_coordinates');
                    }
                }
            }
            return $data;
        });

        // Hide cropped images in media library grid view
        add_filter('ajax_query_attachments_args', function ($args) {
            // post__in is only defined when clicking edit button in attachment
            if (empty($args['post__in'])) {
                $args['meta_query'] = [
                    [
                        'key' => 'acf_image_aspect_ratio_crop',
                        'compare' => 'NOT EXISTS',
                    ],
                ];
            }
            return $args;
        });

        // Add plugin to WordPress admin menu
        add_action('admin_menu', function () {
            add_submenu_page(
                null,
                __("ACF Image Aspect Ratio Crop", "acf-image-aspect-ratio-crop"),
                __("ACF Image Aspect Ratio Crop", "acf-image-aspect-ratio-crop"),
                'manage_options',
                "acf-image-aspect-ratio-crop",
                [$this, 'settings_page']
            );
        });

        // Add settings link on the plugin page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
            $settings_link = '<a href="options-general.php?page=acf-image-aspect-ratio-crop">' . __('Settings', "acf-image-aspect-ratio-crop") . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        });

        if (!wp_next_scheduled('aiarc_delete_unused_attachments')) {
            wp_schedule_event(time(), 'daily', 'aiarc_delete_unused_attachments');
        }

        add_action('aiarc_delete_unused_attachments', [$this, 'delete_unused_attachments']);
    }

    /*
     *  include_field_types
     *
     *  This function will include the field type class
     *
     *  @type	function
     *  @date	17/02/2016
     *  @since	1.0.0
     *
     *  @param	$version (int) major ACF version. Defaults to false
     *  @return	n/a
     */

    function include_field_types()
    {
        // include
        include_once 'fields/class-npx-acf-field-image-aspect-ratio-crop-v5.php';
    }

    /**
     * Render WordPress plugin settings page
     */
    public function settings_page()
    {
        $updated = false;
        $settings = $this->user_settings;
        if (!empty($_POST)) {
            check_admin_referer("acf-image-aspect-ratio-crop");

            if (!empty($_POST['modal_type'])) {
                $settings['modal_type'] = $_POST['modal_type'];
            }

            if (!empty($_POST['delete_unused'])) {
                $settings['delete_unused'] = filter_var($_POST['delete_unused'], FILTER_VALIDATE_BOOLEAN);
            }

            update_option("acf-image-aspect-ratio-crop-settings", $settings);
            $updated = true;
        }
        $modal_type = $settings['modal_type'];
        $delete_unused = $settings['delete_unused'];
        echo '<div class="wrap">';
        echo '    <h1>' . __('ACF Image Aspect Ratio Crop', 'acf-image-aspect-ratio-crop') . '</h1>';
        echo '    <div class="js-finnish-base-forms-admin-notices"></div>';
        if ($updated) {
            echo '    <div class="notice notice-success">';
            echo '        <p>' . __('Options have been updated', "acf-image-aspect-ratio-crop") . '</p>';
            echo '    </div>';
        }
        echo '    <form method="post">';
        echo '    <table class="form-table">';
        echo '        <tbody>';
        echo '            <tr>';
        echo '                <th scope="row">';
        echo '                    <label for="modal_type">' . __('Image displayed in attachment edit modal dialog', "acf-image-aspect-ratio-crop") . '</label>';
        echo '                </th>';
        echo '                <td>';
        echo '                <p><input type="radio" id="cropped" name="modal_type" value="cropped" ' . checked($modal_type, 'cropped', false) . '><label for="cropped"> ' . __('Cropped image', "acf-image-aspect-ratio-crop") . '</label></p>';
        echo '                <p><input type="radio" id="original" name="modal_type" value="original" ' . checked($modal_type, 'original', false) . '><label for="original"> ' .  __('Original image', "acf-image-aspect-ratio-crop") . '</label></p>';
        echo '                </td>';
        echo '            </tr>';
        echo '            <tr>';
        echo '                <th scope="row">';
        echo '                    <label for="modal_type">' . __('Delete unused cropped images', "acf-image-aspect-ratio-crop") . ' ' . __('(Beta feature)', "acf-image-aspect-ratio-crop") . '</label>';
        echo '                </th>';
        echo '                <td>';
        echo '                <p><input type="radio" id="delete_unused_true" name="delete_unused" value="true" ' . checked($delete_unused, true, false) . '><label for="delete_unused_true"> ' . __('Enabled', "acf-image-aspect-ratio-crop") . '</label></p>';
        echo '                <p><input type="radio" id="delete_unused_false" name="delete_unused" value="false" ' . checked($delete_unused, false, false) . '><label for="delete_unused_false"> ' .  __('Disabled', "acf-image-aspect-ratio-crop") . '</label></p>';
        echo '                </td>';
        echo '            </tr>';
        echo '        </tbody>';
        echo '    </table>';
        echo '    <p>'. __('Please note that "Delete unused cropped images" feature is a beta feature because it requires more testing. Please do not enable the option without first backing up your database and uploads in order to prevent potential data loss.', 'acf-image-aspect-ratio-crop') . '</p>';
        echo '    <p class="submit">';
        echo '        <input class="button-primary js-finnish-base-forms-submit-button" type="submit" name="submit-button" value="Save">';
        echo '    </p>';
        wp_nonce_field("acf-image-aspect-ratio-crop");
        echo '    </form>';
        echo '</div>';
    }

    function initialize_settings() {
        $database_version = get_option('acf-image-aspect-ratio-crop-version');
        $plugin_version = $this->settings['version'];
        $settings = get_option('acf-image-aspect-ratio-crop-settings') ?
            get_option('acf-image-aspect-ratio-crop-settings') :
            [];

        // Initialize database settings
        if (empty($database_version)) {
            update_option('acf-image-aspect-ratio-crop-version', $plugin_version);
        }

        if (version_compare(get_option('acf-image-aspect-ratio-crop-version'), $plugin_version, 'lt')) {
            // Database migrations here
            update_option('acf-image-aspect-ratio-crop-version', $plugin_version);
        };

        $default_user_settings = [
            'modal_type' => 'cropped',
            'delete_unused' => false,
        ];

        $this->user_settings = array_merge($default_user_settings, $settings);
        $this->settings['user_settings'] = $this->user_settings;
    }

    /**
     * Clean up any temporary files
     */
    private function cleanup() {
        if ($this->temp_path) {
            @unlink($this->temp_path);
        }
    }

    public function delete_unused_attachments () {

        $this->debug('delete unused attachments cron');

        // Bail early if unused attachment deletion is disabled
        if (!$this->user_settings['delete_unused']) {
            $this->debug('user has disabled unused attachment deletion');
            return;
        }

        $timestamp = (new DateTime())
            ->modify('-7 days')
            ->format('U');

        $posts = get_posts([
            'post_type' => 'attachment',
            'meta_query' => [
                [
                    'key' => 'acf_image_aspect_ratio_crop_timestamp',
                    'compare' => '<',
                    'value' => $timestamp,
                    'type' => 'numeric',
                ],
            ],
        ]);

        foreach ($posts as $post) {
            $this->debug('deleting unused attachment ' . $post->ID);
            wp_delete_attachment($post->ID, true);
        }

    }

    function debug($message)
    {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log(print_r($message, true));
        }
    }
}

// initialize
new npx_acf_plugin_image_aspect_ratio_crop();
