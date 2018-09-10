<?php

/*
Plugin Name: Advanced Custom Fields: Image Aspect Ratio Crop
Plugin URI: https://github.com/joppuyo/acf-image-aspect-ratio-crop
Description: ACF field that allows user to crop image to a specific aspect ratio
Version: 1.0.8
Author: Johannes Siipola
Author URI: https://siipo.la
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

// check if class already exists
if (!class_exists('npx_acf_plugin_image_aspect_ratio_crop')):
    class npx_acf_plugin_image_aspect_ratio_crop
    {
        // vars
        public $settings;

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
            $this->settings = array(
                'version' => '1.0.8',
                'url' => plugin_dir_url(__FILE__),
                'path' => plugin_dir_path(__FILE__),
            );

            // set text domain
            // https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
            load_plugin_textdomain(
                'acf-image-aspect-ratio-crop',
                false,
                plugin_basename(dirname(__FILE__)) . '/lang'
            );

            // include field
            add_action('acf/include_field_types', array(
                $this,
                'include_field_types',
            )); // v5

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

                $media_dir = wp_upload_dir();

                // WP Smush compat: use original image if it exists
                $file = $media_dir['basedir'] . '/' . $image_data['file'];
                $parts = explode('.', $file);
                $extension = array_pop($parts);
                $backup_file = implode('.', $parts) . '.bak.' . $extension;

                if (file_exists($backup_file)) {
                    $image = wp_get_image_editor($backup_file);
                } else {
                    $image = wp_get_image_editor($file);
                }

                if (is_wp_error($image)) {
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

                wp_send_json(['id' => $attachment_id]);
                wp_die();
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
    }

    // initialize
    new npx_acf_plugin_image_aspect_ratio_crop();

    // class_exists check
endif;
