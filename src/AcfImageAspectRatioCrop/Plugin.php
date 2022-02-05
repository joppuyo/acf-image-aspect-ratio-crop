<?php

namespace Joppuyo\AcfImageAspectRatioCrop;

use DateTime;
use Exception;
use Joppuyo\AcfImageAspectRatioCrop\Compatibility\ACFImageCropAddon;
use Joppuyo\AcfImageAspectRatioCrop\Compatibility\EnableMediaReplace;
use Joppuyo\AcfImageAspectRatioCrop\Compatibility\Polylang;
use Joppuyo\AcfImageAspectRatioCrop\Compatibility\WpGraphQl;
use Joppuyo\AcfImageAspectRatioCrop\Compatibility\WPML;
use WP_Image_Editor;

class Plugin
{
    public $settings;
    public $user_settings;
    public $temp_path;

    protected static $instance = null;

    public static function get_instance(): Plugin
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function __construct()
    {
        add_action('init', [$this, 'init']);
        add_action('plugins_loaded', [$this, 'initialize_settings']);
        add_action('acf/include_field_types', [$this, 'include_field_types']); // v5

        add_filter(
            'acf/upload_prefilter/type=image_aspect_ratio_crop',
            [$this, 'acf_upload_prefilter'],
            10,
            3
        );

        add_filter(
            'acf/validate_attachment/type=image_aspect_ratio_crop',
            [$this, 'acf_upload_prefilter'],
            10,
            3
        );

        RestApi::get_instance();
        EnableMediaReplace::get_instance();
        Polylang::get_instance();
        WpGraphQl::get_instance();
        WPML::get_instance();
        Admin::get_instance();
        ACFImageCropAddon::get_instance();
    }

    public function init()
    {
    }

    public function initialize_settings()
    {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_main_file = realpath(
            __DIR__ . '/../../acf-image-aspect-ratio-crop.php'
        );

        $this->settings = [
            'version' => get_plugin_data($plugin_main_file, false, false)[
                'Version'
            ],
            'url' => plugin_dir_url($plugin_main_file),
            'path' => plugin_dir_path($plugin_main_file),
        ];
        $this->temp_path = null;

        $database_version = get_option('acf-image-aspect-ratio-crop-version');
        $plugin_version = $this->settings['version'];
        $settings = get_option('acf-image-aspect-ratio-crop-settings')
            ? get_option('acf-image-aspect-ratio-crop-settings')
            : [];

        // Initialize database settings
        if (empty($database_version)) {
            update_option(
                'acf-image-aspect-ratio-crop-version',
                $plugin_version
            );
        }

        if (
            version_compare(
                get_option('acf-image-aspect-ratio-crop-version'),
                $plugin_version,
                'lt'
            )
        ) {
            // Database migrations here
            update_option(
                'acf-image-aspect-ratio-crop-version',
                $plugin_version
            );
        }

        $default_user_settings = [
            'modal_type' => 'cropped',
        ];

        $this->user_settings = array_merge($default_user_settings, $settings);
        $this->settings['user_settings'] = $this->user_settings;
    }

    public function include_field_types()
    {
        $acf_version = get_option('acf_version');
        if (!$acf_version) {
            return;
        }
        if (version_compare($acf_version, '5.9.0', 'lt')) {
            add_action('admin_notices', [$this, 'acf_update_notice']);
            return;
        }
        Field::get_instance();
    }

    public function acf_update_notice()
    {
        $class = 'notice notice-warning';
        $message =
            __('ACF Image Aspect Ratio Crop', 'acf-image-aspect-ratio-crop') .
            ': ' .
            sprintf(
                __(
                    'ACF version requirements are not met. This plugin requires at least ACF %s. The field has been disabled.',
                    'acf-image-aspect-ratio-crop'
                ),
                '5.9.0'
            );

        printf(
            '<div class="%1$s"><p>%2$s</p></div>',
            esc_attr($class),
            esc_html($message)
        );
    }

