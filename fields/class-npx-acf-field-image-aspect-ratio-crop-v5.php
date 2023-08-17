<?php

/*
 * Based on includes/fields/class-acf-field-image.php from
 * https://github.com/AdvancedCustomFields/acf by elliotcondon, licensed
 * under GPLv2 or later
 */

// exit if accessed directly
if (!defined('ABSPATH')) {
    exit();
}

class npx_acf_field_image_aspect_ratio_crop extends acf_field
{
    /** @var string */
    public $temp_post_id;

    /*
     *  __construct
     *
     *  This function will setup the field type data
     *
     *  @type    function
     *  @date    5/03/2014
     *  @since    5.0.0
     *
     *  @param    n/a
     *  @return    n/a
     */

    function __construct($settings)
    {
        /*
         *  name (string) Single word, no spaces. Underscores allowed
         */

        $this->name = 'image_aspect_ratio_crop';

        /*
         *  label (string) Multiple words, can include spaces, visible when selecting a field type
         */

        $this->label = __(
            'Image Aspect Ratio Crop',
            'acf-image-aspect-ratio-crop'
        );

        /*
         *  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
         */

        $this->category = 'content';

        /*
         *  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
         */

        $this->defaults = [
            'return_format' => 'array',
            'preview_size' => 'medium',
            'library' => 'all',
            'min_width' => 0,
            'min_height' => 0,
            'min_size' => 0,
            'max_width' => 0,
            'max_height' => 0,
            'max_size' => 0,
            'mime_types' => '',
            'crop_type' => 'aspect_ratio',
        ];

        $this->l10n = [
            'select' => __('Select Image', 'acf'),
            'edit' => __('Edit Image', 'acf'),
            'update' => __('Update Image', 'acf'),
            'uploadedTo' => __('Uploaded to this post', 'acf'),
            'all' => __('All images', 'acf'),
        ];

        // We need to generate temporary id for the post because we don't have id when creating new post
        // Also options pages, taxonomies etc have ACF generated special post id that we don't know before save hook
        $this->temp_post_id = wp_generate_uuid4();

        // Store temporary post id in a hidden field
        add_action(
            'acf/input/form_data',
            function () {
                echo "<input type='hidden' name='aiarc_temp_post_id' value='$this->temp_post_id'>";
            },
            10,
            1
        );

        // filters
        add_filter('get_media_item_args', [$this, 'get_media_item_args']);
        add_filter(
            'wp_prepare_attachment_for_js',
            [$this, 'wp_prepare_attachment_for_js'],
            10,
            3
        );

        /*
         *  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
         *  var message = acf._e('image_aspect_ratio_crop', 'error');
         */

        //$this->l10n = array(
        //    'error'    => __('Error! Please enter a higher value', 'acf-image-aspect-ratio-crop'),
        //);

        /*
         *  settings (array) Store plugin settings (url, path, version) as a reference for later use with assets
         */

        $this->settings = $settings;

        // do not delete!
        parent::__construct();
    }

    /*
     *  render_field_settings()
     *
     *  Create extra settings for your field. These are visible when editing a field
     *
     *  @type    action
     *  @since    3.6
     *  @date    23/01/13
     *
     *  @param    $field (array) the $field being edited
     *  @return    n/a
     */

