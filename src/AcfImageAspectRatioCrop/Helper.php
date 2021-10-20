<?php

namespace Joppuyo\AcfImageAspectRatioCrop;

class Helper
{
    /**
     * @param $mime_types
     * @return array
     */
    public static function extension_list_to_mime_array($mime_types)
    {
        $extension_array = explode(',', $mime_types);
        $extension_array = array_map(function ($extension) {
            return trim($extension);
        }, $extension_array);

        $allowed_mime_types = [];

        foreach ($extension_array as $extension) {
            if ($extension === 'jpeg' || $extension === 'jpg') {
                array_push($allowed_mime_types, 'image/jpeg');
            }
            if ($extension === 'png') {
                array_push($allowed_mime_types, 'image/png');
            }
            if ($extension === 'gif') {
                array_push($allowed_mime_types, 'image/gif');
            }
        }

        $allowed_mime_types = array_unique($allowed_mime_types);
        return $allowed_mime_types;
    }
}
