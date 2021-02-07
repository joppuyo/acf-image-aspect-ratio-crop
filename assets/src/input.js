/*!
 * Based on assets/js/acf-input.js from
 * https://github.com/AdvancedCustomFields/acf by elliotcondon, licensed
 * under GPLv2 or later
 */

import Cropper from 'cropperjs';
import axios from 'axios';
import qs from 'qs';
import { sprintf } from 'sprintf-js';

(function($) {
  var field = null;

  acf.fields.image_aspect_ratio_crop = acf.field.extend({
    type: 'image_aspect_ratio_crop',
    $el: null,
    $input: null,
    $img: null,

    actions: {
      ready: 'initialize',
      append: 'initialize',
    },

    events: {
      'click a[data-name="add"]': 'add',
      'click a[data-name="edit"]': 'edit',
      'click a[data-name="remove"]': 'remove',
      'change input[type="file"]': 'change',
      'click a[data-name="crop"]': 'changeCrop',
      'change .js-aiarc-upload': 'front_end_upload',
    },

    front_end_upload: function(event) {
      let uploadElement = event.currentTarget;

      var acfKey = $(this.$field)
        .find('.acf-image-uploader-aspect-ratio-crop')
        .data('key');

      let files = uploadElement.files;
      let formData = new FormData();

      this.isFirstCrop = true;

      if (!files.length) {
        return;
      }

      Array.from(Array(files.length).keys()).map(index => {
        formData.append('image', files[index], files[index].name);
        formData.append('key', acfKey);
      });

      uploadElement.value = '';

      let settings = {
        onUploadProgress: progressEvent => {
          let percentCompleted = Math.round(
            (progressEvent.loaded * 100) / progressEvent.total,
          );

          this.$el
            .find('.js-aiarc-upload-progress')
            .html(
              sprintf(
                window.aiarc_translations.upload_progress,
                percentCompleted,
              ),
            );
        },
        headers: {
          'X-Aiarc-Nonce': window.aiarc.nonce,
          'X-WP-Nonce': window.aiarc.wp_rest_nonce,
        },
      };

      $(this.$el)
        .find('.js-aiarc-upload')
        .hide();

      $(this.$el)
        .find('.js-aiarc-upload-progress')
        .show();

      axios
        .post(`${window.aiarc.api_root}/aiarc/v1/upload`, formData, settings)
        .then(response => {
          // This is just for the preview
          axios
            .get(
              `${window.aiarc.api_root}/aiarc/v1/get/${response.data.attachment_id}`,
            )
            .then(response => {
              let attachment = new window.Backbone.Model(response.data);
              this.render(attachment);
            });

          $(this.$el)
            .find('.js-aiarc-upload-progress')
            .hide();

          $(this.$el)
            .find('.js-aiarc-upload')
            .show();

          let $field = this.$field;

          // Add original id attribute to the image so we can recrop it right away without saving the post
          $field
            .find('.acf-image-uploader-aspect-ratio-crop')
            .data('original-image-id', response.data.attachment_id)
            .attr('data-original-image-id', response.data.attachment_id);

          axios
            .get(
              `${window.aiarc.api_root}/aiarc/v1/get/${response.data.attachment_id}`,
            )
            .then(response => {
              let attachment = new window.Backbone.Model(response.data);

              this.render(attachment);
              this.openModal({ attachment: attachment, field: $field });
            });
        })
        .catch(error => {
          $(this.$el)
            .find('.js-aiarc-upload-progress')
            .hide();

          $(this.$el)
            .find('.js-aiarc-upload')
            .show();

          let errorMessage = window.aiarc_translations.upload_failed;

          if (
            error.response &&
            error.response.data &&
            error.response.data.message
          ) {
            errorMessage = error.response.data.message;
          }

          window.alert(errorMessage);
        });
    },

    /*
     *  focus
     *
     *  This function will setup variables when focused on a field
     *
     *  @type	function
     *  @date	12/04/2016
     *  @since	5.3.8
     *
     *  @param	n/a
     *  @return	n/a
     */

    focus: function() {
      // vars
      this.$el = this.$field.find('.acf-image-uploader-aspect-ratio-crop');
      this.$input = this.$el.find('input[type="hidden"]');
      this.$img = this.$el.find('img');

      // options
      this.o = acf.get_data(this.$el);
    },

    /*
     *  initialize
     *
     *  This function is used to setup basic upload form attributes
     *
     *  @type	function
     *  @date	12/04/2016
     *  @since	5.3.8
     *
     *  @param	n/a
     *  @return	n/a
     */

    initialize: function() {
      this.isFirstCrop = null;
      var self = this;

      // add attribute to form
      if (this.o.uploader == 'basic') {
        this.$el.closest('form').attr('enctype', 'multipart/form-data');
      }

      this.escapeHandlerBound = this.escapeHandler.bind(this);

      $(document).on('click', '.js-acf-image-aspect-ratio-crop-cancel', () =>
        this.closeModal(),
      );

      $(document)
        .off('click', '.js-acf-image-aspect-ratio-crop-reset')
        .on('click', '.js-acf-image-aspect-ratio-crop-reset', () => {
          this.cropper.reset();
        });

      $(document)
        .off('click', '.js-acf-image-aspect-ratio-crop-crop')
        .on('click', '.js-acf-image-aspect-ratio-crop-crop', function() {
          var cropData = self.cropper.getData(true);

          $('.js-acf-image-aspect-ratio-crop-modal').css(
            'max-width',
            self.cropper.containerData.width,
          );

          var cropType = $(field)
            .find('.acf-image-uploader-aspect-ratio-crop')
            .data('crop_type');

          let acfKey = $(field)
            .find('.acf-image-uploader-aspect-ratio-crop')
            .data('key');

          var data = {
            id: $(this).data('id'),
            aspectRatioHeight: $(this).data('aspect-ratio-height'),
            aspectRatioWidth: $(this).data('aspect-ratio-width'),
            cropType: $(this).data('crop-type'),
            x: cropData.x,
            y: cropData.y,
            width: cropData.width,
            height: cropData.height,
            temp_post_id: aiarc.temp_post_id,
            key: acfKey,
          };

          $('.js-acf-image-aspect-ratio-crop-crop').prop('disabled', true);
          $('.js-acf-image-aspect-ratio-crop-reset').prop('disabled', true);

          // prettier-ignore
          var loading = '<div class="acf-image-aspect-ratio-crop-modal-loading">' +
                          '<div class="acf-image-aspect-ratio-crop-modal-loading-icon">' +
                          '<!-- Icon from https://github.com/google/material-design-icons -->' +
                          '<!-- Licensed under Apache License 2.0 -->' +
                          '<!-- Copyright (c) Google Inc. -->' +
                          '<svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg"><path d="M7 2.64V1L4.75 3.18 7 5.36V3.73A3.33 3.33 0 0 1 10.38 7c0 .55-.15 1.07-.4 1.53l.82.8c.44-.68.7-1.47.7-2.33A4.43 4.43 0 0 0 7 2.64zm0 7.63A3.33 3.33 0 0 1 3.62 7c0-.55.15-1.07.4-1.53l-.82-.8c-.44.68-.7 1.47-.7 2.33A4.43 4.43 0 0 0 7 11.36V13l2.25-2.18L7 8.64v1.63z" fill="#FFF" fill-rule="nonzero"/></svg>' +
                          '</div>' +
                          '<div class="acf-image-aspect-ratio-crop-modal-loading-text">' +
                          aiarc_translations.cropping_in_progress +
                          '</div>' +
                        '</div>';

          // prettier-ignore
          var error = '<div class="acf-image-aspect-ratio-crop-modal-error">' +
                        '<div class="acf-image-aspect-ratio-crop-modal-error-icon">' +
                        '<!-- Icon from https://github.com/google/material-design-icons -->' +
                        '<!-- Licensed under Apache License 2.0 -->' +
                        '<!-- Copyright (c) Google Inc. -->' +
                        '<svg width="22" height="22" viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg"><path d="M1 20.14h20l-10-17-10 17zm10.9-2.69h-1.8v-1.79h1.8v1.8zm0-3.58h-1.8V10.3h1.8v3.58z" fill="#F44336" fill-rule="nonzero"/></svg>' +
                        '</div>' +
                        '<div class="acf-image-aspect-ratio-crop-modal-error-text">' +
                        aiarc_translations.cropping_failed +
                        '</div>' +
                      '</div>';

          $('.js-acf-image-aspect-ratio-crop-modal-footer-status').empty();
          $('.js-acf-image-aspect-ratio-crop-modal-footer-status').html(
            loading,
          );
          self.cropper.disable();

          let options = {};

          let url = null;

          if (window.aiarc_settings.rest_api_compat === '') {
            url = `${window.aiarc.api_root}/aiarc/v1/crop`;
            options = {
              headers: {
                'X-Aiarc-Nonce': window.aiarc.nonce,
                'X-WP-Nonce': window.aiarc.wp_rest_nonce,
              },
            };
          }

          if (window.aiarc_settings.rest_api_compat === '1') {
            url = ajaxurl;
            data = qs.stringify({
              action: 'acf_image_aspect_ratio_crop_crop',
              data: JSON.stringify(data),
            });
          }

          axios
            .post(url, data, options)
            .then(response => {
              self.cropComplete(response.data);
              $('.js-acf-image-aspect-ratio-crop-crop').prop('disabled', false);
              $('.js-acf-image-aspect-ratio-crop-reset').prop(
                'disabled',
                false,
              );
              $('.js-acf-image-aspect-ratio-crop-modal-footer-status').empty();
            })
            .catch(response => {
              console.error(response);
              self.cropper.enable();
              $('.js-acf-image-aspect-ratio-crop-crop').prop('disabled', false);
              $('.js-acf-image-aspect-ratio-crop-reset').prop(
                'disabled',
                false,
              );
              $('.js-acf-image-aspect-ratio-crop-modal-footer-status').empty();
              $('.js-acf-image-aspect-ratio-crop-modal-footer-status').html(
                error,
              );
            });
        });
    },

    /*
     *  prepare
     *
     *  This function will prepare an object of attachment data
     *  selecting a library image vs embed an image via url return different data
     *  this function will keep the 2 consistent
     *
     *  @type	function
     *  @date	12/04/2016
     *  @since	5.3.8
     *
     *  @param	attachment (object)
     *  @return	data (object)
     */

    prepare: function(attachment) {
      // defaults
      attachment = attachment || {};

      // bail ealry if already valid
      if (attachment._valid) return attachment;

      // vars
      var data = {
        url: '',
        alt: '',
        title: '',
        caption: '',
        description: '',
        width: 0,
        height: 0,
      };

      // wp image
      if (attachment.id) {
        // update data
        data = attachment.attributes;

        // maybe get preview size
        //data.url = acf.maybe_get(
        //  data,
        //  'sizes.' + this.o.preview_size + '.url',
        //  data.url,
        //);
      }

      // valid
      data._valid = true;

      // return
      return data;
    },

    /*
     *  render
     *
     *  This function will render the UI
     *
     *  @type	function
     *  @date	12/04/2016
     *  @since	5.3.8
     *
     *  @param	attachment (obj)
     *  @return	n/a
     */

    render: function(data) {
      // prepare

      data = this.prepare(data);

      // update image
      this.$img.attr({
        src: data.url,
        alt: data.alt,
        title: data.title,
      });

      // vars
      var val = '';

      // WP attachment
      if (data.id) {
        val = data.id;
      }

      // update val
      acf.val(this.$input, val);

      // update class
      if (val) {
        this.$el.addClass('has-value');
      } else {
        this.$el.removeClass('has-value');
      }
    },

    /*
     *  add
     *
     *  event listener
     *
     *  @type	function
     *  @date	12/04/2016
     *  @since	5.3.8
     *
     *  @param	e (event)
     *  @return	n/a
     */

    add: function() {
      // reference
      var self = this,
        $field = this.$field;

      // get repeater
      var $repeater = acf.get_closest_field(this.$field, 'repeater');

      // popup
      var frame = acf.media.popup({
        title: acf._e('image', 'select'),
        mode: 'select',
        type: 'image',
        field: $field.data('key'),
        multiple: false,
        library: this.o.library,
        mime_types: this.o.mime_types,

        select: function(attachment, i) {
          // select / add another image field?
          if (i > 0) {
            // vars
            var key = $field.data('key'),
              $tr = $field.closest('.acf-row');

            // reset field
            $field = false;

            // find next image field
            $tr.nextAll('.acf-row:visible').each(function() {
              // get next $field
              $field = acf.get_field(key, $(this));

              // bail early if $next was not found
              if (!$field) return;

              // bail early if next file uploader has value
              if (
                $field
                  .find('.acf-image-uploader-aspect-ratio-crop.has-value')
                  .exists()
              ) {
                $field = false;
                return;
              }

              // end loop if $next is found
              return false;
            });

            // add extra row if next is not found
            if (!$field) {
              $tr = acf.fields.repeater.doFocus($repeater).add();

              // bail early if no $tr (maximum rows hit)
              if (!$tr) return false;

              // get next $field
              $field = acf.get_field(key, $tr);
            }
          }

          self.isFirstCrop = true;

          // Add original id attribute to the image so we can recrop it right away without saving the post
          $field
            .find('.acf-image-uploader-aspect-ratio-crop')
            .data('original-image-id', attachment.id)
            .attr('data-original-image-id', attachment.id);

          self.openModal({ attachment: attachment, field: $field });

          // render
          self.set('$field', $field).render(attachment);
        },
      });
    },

    changeCrop: function() {
      this.isFirstCrop = false;
      var originalImageId = $(this.$field)
        .find('.acf-image-uploader-aspect-ratio-crop')
        .data('original-image-id');

      let callback = response => {
        let attachment = new window.Backbone.Model(response.data);
        let $field = this.$field;
        this.openModal({ attachment: attachment, field: $field });
      };

      if (window.aiarc_settings.rest_api_compat === '') {
        axios
          .get(`${window.aiarc.api_root}/aiarc/v1/get/${originalImageId}`)
          .then(response => callback(response));
      }

      if (window.aiarc_settings.rest_api_compat === '1') {
        let data = qs.stringify({
          action: 'acf_image_aspect_ratio_crop_get_attachment',
          data: JSON.stringify({ attachment_id: originalImageId }),
        });
        axios.post(ajaxurl, data).then(response => callback(response));
      }
    },

    /*
     *  edit
     *
     *  event listener
     *
     *  @type	function
     *  @date	12/04/2016
     *  @since	5.3.8
     *
     *  @param	e (event)
     *  @return	n/a
     */

    edit: function() {
      // reference
      var self = this,
        $field = this.$field;

      // vars
      var val = null;
      if (
        this.$input.parent().attr('data-original-image-id') &&
        window.aiarc_settings.modal_type === 'original'
      ) {
        val = this.$input.parent().attr('data-original-image-id');
      } else {
        val = this.$input.val();
      }

      // bail early if no val
      if (!val) return;

      // popup
      var frame = acf.media.popup({
        title: acf._e('image', 'edit'),
        button: acf._e('image', 'update'),
        mode: 'edit',
        attachment: val,
      });
    },

    /*
     *  remove
     *
     *  event listener
     *
     *  @type	function
     *  @date	12/04/2016
     *  @since	5.3.8
     *
     *  @param	e (event)
     *  @return	n/a
     */

    remove: function() {
      // Remove all data attributes from the previous image
      this.$field
        .find('.acf-image-uploader-aspect-ratio-crop')
        .data('original-image-id', null)
        .attr('data-original-image-id', null)
        .data('coordinates', null)
        .attr('data-coordinates', null);

      // vars
      var attachment = {};

      // add file to field
      this.render(attachment);
    },

    /*
     *  change
     *
     *  This function will update the hidden input when selecting a basic file to add basic validation
     *
     *  @type	function
     *  @date	12/04/2016
     *  @since	5.3.8
     *
     *  @param	e (event)
     *  @return	n/a
     */

    change: function(e) {
      //acf.fields.file.get_file_info(e.$el, this.$input);
    },

    escapeHandler: function(event) {
      if (event.key === 'Escape') {
        this.closeModal();
      }
    },

    openModal: function(data) {
      var url = data.attachment.attributes.url;
      var id = data.attachment.attributes.id;
      field = data.field;

      document.addEventListener('keydown', this.escapeHandlerBound);

      var aspectRatioWidth = $(field)
        .find('.acf-image-uploader-aspect-ratio-crop')
        .data('aspect_ratio_width');
      var aspectRatioHeight = $(field)
        .find('.acf-image-uploader-aspect-ratio-crop')
        .data('aspect_ratio_height');
      var cropType = $(field)
        .find('.acf-image-uploader-aspect-ratio-crop')
        .data('crop_type');
      var minWidth = $(field)
        .find('.acf-image-uploader-aspect-ratio-crop')
        .data('min_width');
      var minHeight = $(field)
        .find('.acf-image-uploader-aspect-ratio-crop')
        .data('min_height');

      var options = {
        aspectRatio: aspectRatioWidth / aspectRatioHeight,
        viewMode: 1,
        autoCropArea: 1,
        zoomable: false,
        checkCrossOrigin: false,
        checkOrientation: false,
        responsive: true,
      };

      if (cropType === 'pixel_size') {
        options.crop = function(event) {
          let width = event.detail.width;
          let height = event.detail.height;
          if (width < aspectRatioWidth || height < aspectRatioHeight) {
            this.cropper.setData({
              width: aspectRatioWidth,
              height: aspectRatioHeight,
            });
          }
        };
      }

      if (cropType === 'aspect_ratio' && minHeight !== 0 && minWidth !== 0) {
        options.crop = function(event) {
          let width = event.detail.width;
          let height = event.detail.height;
          if (width < minWidth || height < minHeight) {
            this.cropper.setData({
              width: minWidth,
              height: minHeight,
            });
          }
        };
      }

      let coordinates = $(field)
        .find('.acf-image-uploader-aspect-ratio-crop')
        .data('coordinates');

      if (coordinates) {
        options.data = coordinates;
      }

      // prettier-ignore
      $('body').append(`
<div class="acf-image-aspect-ratio-crop-backdrop">
  <div class="acf-image-aspect-ratio-crop-modal-wrapper">
    <div
      class="acf-image-aspect-ratio-crop-modal js-acf-image-aspect-ratio-crop-modal"
    >
      <div class="acf-image-aspect-ratio-crop-modal-heading">
        <div class="acf-image-aspect-ratio-crop-modal-heading-text">
          ${aiarc_translations.modal_title}
        </div>
        <button
          class="acf-image-aspect-ratio-crop-modal-heading-close js-acf-image-aspect-ratio-crop-cancel"
          aria-label="Close"
        >
          ${require('!raw-loader!./close.svg')}
        </button>
      </div>
      <div class="acf-image-aspect-ratio-crop-modal-image-container">
        <img
          class="acf-image-aspect-ratio-crop-modal-image js-acf-image-aspect-ratio-crop-modal-image"
          src="${url}"
        />
      </div>

      <div class="acf-image-aspect-ratio-crop-modal-footer">
        <div
          class="acf-image-aspect-ratio-crop-modal-footer-status js-acf-image-aspect-ratio-crop-modal-footer-status"
        ></div>
        <div class="acf-image-aspect-ratio-crop-modal-footer-buttons">
          <button
            class="aiarc-button aiarc-button-link acf-image-aspect-ratio-crop-reset js-acf-image-aspect-ratio-crop-reset"
          >
            ${require('!raw-loader!./reset.svg')}
            ${aiarc_translations.reset}
          </button>
          <button class="aiarc-button aiarc-button-default js-acf-image-aspect-ratio-crop-cancel">
            ${aiarc_translations.cancel}
          </button>
          <button
            class="aiarc-button aiarc-button-primary js-acf-image-aspect-ratio-crop-crop"
            data-id="${id}"
            data-aspect-ratio-height="${aspectRatioHeight}"
            data-aspect-ratio-width="${aspectRatioWidth}"
            data-crop-type="${cropType}"
          >
            ${aiarc_translations.crop}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
`);

      this.cropper = new Cropper(
        $('.js-acf-image-aspect-ratio-crop-modal-image')[0],
        options,
      );

      // Test helper
      window._acf_image_aspect_ratio_cropper = this.cropper;
    },

    cropComplete: function(data) {
      // Save coordinates so they are remembered even without saving the post first
      $(field)
        .find('.acf-image-uploader-aspect-ratio-crop')
        .data('coordinates', this.cropper.getData(true))
        .attr('data-coordinates', JSON.stringify(this.cropper.getData(true)));

      // Cropping successful, change image to cropped version
      this.cropper.destroy();

      $(field)
        .find('input')
        .first()
        .val(data.id);

      let callback = response => {
        let attachment = new window.Backbone.Model(response.data);

        this.render(attachment);
        this.isFirstCrop = false;
        this.closeModal();
      };

      if (window.aiarc_settings.rest_api_compat === '') {
        axios
          .get(`${window.aiarc.api_root}/aiarc/v1/get/${data.id}`)
          .then(response => callback(response));
      }

      if (window.aiarc_settings.rest_api_compat === '1') {
        let postData = qs.stringify({
          action: 'acf_image_aspect_ratio_crop_get_attachment',
          data: JSON.stringify({ attachment_id: data.id }),
        });
        axios.post(ajaxurl, postData).then(response => callback(response));
      }
    },

    closeModal: function() {
      if (this.isFirstCrop) {
        // If it's the first time cropping an image, we don't want to
        // leave the incorrect aspect ratio image in the field
        acf.val(this.$input, '');
        this.render({});
      }
      $('.acf-image-aspect-ratio-crop-backdrop').remove();
      document.removeEventListener('keydown', this.escapeHandlerBound);
      this.cropper.destroy();
    },
  });

  /**
   *  initialize_field
   *
   *  This function will initialize the $field.
   *
   *  @date	30/11/17
   *  @since	5.6.5
   *
   *  @param	n/a
   *  @return	n/a
   */

  function initialize_field($el) {
    //$field.doStuff();
    //var $field = $el, $options = $el.find('.acf-field-image-aspect-ratio-crop');
  }

  /*
   *  ready & append (ACF5)
   *
   *  These two events are called when a field element is ready for initizliation.
   *  - ready: on page load similar to $(document).ready()
   *  - append: on new DOM elements appended via repeater field or other AJAX calls
   *
   *  @param	n/a
   *  @return	n/a
   */

  acf.add_action('ready_field/type=image_aspect_ratio_crop', initialize_field);
  acf.add_action('append_field/type=image_aspect_ratio_crop', initialize_field);
})(jQuery);