    function render_field_settings($field)
    {
        /*
         *  acf_render_field_setting
         *
         *  This function will create a setting for your field. Simply pass the $field parameter and an array of field settings.
         *  The array of settings does not require a `value` or `prefix`; These settings are found from the $field array.
         *
         *  More than one setting can be added by copy/paste the above code.
         *  Please note that you must also have a matching $defaults value for the field name (font_size)
         */

        // clear numeric settings
        $clear = [
            'min_width',
            'min_height',
            'min_size',
            'max_width',
            'max_height',
            'max_size',
        ];

        foreach ($clear as $k) {
            if (empty($field[$k])) {
                $field[$k] = '';
            }
        }

        // return_format
        acf_render_field_setting($field, [
            'label' => __('Crop type', 'acf-image-aspect-ratio-crop'),
            'type' => 'select',
            'name' => 'crop_type',
            'class' => 'crop-type-select',
            'choices' => [
                'aspect_ratio' => __(
                    'Aspect ratio',
                    'acf-image-aspect-ratio-crop'
                ),
                'pixel_size' => __('Pixel size', 'acf-image-aspect-ratio-crop'),
                'free_crop' => __('Free crop', 'acf-image-aspect-ratio-crop'),
            ],
        ]);

        acf_render_field_setting($field, [
            'label' => __('Width', 'acf-image-aspect-ratio-crop'),
            'type' => 'number',
            'required' => true,
            'class' => 'js-aspect-ratio-width',
            'name' => 'aspect_ratio_width',
            'conditional_logic' => [
                'field' => 'crop_type',
                'operator' => '!=',
                'value' => 'free_crop',
            ],
        ]);

        acf_render_field_setting($field, [
            'label' => __('Height', 'acf-image-aspect-ratio-crop'),
            'type' => 'number',
            'required' => true,
            'class' => 'js-aspect-ratio-height',
            'name' => 'aspect_ratio_height',
            'conditional_logic' => [
                'field' => 'crop_type',
                'operator' => '!=',
                'value' => 'free_crop',
            ],
        ]);

        // return_format
        acf_render_field_setting($field, [
            'label' => __('Return Value', 'acf'),
            'instructions' => __(
                'Specify the returned value on front end',
                'acf'
            ),
            'type' => 'radio',
            'name' => 'return_format',
            'layout' => 'horizontal',
            'choices' => [
                'array' => __('Image Array', 'acf'),
                'url' => __('Image URL', 'acf'),
                'id' => __('Image ID', 'acf'),
            ],
        ]);

        // preview_size
        acf_render_field_setting($field, [
            'label' => __('Preview Size', 'acf'),
            'instructions' => __('Shown when entering data', 'acf'),
            'type' => 'select',
            'name' => 'preview_size',
            'choices' => acf_get_image_sizes(),
        ]);

        // library
        acf_render_field_setting($field, [
            'label' => __('Library', 'acf'),
            'instructions' => __('Limit the media library choice', 'acf'),
            'type' => 'radio',
            'name' => 'library',
            'layout' => 'horizontal',
            'choices' => [
                'all' => __('All', 'acf'),
                'uploadedTo' => __('Uploaded to post', 'acf'),
            ],
        ]);

        // min
        acf_render_field_setting($field, [
            'label' => __('Minimum', 'acf'),
            'instructions' => __(
                'Restrict which images can be uploaded',
                'acf'
            ),
            'type' => 'text',
            'name' => 'min_width',
            'class' => 'js-min-width',
            'prepend' => __('Width', 'acf'),
            'append' => 'px',
        ]);

        acf_render_field_setting($field, [
            'label' => '',
            'type' => 'text',
            'name' => 'min_height',
            'class' => 'js-min-height',
            'prepend' => __('Height', 'acf'),
            'append' => 'px',
            '_append' => 'min_width',
        ]);

        acf_render_field_setting($field, [
            'label' => '',
            'type' => 'text',
            'name' => 'min_size',
            'prepend' => __('File size', 'acf'),
            'append' => 'MB',
            '_append' => 'min_width',
        ]);

        // max
        acf_render_field_setting($field, [
            'label' => __('Maximum', 'acf'),
            'instructions' => __(
                'Restrict which images can be uploaded',
                'acf'
            ),
            'type' => 'text',
            'name' => 'max_width',
            'prepend' => __('Width', 'acf'),
            'append' => 'px',
            'class' => 'js-max-width',
        ]);

        acf_render_field_setting($field, [
            'label' => '',
            'type' => 'text',
            'name' => 'max_height',
            'prepend' => __('Height', 'acf'),
            'append' => 'px',
            '_append' => 'max_width',
            'class' => 'js-max-height',
        ]);

        acf_render_field_setting($field, [
            'label' => '',
            'type' => 'text',
            'name' => 'max_size',
            'prepend' => __('File size', 'acf'),
            'append' => 'MB',
            '_append' => 'max_width',
        ]);

        // allowed type
        acf_render_field_setting($field, [
            'label' => __('Allowed file types', 'acf'),
            'instructions' => __(
                'Comma separated list. Leave blank for all types',
                'acf'
            ),
            'type' => 'text',
            'name' => 'mime_types',
        ]);
    }

