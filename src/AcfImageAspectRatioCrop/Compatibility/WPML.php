<?php

namespace Joppuyo\AcfImageAspectRatioCrop\Compatibility;

class WPML
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
        // Old WPML 4.2.9 compat
        add_action(
            'wpml_media_create_duplicate_attachment',
            [$this, 'wpml_copy_fields_old'],
            25,
            2
        );

        // New 4.3.19, 4.4.3  WPML compat
        add_action(
            'wpml_after_update_attachment_texts',
            [$this, 'wpml_copy_fields_new'],
            25,
            2
        );

        add_filter(
            'wpml_duplicate_generic_string',
            [$this, 'translate_post_meta_wpml'],
            10,
            3
        );
    }

    public function wpml_copy_fields_old(
        $attachment_id,
        $duplicate_attachment_id
    ) {
        $this->wpml_copy_fields($attachment_id, $duplicate_attachment_id);
    }

    public function wpml_copy_fields_new($attachment_id, $duplicate_attachment)
    {
        $duplicate_attachment_id = $duplicate_attachment->element_id;
        $this->wpml_copy_fields($attachment_id, $duplicate_attachment_id);
    }

    public function wpml_copy_fields($attachment_id, $duplicate_attachment_id)
    {
        $keys = [
            'acf_image_aspect_ratio_crop',
            'acf_image_aspect_ratio_crop_original_image_id',
            'acf_image_aspect_ratio_crop_coordinates',
        ];
        foreach ($keys as $key) {
            $value = get_post_meta($attachment_id, $key, true);
            if ($value) {
                update_post_meta($duplicate_attachment_id, $key, $value);
            }
        }
    }

    public function translate_post_meta_wpml($value, $lang, $meta_data)
    {
        if ($meta_data['context'] !== 'custom_field') {
            return $value;
        }

        $key = $meta_data['key'];
        $to = $meta_data['post_id'];
        $from = $meta_data['master_post_id'];

        // When creating translated copy of any post if there is a translated version of the
        // cropped image, use it
        if (get_post_type($from) !== 'attachment') {
            $original_field = get_field_object($key, $from);

            if (
                $value &&
                $original_field &&
                $original_field['type'] &&
                $original_field['type'] === 'image_aspect_ratio_crop'
            ) {
                $translated_value = apply_filters(
                    'wpml_object_id',
                    $value,
                    'attachment',
                    false,
                    $lang
                );

                if ($translated_value) {
                    return $translated_value;
                }
            }
        }

        return $value;
    }
}
