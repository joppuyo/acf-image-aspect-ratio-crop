<?php

namespace Joppuyo\AcfImageAspectRatioCrop;

class Admin
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
        add_action('admin_menu', [$this, 'admin_menu']);

        $plugin_main_file = realpath(
            __DIR__ . '/../../acf-image-aspect-ratio-crop.php'
        );

        // Add settings link on the plugin page
        add_filter(
            'plugin_action_links_' . plugin_basename($plugin_main_file),
            [$this, 'plugin_action_links']
        );

        // Donate link
        add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);

        // Hide cropped images in media library grid view
        add_filter('ajax_query_attachments_args', [
            $this,
            'ajax_query_attachments_args',
        ]);
    }

    public function ajax_query_attachments_args($args)
    {
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
    }

    public function plugin_action_links($links)
    {
        $settings_link =
            '<a href="options-general.php?page=acf-image-aspect-ratio-crop">' .
            __('Settings', 'acf-image-aspect-ratio-crop') .
            '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function plugin_row_meta($links, $file)
    {
        $plugin_main_file = realpath(
            __DIR__ . '/../../acf-image-aspect-ratio-crop.php'
        );

        if ($file === plugin_basename($plugin_main_file)) {
            array_push(
                $links,
                '<a href="https://github.com/sponsors/joppuyo">' .
                    esc_html__(
                        'Support development on GitHub Sponsors',
                        'acf-image-aspect-ratio-crop'
                    ) .
                    '</a>'
            );
        }
        return $links;
    }

    public function admin_menu()
    {
        add_submenu_page(
            null,
            __('ACF Image Aspect Ratio Crop', 'acf-image-aspect-ratio-crop'),
            __('ACF Image Aspect Ratio Crop', 'acf-image-aspect-ratio-crop'),
            'manage_options',
            'acf-image-aspect-ratio-crop',
            [$this, 'settings_page']
        );
    }

    public function settings_page()
    {
        $updated = false;

        $aiarc = Plugin::get_instance();

        $settings = $aiarc->user_settings;

        if (!empty($_POST)) {
            check_admin_referer('acf-image-aspect-ratio-crop');

            if (!empty($_POST['modal_type'])) {
                $settings['modal_type'] = $_POST['modal_type'];
            }

            update_option('acf-image-aspect-ratio-crop-settings', $settings);
            $updated = true;
        }
        $modal_type = $settings['modal_type'];

        echo '<div class="wrap">';
        echo '<h1>' .
            __('ACF Image Aspect Ratio Crop', 'acf-image-aspect-ratio-crop') .
            '</h1>';
        echo '<div class="aiarc-admin-notices"></div>';
        if ($updated) {
            echo '<div class="notice notice-success">';
            echo '<p>' .
                __('Options have been updated', 'acf-image-aspect-ratio-crop') .
                '</p>';
            echo '</div>';
        }
        echo '<form method="post">';
        echo '<table class="form-table">';
        echo '<tbody>';
        echo '<tr>';
        echo '<th scope="row">';
        echo '<label for="modal_type">' .
            __(
                'Image displayed in attachment edit modal dialog',
                'acf-image-aspect-ratio-crop'
            ) .
            '</label>';
        echo '</th>';
        echo '<td>';
        echo '<p><input type="radio" id="cropped" name="modal_type" value="cropped" ' .
            checked($modal_type, 'cropped', false) .
            '><label for="cropped"> ' .
            __('Cropped image', 'acf-image-aspect-ratio-crop') .
            '</label></p>';
        echo '<p><input type="radio" id="original" name="modal_type" value="original" ' .
            checked($modal_type, 'original', false) .
            '><label for="original"> ' .
            __('Original image', 'acf-image-aspect-ratio-crop') .
            '</label></p>';
        echo '</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
        echo '<p class="submit">';
        echo '<input class="button-primary js-finnish-base-forms-submit-button" type="submit" name="submit-button" value="Save">';
        echo '</p>';
        wp_nonce_field('acf-image-aspect-ratio-crop');
        echo '</form>';
        echo '</div>';
    }
}