    /*
     *  render_field()
     *
     *  Create the HTML interface for your field
     *
     *  @param    $field (array) the $field being rendered
     *
     *  @type    action
     *  @since    3.6
     *  @date    23/01/13
     *
     *  @param    $field (array) the $field being edited
     *  @return    n/a
     */

    function render_field($field)
    {
        /*
         *  Review the data of $field.
         *  This will show what data is available
         */

        //echo '<pre>';
        //    print_r( $field );
        //echo '</pre>';

        //echo '<h1>test</h1>';

        // vars
        $uploader = acf_get_setting('uploader');

        // enqueue
        if ($uploader == 'wp') {
            acf_enqueue_uploader();
        }

        // vars
        $url = '';
        $alt = '';
        // set aspect width and height to zero for free cropping
        $div = [
            'class' => 'acf-image-uploader-aspect-ratio-crop',
            'data-preview_size' => $field['preview_size'],
            'data-key' => $field['key'],
            'data-library' => $field['library'],
            'data-mime_types' => $field['mime_types'],
            'data-uploader' => $uploader,
            'data-crop_type' => $field['crop_type'],
            'data-aspect_ratio_width' => array_key_exists(
                'aspect_ratio_width',
                $field
            )
                ? $field['aspect_ratio_width']
                : 0,
            'data-aspect_ratio_height' => array_key_exists(
                'aspect_ratio_height',
                $field
            )
                ? $field['aspect_ratio_height']
                : 0,
            'data-min_width' => array_key_exists('min_width', $field)
                ? $field['min_width']
                : 0,
            'data-min_height' => array_key_exists('min_height', $field)
                ? $field['min_height']
                : 0,
        ];

        $image_id = null;
        $original = null;

        // has value?
        if ($field['value']) {
            if (is_numeric($field['value'])) {
                $image_id = $field['value'];
                $original = get_post_meta(
                    $image_id,
                    'acf_image_aspect_ratio_crop_original_image_id',
                    true
                );
            } else {
                // For migration compatibility with acf-image-crop plugin.
                // Retrieves the image from that plugin which it has saved inside JSON encoded value.
                // Thanks to https://github.com/carlblock
                $backwards_compatible_json = json_decode($field['value']);
                if (
                    $backwards_compatible_json !== null &&
                    isset($backwards_compatible_json->original_image) &&
                    isset($backwards_compatible_json->cropped_image)
                ) {
                    $image_id = $backwards_compatible_json->cropped_image;
                    $original = $backwards_compatible_json->original_image;
                }
                $preserved_original = get_post_meta(
                    $image_id,
                    'acf_image_aspect_ratio_crop_original_image_id',
                    true
                );
                if (!$preserved_original) {
                    // Because JSON is changed to id on save, we need to preserve the original image id in new format
                    update_post_meta(
                        $image_id,
                        'acf_image_aspect_ratio_crop_original_image_id',
                        $original
                    );
                }
            }

            // update vars
            $url = wp_get_attachment_image_src(
                $image_id,
                $field['preview_size']
            );
            $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);

            $coordinates = get_post_meta(
                $image_id,
                'acf_image_aspect_ratio_crop_coordinates',
                true
            );

            $div['data-coordinates'] = $coordinates;

            // url exists
            if ($url) {
                $url = $url[0] . '?v=' . md5(get_the_date($image_id));
            }

            if ($original) {
                $div['data-original-image-id'] = $original;
            } else {
                // Normal image field compat
                $div['data-original-image-id'] = $image_id;
            }

            // url exists
            if ($url) {
                $div['class'] .= ' has-value';
            }
        }

