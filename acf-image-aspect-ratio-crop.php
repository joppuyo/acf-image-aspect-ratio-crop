<?php

/*
Plugin Name: Advanced Custom Fields: Image Aspect Ratio Crop
Plugin URI: https://github.com/joppuyo/acf-image-aspect-ratio-crop
Description: ACF field that allows user to crop image to a specific aspect ratio
Version: 1.0.4
Author: Johannes Siipola
Author URI: https://siipo.la
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


// check if class already exists
if (!class_exists('npx_acf_plugin_image_aspect_ratio_crop')) :

    class npx_acf_plugin_image_aspect_ratio_crop
    {

        // vars
        var $settings;


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
                'version' => '1.0.0',
                'url' => plugin_dir_url(__FILE__),
                'path' => plugin_dir_path(__FILE__)
            );


            // set text domain
            // https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
            load_plugin_textdomain('acf-image-aspect-ratio-crop', false, plugin_basename(dirname(__FILE__)) . '/lang');


            // include field
            add_action('acf/include_field_types', array($this, 'include_field_types')); // v5

            add_action('wp_ajax_acf_image_aspect_ratio_crop_crop', function () {

                // WTF WordPress
                $post = array_map('stripslashes_deep', $_POST);

                $data = json_decode($post['data'], true);

                $imageData = wp_get_attachment_metadata($data['id']);

                $mediaDir = wp_upload_dir();

                // WP Smush compat: use original image if it exists
                $file = $mediaDir['basedir'] . '/' . $imageData['file'];
                $parts = explode('.', $file);
                $extension = array_pop($parts);
                $backupFile = implode('.', $parts) . '.bak.' . $extension;

                if (file_exists($backupFile)) {
                    $image = wp_get_image_editor($backupFile);
                } else {
                    $image = wp_get_image_editor($file);
                }

                if (is_wp_error($image)) {
                    wp_send_json('Failed to open image', 500);
                    wp_die();
                }

                $image->crop($data['x'], $data['y'], $data['width'], $data['height']);

                // Retrieve original filename and seperate it from its file extension
                $originalFileName = explode('.', basename($imageData['file']));

                // Retrieve and remove file extension from array
                $originalFileExtension = array_pop($originalFileName);

                // Generate new base filename
                $targetFileName = implode('.',
                        $originalFileName) . '-aspect-ratio-' . $data['aspectRatioWidth'] . 'x' . $data['aspectRatioHeight'] . '.' . $originalFileExtension;

                // Generate target path new file using existing media library
                $targetFilePath = $mediaDir['path'] . '/' . wp_unique_filename($mediaDir['path'], $targetFileName);

                // Get the relative path to save as the actual image url
                $targetRelativePath = str_replace($mediaDir['basedir'] . '/', '', $targetFilePath);

                //$save = $image->save('test.jpg');
                $save = $image->save($targetFilePath);
                if (is_wp_error($save)) {
                    wp_send_json('Failed to crop', 500);
                    wp_die();
                }

                $wp_filetype = wp_check_filetype($targetRelativePath, null);

                $attachment = [
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', $targetFileName),
                    'post_content' => '',
                    'post_status' => 'publish'
                ];

                $attachmentId = wp_insert_attachment($attachment, $targetRelativePath);

                if (is_wp_error($attachmentId)) {
                    wp_send_json('Failed to save attachment', 500);
                    wp_die();
                }

                require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                $attachment_data = wp_generate_attachment_metadata($attachmentId, $targetFilePath);
                wp_update_attachment_metadata($attachmentId, $attachment_data);
                add_post_meta($attachmentId, 'acf_image_aspect_ratio_crop', true, true);
                add_post_meta($attachmentId, 'acf_image_aspect_ratio_crop_original_image_id', $data['id'], true);

                wp_send_json(['id' => $attachmentId]);
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
                        ]
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
            include_once('fields/class-npx-acf-field-image-aspect-ratio-crop-v5.php');
        }

    }


// initialize
    new npx_acf_plugin_image_aspect_ratio_crop();


// class_exists check
endif;
