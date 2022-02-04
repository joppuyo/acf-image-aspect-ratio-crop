<?php

namespace Joppuyo\AcfImageAspectRatioCrop\Compatibility;

class ACFImageCropAddon
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
        add_filter('aiarc/pre_format_value', [
            $this,
            'get_image_id_from_old_format',
            9,
        ]);
        add_filter('aiarc/pre_render_field', [
            $this,
            'modify_field_value_and_preserve_old_original_image_id',
            9,
        ]);
    }

    public function get_image_id_from_old_format($value)
    {
        // If we have value in the plugin format, let's use that
        if (is_numeric($value)) {
            return $value;
        }

        /*
         * ACF Image Crop Addon stores value as JSON in the following format:
         * {
         *   "original_image": "123",
         *   "cropped_image": 321
         * }
         * So, let's try to decode the value as JSON and if we have the cropped_image property,
         * use that
         */

        if (
            json_decode($value) !== false &&
            !empty(json_decode($value)->cropped_image)
        ) {
            return json_decode($value)->cropped_image;
        }

        // If nothing else works, return false
        return false;
    }

    public function modify_field_value_and_preserve_old_original_image_id(
        $field
    ) {
        // For migration compatibility with acf-image-crop plugin.
        // Retrieves the image from that plugin which it has saved inside JSON encoded value.
        // Thanks to https://github.com/carlblock
        $backwards_compatible_json = json_decode($field['value']);

        // Change field value from JSON to post id integer
        if (
            $backwards_compatible_json !== null &&
            isset($backwards_compatible_json->cropped_image)
        ) {
            $field['value'] = $backwards_compatible_json->cropped_image;
        }

        // Preserve original image information
        if (
            $backwards_compatible_json !== null &&
            isset($backwards_compatible_json->original_image)
        ) {
            $original = $backwards_compatible_json->original_image;
            $preserved_original = get_post_meta(
                $field['value'],
                'acf_image_aspect_ratio_crop_original_image_id',
                true
            );
            if (!$preserved_original) {
                // Because JSON is changed to id on save, we need to preserve the original image id in new format
                update_post_meta(
                    $field['value'],
                    'acf_image_aspect_ratio_crop_original_image_id',
                    (int) $original
                );
            }
        }

        return $field;
    }
}
