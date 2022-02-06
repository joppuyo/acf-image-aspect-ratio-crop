<?php

namespace Joppuyo\AcfImageAspectRatioCrop;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class RestApi
{
    protected static $instance = null;

    public static function get_instance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'rest_api_init']);
    }

    public function rest_api_init()
    {
        register_rest_route('aiarc/v1', '/upload', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_api_upload_callback'],
            'permission_callback' => function () {
                return true;
            },
        ]);
        register_rest_route('aiarc/v1', '/crop', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_api_crop_callback'],
            'permission_callback' => function () {
                return true;
            },
        ]);
        register_rest_route('aiarc/v1', '/get/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_api_get_callback'],
            'args' => ['id' => []],
            'permission_callback' => function () {
                return true;
            },
        ]);
    }

    public function rest_api_crop_callback(WP_REST_Request $data)
    {
        $this->rest_api_check_nonce($data);
        $parameters = $data->get_json_params();

        $aiarc = Plugin::get_instance();

        $attachment_id = $aiarc->create_crop($parameters);
        return [
            'id' => $attachment_id,
        ];
    }

    public function rest_api_upload_callback(WP_REST_Request $data)
    {
        $this->rest_api_check_nonce($data);

        if (empty($data->get_file_params()['image'])) {
            return new WP_Error(
                'image_field_missing',
                __('Image field missing.', 'acf-image-aspect-ratio-crop')
            );
        }

        if (empty($data->get_param('key'))) {
            return new WP_Error(
                'key_field_missing',
                __('Key field missing.', 'acf-image-aspect-ratio-crop')
            );
        }

        $key = $data->get_param('key');

        $field_object = get_field_object($key);
        $mime_types = $field_object['mime_types'];
        $min_size = $field_object['min_size'];
        $max_size = $field_object['max_size'];

        $min_width = $field_object['min_width'];
        $max_width = $field_object['max_width'];

        $min_height = $field_object['min_height'];
        $max_height = $field_object['max_height'];

        $crop_type = $field_object['crop_type'];

        // MIME validation

        $file_mime = mime_content_type(
            $data->get_file_params()['image']['tmp_name']
        );

        $allowed_mime_types = Helper::extension_list_to_mime_array($mime_types);

        if (
            !empty($allowed_mime_types) &&
            !in_array($file_mime, $allowed_mime_types)
        ) {
            return new WP_Error(
                'invalid_mime_type',
                __('Invalid file type.', 'acf-image-aspect-ratio-crop')
            );
        }

        // File size validation

        if (
            !empty($max_size) &&
            $data->get_file_params()['image']['size'] > $max_size * 1000000
        ) {
            return new WP_Error(
                'file_too_large',
                sprintf(
                    __(
                        'File size too large. Maximum file size is %d megabytes.',
                        'acf-image-aspect-ratio-crop'
                    ),
                    $max_size
                ),
                'acf-image-aspect-ratio-crop'
            );
        }

        if (
            !empty($min_size) &&
            $data->get_file_params()['image']['size'] < $min_size * 1000000
        ) {
            return new WP_Error(
                'file_too_small',
                sprintf(
                    __(
                        'File size too small. Minimum file size is %d megabytes.',
                        'acf-image-aspect-ratio-crop'
                    ),
                    $min_size
                ),
                'acf-image-aspect-ratio-crop'
            );
        }

        // Image size validation

        $image_size = @getimagesize(
            $data->get_file_params()['image']['tmp_name']
        );

        if (!$image_size) {
            return new WP_Error(
                'failed_to_parse_image',
                __('Failed to parse image.', 'acf-image-aspect-ratio-crop')
            );
        }

        $image_width = $image_size[0];
        $image_height = $image_size[1];

        if (
            !empty($min_width) &&
            !empty($min_height) &&
            ($image_width < $min_width || $image_height < $min_height)
        ) {
            return new WP_Error(
                'image_too_small',
                sprintf(
                    __(
                        'Image too small. Minimum image dimensions are %d×%d pixels.',
                        'acf-image-aspect-ratio-crop'
                    ),
                    $min_width,
                    $min_height
                ),
                'acf-image-aspect-ratio-crop'
            );
        }

        $upload = wp_upload_bits(
            $data->get_file_params()['image']['name'],
            null,
            file_get_contents($data->get_file_params()['image']['tmp_name'])
        );
        $wp_filetype = wp_check_filetype(basename($upload['file']), null);
        $wp_upload_dir = wp_upload_dir();

        $attachment = [
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => preg_replace(
                '/\.[^.]+$/',
                '',
                basename($upload['file'])
            ),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_id = wp_insert_attachment($attachment, $upload['file']);
        $attachment_data = wp_generate_attachment_metadata(
            $attachment_id,
            $upload['file']
        );
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        return new WP_REST_Response(['attachment_id' => $attachment_id]);
    }

    public function rest_api_get_callback(WP_REST_Request $data)
    {
        // TODO: validate nonce
        $attachment_id = $data->get_param('id');

        $attachment = get_post($attachment_id);

        if (!$attachment) {
            wp_send_json_error(
                new WP_Error(
                    'attachment_not_found',
                    __('Attachment not found', 'acf-image-aspect-ratio-crop')
                ),
                404
            );
        }

        $attachment = wp_prepare_attachment_for_js($attachment);

        if (empty($attachment['width']) || empty($attachment['height'])) {
            wp_send_json_error(
                new WP_Error(
                    'malformed_image',
                    __(
                        'Malformed image. Height or width data missing.',
                        'acf-image-aspect-ratio-crop'
                    )
                ),
                500
            );
        }

        return new WP_REST_Response($attachment);
    }

    /**
     * @param WP_REST_Request $data
     */
    public function rest_api_check_nonce(WP_REST_Request $data)
    {
        //$nonce = $data->get_header('X-Aiarc-Nonce');

        /*if (empty($nonce)) {
            wp_send_json_error(
                new WP_Error(
                    'nonce_missing',
                    __('Nonce missing.', 'acf-image-aspect-ratio-crop')
                ),
                400
            );
        }*/

        /*if (!wp_verify_nonce($nonce, 'aiarc')) {
            wp_send_json_error(
                new WP_Error(
                    'invalid_nonce',
                    __('Invalid nonce.', 'acf-image-aspect-ratio-crop')
                ),
                400
            );
        }*/
    }
}
