<?php

namespace Joppuyo\AcfImageAspectRatioCrop\Compatibility;

class Polylang
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
        add_filter(
            'pll_translate_post_meta',
            [$this, 'translate_post_meta_polylang'],
            10,
            5
        );
    }

    public function translate_post_meta_polylang(
        $value,
        $key,
        $lang,
        $from,
        $to
    ) {
        // When creating translated duplicated attachment if there is a translated version of
        // the original image, use it
        if (get_post_type($from) === 'attachment') {
            if ($key === 'acf_image_aspect_ratio_crop_original_image_id') {
                return pll_get_post($value, $lang)
                    ? pll_get_post($value, $lang)
                    : $value;
            }
        }

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
                $translated_value = pll_get_post($value, $lang);
                if ($translated_value) {
                    return $translated_value;
                }
            }
        }

        return $value;
    }
}
