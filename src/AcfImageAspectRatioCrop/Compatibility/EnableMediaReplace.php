<?php

namespace Joppuyo\AcfImageAspectRatioCrop\Compatibility;

class EnableMediaReplace
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
        // Enable Media Replace compat: if file is replaced using Enable Media Replace, wipe the coordinate data
        add_filter('wp_handle_upload', [$this, 'wp_handle_upload']);
    }

    public function wp_handle_upload($data)
    {
        $id = attachment_url_to_postid($data['url']);
        if ($id !== 0) {
            $posts = get_posts([
                'post_type' => 'attachment',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' =>
                            'acf_image_aspect_ratio_crop_original_image_id',
                        'value' => $id,
                        'compare' => '=',
                    ],
                    [
                        'key' => 'acf_image_aspect_ratio_crop_coordinates',
                        'compare' => 'EXISTS',
                    ],
                ],
            ]);
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    delete_post_meta(
                        $post->ID,
                        'acf_image_aspect_ratio_crop_coordinates'
                    );
                }
            }
        }
        return $data;
    }
}
