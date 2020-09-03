(function($) {
  // This is for the field group admin. I would have preferred to do this in PHP but I could't find an ACF hook

  // On page ready
  $(document).ready(() => {
    $('.acf-field-object-image-aspect-ratio-crop .crop-type-select').each(
      function() {
        toggleCropType(this, 'ready');
      },
    );
  });

  // When field is added / changed
  acf.add_action('append', function() {
    $('.acf-field-object-image-aspect-ratio-crop .crop-type-select').each(
      function() {
        toggleCropType(this, 'append');
      },
    );
  });

  // When crop type is changed
  $(document).on(
    'change',
    '.acf-field-object-image-aspect-ratio-crop .crop-type-select',
    function(event) {
      toggleCropType(this, 'change');
    },
  );

  // When height is changed
  $(document).on(
    'input change',
    '.acf-field-object-image-aspect-ratio-crop .js-aspect-ratio-height',
    function(event) {
      toggleCropType(
        $(this)
          .parents('.acf-field-object-image-aspect-ratio-crop')
          .first()
          .find('.crop-type-select'),
      );
    },
  );

  // When width is changed
  $(document).on(
    'input change',
    '.acf-field-object-image-aspect-ratio-crop .js-aspect-ratio-width',
    function(event) {
      toggleCropType(
        $(this)
          .parents('.acf-field-object-image-aspect-ratio-crop')
          .first()
          .find('.crop-type-select'),
      );
    },
  );

  function toggleCropType(element, actionType) {
    let $element = $(element);
    let type = $element.val();

    let widthElement = $element
      .parents('.acf-field-object-image-aspect-ratio-crop')
      .first()
      .find('.js-aspect-ratio-width');

    let heightElement = $element
      .parents('.acf-field-object-image-aspect-ratio-crop')
      .first()
      .find('.js-aspect-ratio-height');

    if (type === 'pixel_size') {
      let minWidthElement = $element
        .parents('.acf-field-object-image-aspect-ratio-crop')
        .first()
        .find('.js-min-width');
      minWidthElement.val('');

      widthElement.prop('required', true);
      if (widthElement.val()) {
        minWidthElement.val(widthElement.val());
        minWidthElement.attr('value', widthElement.val());
      }

      minWidthElement.prop('readonly', true);

      let minHeightElement = $element
        .parents('.acf-field-object-image-aspect-ratio-crop')
        .first()
        .find('.js-min-height');
      minHeightElement.val('');

      heightElement.prop('required', true);
      if (heightElement.val()) {
        minHeightElement.val(heightElement.val());
        minHeightElement.attr('value', heightElement.val());
      }

      minHeightElement.prop('readonly', true);
    }
    if (type !== 'pixel_size') {
      let minWidthElement = $element
        .parents('.acf-field-object-image-aspect-ratio-crop')
        .first()
        .find('.js-min-width');
      if (actionType !== 'ready') {
        minWidthElement.val('');
      }
      minWidthElement.prop('readonly', false);

      let minHeightElement = $element
        .parents('.acf-field-object-image-aspect-ratio-crop')
        .first()
        .find('.js-min-height');
      if (actionType !== 'ready') {
        minHeightElement.val('');
      }
      minHeightElement.prop('readonly', false);
    }
    if (type === 'free_crop') {
      widthElement.prop('required', false);
      heightElement.prop('required', false);
    }
    if (type !== 'free_crop') {
      widthElement.prop('required', true);
      heightElement.prop('required', true);
    }
  }
})(jQuery);