    public function acf_upload_prefilter($errors, $file, $field)
    {
        // Suppress error about maximum height and width
        if (!empty($errors['max_width'])) {
            unset($errors['max_width']);
        }
        if (!empty($errors['max_height'])) {
            unset($errors['max_height']);
        }
        return $errors;
    }

    /**
     * @param $data
     * @return array
     */
    public function create_crop($data)
    {
        $image_data = apply_filters(
            'aiarc_image_data',
            wp_get_attachment_metadata($data['id']),
            $data['id']
        );

        if ($image_data === false) {
            $error_text =
                'Failed to get image data. Maybe the original image was deleted?';
            $this->log_error($error_text);
            wp_send_json($error_text, 500);
        }

        // If the difference between the images is less than half a percentage, use the original image
        // prettier-ignore
        if ($image_data['height'] - $data['height'] < $image_data['height'] * 0.005 &&
            $image_data['width'] - $data['width'] < $image_data['width'] * 0.005 &&
            $data['cropType'] !== 'pixel_size'
        ) {
            wp_send_json(['id' => $data['id']]);
            wp_die();
        }

        do_action('aiarc_pre_customize_upload_dir');

        $media_dir = apply_filters(
            'aiarc_upload_dir',
            wp_upload_dir(),
            $data['id']
        );

        do_action('aiarc_after_customize_upload_dir');

        $file = $media_dir['basedir'] . '/' . $image_data['file'];

        add_filter('jpeg_quality', [$this, 'jpeg_quality']);

        $image = null;
        $scaled_data = null;
        if (
            file_exists($file) &&
            function_exists('wp_get_original_image_path') &&
            wp_get_original_image_path($data['id']) &&
            wp_get_original_image_path($data['id']) !== $file &&
            file_exists(wp_get_original_image_path($data['id']))
        ) {
            // Handle the new asinine feature in WP 5.3 which resizes images without asking the user. We want the
            // original image so we do "original_image -> crop" instead of "original_image -> resized_image -> crop"
            $resized_image = wp_get_image_editor($file);
            $image = wp_get_image_editor(
                wp_get_original_image_path($data['id'])
            );

            if (is_wp_error($image)) {
                throw new \Exception($image->get_error_message());
            }

            if (is_wp_error($resized_image)) {
                throw new \Exception($image->get_error_message());
            }

            // Handle case with EXIF rotation where image size exceeds big_image_size_threshold
            // so the scaled image is rotated but original is not. Rotate original before
            // calculating co-ordinates and performing crop.
            // https://wordpress.org/support/topic/srgb-image-turned-into-1x1-white-image/
            if (method_exists($image, 'maybe_exif_rotate')) {
                $image->maybe_exif_rotate();
            }
            $resized_width = $resized_image->get_size()['width'];
            $original_width = $image->get_size()['width'];

            // Get the scale
            $scale = $original_width / $resized_width;

            // Clone data array
            $scaled_data = $data;

            // Scale crop coordinates to fit larger image
            $scaled_data['x'] = floor($data['x'] * $scale);
            $scaled_data['y'] = floor($data['y'] * $scale);
            $scaled_data['width'] = floor($data['width'] * $scale);
            $scaled_data['height'] = floor($data['height'] * $scale);
        } elseif (file_exists($file)) {
            $image = wp_get_image_editor($file);
        } else {
            // Let's attempt to get the file by URL
            $temp_name = wp_generate_uuid4();
            $temp_directory = get_temp_dir();
            $this->temp_path = $temp_directory . $temp_name;
            try {
                $url = wp_get_attachment_url($data['id']);
                $url = apply_filters('aiarc_request_url', $url, $data['id']);

                $request_options = [
                    'stream' => true,
                    'filename' => $this->temp_path,
                    'timeout' => 25,
                ];

                $result = wp_remote_get($url, $request_options);

                if (is_wp_error($result)) {
                    throw new Exception('Failed to save image');
                }
                $image = wp_get_image_editor($this->temp_path);
            } catch (Exception $exception) {
                $this->cleanup();
                $error_text = 'Failed fetch remote image';
                $this->log_error($error_text, $exception);
                wp_send_json($error_text, 500);
                wp_die();
            }
        }

        if (is_wp_error($image)) {
            $this->cleanup();
            $error_text = 'Failed to open image';
            $this->log_error($error_text, $image);
            wp_send_json($error_text, 500);
            wp_die();
        }

        // Use scaled coordinates if we have those
        $this->crop($image, $scaled_data ? $scaled_data : $data);

        if ($data['cropType'] === 'pixel_size') {
            $image->resize(
                $data['aspectRatioWidth'],
                $data['aspectRatioHeight'],
                true
            );
        }

        $field_object = get_field_object($data['key']);

        $max_width = $field_object['max_width'];
        $max_height = $field_object['max_height'];

        if (
            $data['cropType'] === 'aspect_ratio' &&
            !empty($max_width) &&
            !empty($max_height) &&
            $data['width'] > $max_width &&
            $data['height'] > $max_height
        ) {
            $image->resize($max_width, $max_height, true);
        }

        // Retrieve original filename and seperate it from its file extension
        $original_file_name = explode('.', basename($image_data['file']));

        // Retrieve and remove file extension from array
        $original_file_extension = array_pop($original_file_name);

        $width = $data['aspectRatioWidth'];
        $height = $data['aspectRatioHeight'];

        if ($data['cropType'] === 'free_crop') {
            $width = $data['width'];
            $height = $data['height'];
        }

        // Generate new base filename
        $target_file_name =
            implode('.', $original_file_name) .
            '-aspect-ratio-' .
            $width .
            '-' .
            $height .
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

        $save = $image->save($target_file_path);
        remove_filter('jpeg_quality', [$this, 'jpeg_quality']);

        if (is_wp_error($save)) {
            $this->cleanup();
            $error_text = 'Failed to crop';
            $this->log_error($error_text, $save);
            wp_send_json($error_text, 500);
            wp_die();
        }

        $wp_filetype = wp_check_filetype($target_relative_path, null);

        $attachment = [
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', $target_file_name),
            'post_content' => '',
            'post_status' => 'publish',
        ];

        // Polylang 2.9 Compat
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = wp_insert_attachment(
            $attachment,
            $target_relative_path
        );

        if (is_wp_error($attachment_id)) {
            $this->cleanup();
            $error_text = 'Failed to save attachment';
            $this->log_error($error_text, $attachment_id);
            wp_send_json($error_text, 500);
            wp_die();
        }

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
                'height' => $data['height'],
            ],
            true
        );

        require_once ABSPATH . 'wp-admin' . '/includes/image.php';
        $attachment_data = wp_generate_attachment_metadata(
            $attachment_id,
            $target_file_path
        );
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        // WPML compat
        do_action('wpml_sync_all_custom_fields', $attachment_id);

        $this->cleanup();

        return $attachment_id;
    }

    /**
     * Clean up any temporary files
     */
    private function cleanup()
    {
        if ($this->temp_path) {
            @unlink($this->temp_path);
        }
    }

    public function jpeg_quality($jpeg_quality)
    {
        $jpeg_quality = apply_filters('aiarc_jpeg_quality', $jpeg_quality);
        $jpeg_quality = apply_filters('aiarc/jpeg_quality', $jpeg_quality);
        return $jpeg_quality;
    }

    private function crop(WP_Image_Editor $image, $data)
    {
        $image->crop($data['x'], $data['y'], $data['width'], $data['height']);
    }

    function debug($message)
    {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log(print_r($message, true));
        }
    }

    private function log_error($description, $object = false)
    {
        error_log("ACF Image Aspect Ratio Crop: $description");
        if ($object) {
            error_log(print_r($object, true));
        }
    }
}
