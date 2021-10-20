<?php

namespace Joppuyo\AcfImageAspectRatioCrop\Compatibility;

class WpGraphQl
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
            'wpgraphql_acf_supported_fields',
            __NAMESPACE__ . '\\wpgraphql_acf_supported_fields'
        );
        add_filter(
            'wpgraphql_acf_register_graphql_field',
            __NAMESPACE__ . '\\wpgraphql_acf_register_graphql_field',
            10,
            4
        );
    }

    public function wpgraphql_acf_supported_fields($supported_fields)
    {
        array_push($supported_fields, 'image_aspect_ratio_crop');
        return $supported_fields;
    }

    function wpgraphql_acf_register_graphql_field(
        $field_config,
        $type_name,
        $field_name,
        $config
    ) {
        // How to add new WPGraphQL fields is super undocumented, I used this code as a base
        // https://github.com/wp-graphql/wp-graphql/issues/214#issuecomment-653141685

        $acf_field = isset($config['acf_field']) ? $config['acf_field'] : null;
        $acf_type = isset($acf_field['type']) ? $acf_field['type'] : null;

        $resolve = $field_config['resolve'];

        if ($acf_type == 'image_aspect_ratio_crop') {
            $field_config = [
                'type' => 'MediaItem',
                'resolve' => function ($root, $args, $context, $info) use (
                    $resolve
                ) {
                    $value = $resolve($root, $args, $context, $info);
                    return WPGraphQL\Data\DataSource::resolve_post_object(
                        (int) $value,
                        $context
                    );
                },
            ];
        }

        return $field_config;
    }
}