        // get size of preview value
        $size = acf_get_image_size($field['preview_size']);
        ?>
        <div <?php acf_esc_attr_e($div); ?>>
            <?php acf_hidden_input([
                'name' => $field['name'],
                'value' => $image_id,
            ]); ?>
            <div class="show-if-value image-wrap"
                 <?php if ($size['width']): ?>style="<?php echo esc_attr(
    'max-width: ' . $size['width'] . 'px'
); ?>"<?php endif; ?>>
                <img data-name="image" src="<?php echo esc_url(
                    $url
                ); ?>" alt="<?php echo esc_attr($alt); ?>"/>
                <div class="acf-actions -hover">
                    <a class="acf-icon -crop dark" data-name="crop" href="#"
                       title="<?php _e('Crop', 'acf'); ?>"></a>
                    <?php if ($uploader != 'basic'): ?>
                    <a class="acf-icon -pencil dark" data-name="edit" href="#"
                       title="<?php _e(
                           'Edit',
                           'acf'
                       ); ?>"></a><?php endif; ?><a class="acf-icon -cancel-custom dark" data-name="remove" href="#"
                         title="<?php _e('Remove', 'acf'); ?>"></a>
                </div>
            </div>
            <div class="hide-if-value">
                <?php if ($uploader == 'basic'): ?>

                <!-- basic uploader start -->

                <?php $mime_array = npx_acf_plugin_image_aspect_ratio_crop::extension_list_to_mime_array(
                    $field['mime_types']
                ); ?>

                <div class="js-aiarc-upload-progress" style="display: none"></div>

                <input type="file" class="aiarc-upload js-aiarc-upload" data-id="<?php echo $field[
                    'name'
                ]; ?>" accept="<?php echo implode(',', $mime_array); ?>">

                    <?php if ($image_id && !is_numeric($image_id)): ?>
                        <div class="acf-error-message"><p><?php echo acf_esc_html(
                            $image_id
                        ); ?></p></div>
                    <?php endif; ?>

                    <!-- basic uploader end -->

                <?php else: ?>

                    <!-- advanced uploader start -->

                    <p><?php _e(
                        'No image selected',
                        'acf'
                    ); ?> <a data-name="add" class="acf-button button"
                                                                   href="#"><?php _e(
                                                                       'Add Image',
                                                                       'acf'
                                                                   ); ?></a></p>

                    <!-- advanced uploader end -->

                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /*
     *  input_admin_enqueue_scripts()
     *
     *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
     *  Use this action to add CSS + JavaScript to assist your render_field() action.
     *
     *  @type    action (admin_enqueue_scripts)
     *  @since    3.6
     *  @date    23/01/13
     *
     *  @param    n/a
     *  @return    n/a
     */

    function input_admin_enqueue_scripts()
    {
        global $post;
        $url = $this->settings['url'];
        $version = $this->settings['version'];

        wp_register_script(
            'acf-image-aspect-ratio-crop',
            "{$url}assets/dist/input-script.js",
            ['acf-input', 'backbone'],
            WP_DEBUG
                ? md5_file(
                    $this->settings['path'] . '/assets/dist/input-script.js'
                )
                : $version
        );
        $translation_array = [
            'cropping_in_progress' => __(
                'Cropping image...',
                'acf-image-aspect-ratio-crop'
            ),
            'cropping_failed' => __(
                'Failed to crop image',
                'acf-image-aspect-ratio-crop'
            ),
            'crop' => __('Crop', 'acf-image-aspect-ratio-crop'),
            'cancel' => __('Cancel', 'acf-image-aspect-ratio-crop'),
            'modal_title' => __('Crop image', 'acf-image-aspect-ratio-crop'),
            'reset' => __('Reset crop', 'acf-image-aspect-ratio-crop'),
            'upload_progress' => __(
                'Uploading image. Progress %d%%.',
                'acf-image-aspect-ratio-crop'
            ),
            'upload_failed' => __(
                'Upload failed.',
                'acf-image-aspect-ratio-crop'
            ),
        ];
        $settings_array = [
            'modal_type' => $this->settings['user_settings']['modal_type'],
            'rest_api_compat' =>
                $this->settings['user_settings']['rest_api_compat'],
        ];

        $data_array = [
            'temp_post_id' => $this->temp_post_id,
            'nonce' => wp_create_nonce('aiarc'),
            // This thing is required because WordPress is weird and not having this makes
            // verify_nonce always return false when the API is called on the admin side
            // https://stackoverflow.com/questions/41878315/wp-ajax-nonce-works-when-logged-out-but-not-when-logged-in
            'wp_rest_nonce' => wp_create_nonce('wp_rest'),
            'api_root' => untrailingslashit(get_rest_url()),
        ];
        wp_localize_script(
            'acf-image-aspect-ratio-crop',
            'aiarc_settings',
            $settings_array
        );
        wp_localize_script(
            'acf-image-aspect-ratio-crop',
            'aiarc_translations',
            $translation_array
        );
        wp_localize_script('acf-image-aspect-ratio-crop', 'aiarc', $data_array);

        wp_enqueue_script('acf-image-aspect-ratio-crop');
        wp_register_style(
            'acf-image-aspect-ratio-crop',
            "{$url}assets/dist/input-style.css",
            ['acf-input'],
            WP_DEBUG
                ? md5_file(
                    $this->settings['path'] . '/assets/dist/input-style.css'
                )
                : $version
        );
        wp_enqueue_style('acf-image-aspect-ratio-crop');
    }

    /*

    function input_admin_enqueue_scripts() {

        // vars
        $url = $this->settings['url'];
        $version = $this->settings['version'];


        // register & include JS
        wp_register_script('acf-image-aspect-ratio-crop', "{$url}assets/js/input.js", array('acf-input'), $version);
        wp_enqueue_script('acf-image-aspect-ratio-crop');


        // register & include CSS
        wp_register_style('acf-image-aspect-ratio-crop', "{$url}assets/css/input.css", array('acf-input'), $version);
        wp_enqueue_style('acf-image-aspect-ratio-crop');

    }

    */

    /*
     *  input_admin_head()
     *
     *  This action is called in the admin_head action on the edit screen where your field is created.
     *  Use this action to add CSS and JavaScript to assist your render_field() action.
     *
     *  @type    action (admin_head)
     *  @since    3.6
     *  @date    23/01/13
     *
     *  @param    n/a
     *  @return    n/a
     */

    /*

    function input_admin_head() {
    }

    */

    /*
     *  input_form_data()
     *
     *  This function is called once on the 'input' page between the head and footer
     *  There are 2 situations where ACF did not load during the 'acf/input_admin_enqueue_scripts' and
     *  'acf/input_admin_head' actions because ACF did not know it was going to be used. These situations are
     *  seen on comments / user edit forms on the front end. This function will always be called, and includes
     *  $args that related to the current screen such as $args['post_id']
     *
     *  @type    function
     *  @date    6/03/2014
     *  @since    5.0.0
     *
     *  @param    $args (array)
     *  @return    n/a
     */

    /*

    function input_form_data( $args ) {
    }

    */

    /*
     *  input_admin_footer()
     *
     *  This action is called in the admin_footer action on the edit screen where your field is created.
     *  Use this action to add CSS and JavaScript to assist your render_field() action.
     *
     *  @type    action (admin_footer)
     *  @since    3.6
     *  @date    23/01/13
     *
     *  @param    n/a
     *  @return    n/a
     */

    /*

    function input_admin_footer() {
    }

    */

    /*
     *  field_group_admin_enqueue_scripts()
     *
     *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is edited.
     *  Use this action to add CSS + JavaScript to assist your render_field_options() action.
     *
     *  @type    action (admin_enqueue_scripts)
     *  @since    3.6
     *  @date    23/01/13
     *
     *  @param    n/a
     *  @return    n/a
     */

    /*

    function field_group_admin_enqueue_scripts() {
    }

    */

    /*
     *  field_group_admin_head()
     *
     *  This action is called in the admin_head action on the edit screen where your field is edited.
     *  Use this action to add CSS and JavaScript to assist your render_field_options() action.
     *
     *  @type    action (admin_head)
     *  @since    3.6
     *  @date    23/01/13
     *
     *  @param    n/a
     *  @return    n/a
     */

    /*

    function field_group_admin_head() {
    }

    */

    /*
     *  load_value()
     *
     *  This filter is applied to the $value after it is loaded from the db
     *
     *  @type    filter
     *  @since    3.6
     *  @date    23/01/13
     *
     *  @param    $value (mixed) the value found in the database
     *  @param    $post_id (mixed) the $post_id from which the value was loaded
     *  @param    $field (array) the field array holding all the field options
     *  @return    $value
     */

    /*

    function load_value( $value, $post_id, $field ) {
        return $value;
    }

    */

    /*
     *  update_value()
     *
     *  This filter is applied to the $value before it is saved in the db
     *
     *  @type    filter
     *  @since    3.6
     *  @date    23/01/13
     *
     *  @param    $value (mixed) the value found in the database
     *  @param    $post_id (mixed) the $post_id from which the value was loaded
     *  @param    $field (array) the field array holding all the field options
     *  @return    $value
     */

    /*

    function update_value( $value, $post_id, $field ) {
        return $value;
    }

    */

    function update_value($value, $post_id, $field)
    {
        return acf_get_field_type('file')->update_value(
            $value,
            $post_id,
            $field
        );
    }

    /*
     *  format_value()
     *
     *  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
     *
     *  @type    filter
     *  @since    3.6
     *  @date    23/01/13
     *
     *  @param    $value (mixed) the value which was loaded from the database
     *  @param    $post_id (mixed) the $post_id from which the value was loaded
     *  @param    $field (array) the field array holding all the field options
     *
     *  @return    $value (mixed) the modified value
     */

    /*

    function format_value( $value, $post_id, $field ) {

        // bail early if no value
        if( empty($value) ) {

            return $value;

        }


        // apply setting
        if( $field['font_size'] > 12 ) {

            // format the value
            // $value = 'something';

        }


        // return
        return $value;
    }

    */

    /*
     *  format_value()
     *
     *  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
     *
     *  @type    filter
     *  @since    3.6
     *  @date    23/01/13
     *
     *  @param    $value (mixed) the value which was loaded from the database
     *  @param    $post_id (mixed) the $post_id from which the value was loaded
     *  @param    $field (array) the field array holding all the field options
     *
     *  @return    $value (mixed) the modified value
     */

    function format_value($value, $post_id, $field)
    {
        // bail early if no value
        if (empty($value)) {
            return false;
        }

        $image_id = null;

        // For migration compatibility with acf-image-crop plugin.
        // Retrieves the image from that plugin which it has saved inside JSON encoded value.
        if (is_numeric($value)) {
            $image_id = $value;
        } elseif (
            json_decode($value) !== false &&
            !empty(json_decode($value)->cropped_image)
        ) {
            $image_id = json_decode($value)->cropped_image;
        }

        // bail early if not numeric (error message)
        if (!is_numeric($image_id)) {
            return false;
        }

        // convert to int
        $image_id = intval($image_id);

        // format
        if ($field['return_format'] == 'url') {
            return wp_get_attachment_url($image_id);
        } elseif ($field['return_format'] == 'array') {
            $output = acf_get_attachment($image_id);
            if ($output) {
                $output['original_image'] = null;
                // TODO: use singular
                $original = get_post_meta(
                    $image_id,
                    'acf_image_aspect_ratio_crop_original_image_id'
                );
                if (count($original)) {
                    $output['original_image'] = acf_get_attachment(
                        $original[0]
                    );
                }
            }

            return $output;
        }

        // return
        return $image_id;
    }

    /*
     *  validate_value()
     *
     *  This filter is used to perform validation on the value prior to saving.
     *  All values are validated regardless of the field's required setting. This allows you to validate and return
     *  messages to the user if the value is not correct
     *
     *  @type    filter
     *  @date    11/02/2014
     *  @since    5.0.0
     *
     *  @param    $valid (boolean) validation status based on the value and the field's required setting
     *  @param    $value (mixed) the $_POST value
     *  @param    $field (array) the field array holding all the field options
     *  @param    $input (string) the corresponding input name for $_POST value
     *  @return    $valid
     */

    /*

    function validate_value( $valid, $value, $field, $input ){

        // Basic usage
        if( $value < $field['custom_minimum_setting'] )
        {
            $valid = false;
        }


        // Advanced usage
        if( $value < $field['custom_minimum_setting'] )
        {
            $valid = __('The value is too little!','acf-image-aspect-ratio-crop'),
        }


        // return
        return $valid;

    }

    */

    /*
     *  delete_value()
     *
     *  This action is fired after a value has been deleted from the db.
     *  Please note that saving a blank value is treated as an update, not a delete
     *
     *  @type    action
     *  @date    6/03/2014
     *  @since    5.0.0
     *
     *  @param    $post_id (mixed) the $post_id from which the value was deleted
     *  @param    $key (string) the $meta_key which the value was deleted
     *  @return    n/a
     */

    /*

    function delete_value( $post_id, $key ) {



    }

    */

    /*
     *  load_field()
     *
     *  This filter is applied to the $field after it is loaded from the database
     *
     *  @type    filter
     *  @date    23/01/2013
     *  @since    3.6.0
     *
     *  @param    $field (array) the field array holding all the field options
     *  @return    $field
     */

    /*

    function load_field( $field ) {

        return $field;

    }

    */

    /*
     *  update_field()
     *
     *  This filter is applied to the $field before it is saved to the database
     *
     *  @type    filter
     *  @date    23/01/2013
     *  @since    3.6.0
     *
     *  @param    $field (array) the field array holding all the field options
     *  @return    $field
     */

    /*

    function update_field( $field ) {

        return $field;

    }

    */

    /*
     *  delete_field()
     *
     *  This action is fired after a field is deleted from the database
     *
     *  @type    action
     *  @date    11/02/2014
     *  @since    5.0.0
     *
     *  @param    $field (array) the field array holding all the field options
     *  @return    n/a
     */

    /*

    function delete_field( $field ) {



    }

    */

    /*
     *  get_media_item_args
     *
     *  description
     *
     *  @type    function
     *  @date    27/01/13
     *  @since    3.6.0
     *
     *  @param    $vars (array)
     *  @return    $vars
     */

    function get_media_item_args($vars)
    {
        $vars['send'] = true;
        return $vars;
    }

    /*
     *  wp_prepare_attachment_for_js
     *
     *  this filter allows ACF to add in extra data to an attachment JS object
     *  This sneaky hook adds the missing sizes to each attachment in the 3.5 uploader.
     *  It would be a lot easier to add all the sizes to the 'image_size_names_choose' filter but
     *  then it will show up on the normal the_content editor
     *
     *  @type    function
     *  @since:    3.5.7
     *  @date    13/01/13
     *
     *  @param    {int}    $post_id
     *  @return    {int}    $post_id
     */

    function wp_prepare_attachment_for_js($response, $attachment, $meta)
    {
        // only for image
        if ($response['type'] != 'image') {
            return $response;
        }

        // make sure sizes exist. Perhaps they dont?
        if (!isset($meta['sizes'])) {
            return $response;
        }

        $attachment_url = $response['url'];
        $base_url = str_replace(
            wp_basename($attachment_url),
            '',
            $attachment_url
        );

        if (isset($meta['sizes']) && is_array($meta['sizes'])) {
            foreach ($meta['sizes'] as $k => $v) {
                if (!isset($response['sizes'][$k])) {
                    $response['sizes'][$k] = [
                        'height' => $v['height'],
                        'width' => $v['width'],
                        'url' => $base_url . $v['file'],
                        'orientation' =>
                            $v['height'] > $v['width']
                                ? 'portrait'
                                : 'landscape',
                    ];
                }
            }
        }

        return $response;
    }
}

// initialize
new npx_acf_field_image_aspect_ratio_crop($this->settings);
?>
