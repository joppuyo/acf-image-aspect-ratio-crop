<?php

namespace Joppuyo\AcfImageAspectRatioCrop;

use acf_field;

class Field extends acf_field
{
    protected static $instance = null;

    public static function get_instance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

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

    function __construct()
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

        $this->settings = Plugin::get_instance()->settings;

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

        // v7 start
        $aspect_ratio_width = array_key_exists(
            'aspect_ratio_width',
            $field
        )
            ? $field['aspect_ratio_width']
            : 0;

        $aspect_ratio_height = array_key_exists(
            'aspect_ratio_height',
            $field
        )
            ? $field['aspect_ratio_height']
            : 0;
        $min_width = !empty($field['min_width']) ? $field['min_width'] : null;
        $min_height = !empty($field['min_height']) ? $field['min_height'] : null;
        // v7 end

        $image_id = null;
        $original = null;

        $field = apply_filters('aiarc/pre_render_field', $field);

        // has value?
        if ($field['value']) {
            $image_id = $field['value'];
            $original = get_post_meta(
                $image_id,
                'acf_image_aspect_ratio_crop_original_image_id',
                true
            );

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

            <?php
            $v7_i18n = [
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
                'modal_title' => __(
                    'Crop image',
                    'acf-image-aspect-ratio-crop'
                ),
                'reset' => __('Reset crop', 'acf-image-aspect-ratio-crop'),
                'upload_progress' => __(
                    'Uploading image. Progress %d%%.',
                    'acf-image-aspect-ratio-crop'
                ),
                'upload_failed' => __(
                    'Upload failed.',
                    'acf-image-aspect-ratio-crop'
                ),
                'get_data_error' => __(
                    'Failed to get image data.',
                    'acf-image-aspect-ratio-crop'
                ),
                'edit_image' => __('Edit Image', 'acf-image-aspect-ratio-crop'),
                'update_image' => __(
                    'Update Image',
                    'acf-image-aspect-ratio-crop'
                ),
            ];

            $original_image_data = null;

            $original_image_id = get_post_meta(
                $image_id,
                'acf_image_aspect_ratio_crop_original_image_id',
                true
            );

            $crop_coordinates = get_post_meta(
                $image_id,
                'acf_image_aspect_ratio_crop_coordinates',
                true
            ) ?: null;

            if ($original_image_id) {
                $original_image_data = wp_prepare_attachment_for_js($original_image_id) ?? null;
            }

            $v7_context = [
                'initial_image_data' =>
                    wp_prepare_attachment_for_js($image_id) ?? null,
                'initial_original_image_data' => $original_image_data,
                'initial_crop_coordinates' => $crop_coordinates,
                'preview_size' => acf_get_image_size($field['preview_size']),
                'wp_rest_nonce' => wp_create_nonce('wp_rest'),
                'api_root' => untrailingslashit(get_rest_url()),
                'i18n' => $v7_i18n,
                'aspect_ratio_width' => $aspect_ratio_width,
                'crop_type' => $field['crop_type'],
                'aspect_ratio_height' => $aspect_ratio_height,
                'min_width' => $min_width,
                'min_height' => $min_height,
            ];
            $v7_context = htmlspecialchars(json_encode($v7_context));
            ?>

            <div class="js-aiarc-field-root" data-context="<?php echo $v7_context; ?>">
                <aiarc-cropper></aiarc-cropper>
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

        // v7 stuff

        wp_register_script(
            'aiarc-script-v7',
            "{$url}assets-v7/dist/main.js",
            ['acf-input', 'backbone'],
            WP_DEBUG
                ? md5_file($this->settings['path'] . '/assets-v7/dist/main.js')
                : $version
        );

        add_filter(
            'script_loader_tag',
            function ($tag, $handle, $src) {
                if ('aiarc-script-v7' !== $handle) {
                    return $tag;
                }
                $tag =
                    '<script type="module" src="' .
                    esc_url($src) .
                    '"></script>';
                return $tag;
            },
            10,
            3
        );

        wp_enqueue_script('aiarc-script-v7');

        wp_register_style(
            'aiarc-style-v7',
            "{$url}assets-v7/dist/main.css",
            ['acf-input'],
            WP_DEBUG
                ? md5_file($this->settings['path'] . '/assets-v7/dist/main.css')
                : $version
        );
        wp_enqueue_style('aiarc-style-v7');
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

        $image_id = apply_filters('aiarc/pre_format_value', $value);

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

                $original = get_post_meta(
                    $image_id,
                    'acf_image_aspect_ratio_crop_original_image_id',
                    true
                );

                if ($original) {
                    $output['original_image'] = acf_get_attachment($original);
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