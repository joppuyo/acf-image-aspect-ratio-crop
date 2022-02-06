(function($) {
  // This is for the field group admin. I would have preferred to do this in PHP but I could't find an ACF hook

  const checkElementHasValue = (rootElement, element) => {
    let targetElement = rootElement
      .parents('.acf-field-object-image-aspect-ratio-crop')
      .first()
      .find(element);

    return targetElement.val() ? true : false;
  };

  const makeElementReadOnly = (rootElement, element) => {
    let targetElement = rootElement
      .parents('.acf-field-object-image-aspect-ratio-crop')
      .first()
      .find(element);
    targetElement.prop('readonly', true);
  };

  const makeElementEditable = (rootElement, element) => {
    let targetElement = rootElement
      .parents('.acf-field-object-image-aspect-ratio-crop')
      .first()
      .find(element);
    targetElement.prop('readonly', false);
  };

  const clearElement = (rootElement, element) => {
    let targetElement = rootElement
      .parents('.acf-field-object-image-aspect-ratio-crop')
      .first()
      .find(element);
    targetElement.val('');
  };

  const makeElementRequired = (rootElement, element) => {
    let targetElement = rootElement
      .parents('.acf-field-object-image-aspect-ratio-crop')
      .first()
      .find(element);
    targetElement.prop('required', true);
  };

  const makeElementNotRequired = (rootElement, element) => {
    let targetElement = rootElement
      .parents('.acf-field-object-image-aspect-ratio-crop')
      .first()
      .find(element);
    targetElement.prop('required', false);
  };

  const copyElementValue = (rootElement, element1, element2) => {
    let targetElement1 = rootElement
      .parents('.acf-field-object-image-aspect-ratio-crop')
      .first()
      .find(element1);

    let targetElement2 = rootElement
      .parents('.acf-field-object-image-aspect-ratio-crop')
      .first()
      .find(element2);

    if (targetElement1.val()) {
      targetElement2.val(targetElement1.val());
      targetElement2.attr('value', targetElement1.val());
    }
  };

  const getElementValue = (rootElement, element1) => {
    let targetElement1 = rootElement
      .parents('.acf-field-object-image-aspect-ratio-crop')
      .first()
      .find(element1);
    return targetElement1.val();
  };

  const setElementValue = (rootElement, element1, value) => {
    let targetElement = rootElement
      .parents('.acf-field-object-image-aspect-ratio-crop')
      .first()
      .find(element1);

    targetElement.val(value);
    targetElement.attr('value', value);
  };

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

    if (type === 'pixel_size') {
      clearElement($element, '.js-max-width');
      makeElementReadOnly($element, '.js-max-width');

      clearElement($element, '.js-max-height');
      makeElementReadOnly($element, '.js-max-height');

      clearElement($element, '.js-min-width');
      copyElementValue($element, '.js-aspect-ratio-width', '.js-min-width');
      makeElementReadOnly($element, '.js-min-width');

      clearElement($element, '.js-min-height');
      copyElementValue($element, '.js-aspect-ratio-height', '.js-min-height');
      makeElementReadOnly($element, '.js-min-height');
    }
    if (type === 'aspect_ratio' || type === 'free_crop') {
      if (actionType !== 'ready') {
        clearElement($element, '.js-min-width');
      }
      makeElementEditable($element, '.js-min-width');
      if (actionType !== 'ready') {
        clearElement($element, '.js-min-height');
      }
      makeElementEditable($element, '.js-min-height');
      if (actionType !== 'ready') {
        clearElement($element, '.js-max-width');
      }
      makeElementEditable($element, '.js-max-width');
      if (actionType !== 'ready') {
        clearElement($element, '.js-max-height');
      }
      makeElementEditable($element, '.js-max-height');
    }
    if (type === 'free_crop') {
      makeElementNotRequired($element, '.js-aspect-ratio-width');
      makeElementNotRequired($element, '.js-aspect-ratio-height');

      clearElement($element, '.js-max-width');
      makeElementReadOnly($element, '.js-max-width');

      clearElement($element, '.js-max-height');
      makeElementReadOnly($element, '.js-max-height');

      clearElement($element, '.js-min-width');
      makeElementReadOnly($element, '.js-min-width');

      clearElement($element, '.js-min-height');
      makeElementReadOnly($element, '.js-min-height');
    }
    if (type === 'aspect_ratio' || type === 'pixel_size') {
      makeElementRequired($element, '.js-aspect-ratio-width');
      makeElementRequired($element, '.js-aspect-ratio-height');
    }

    if (type === 'aspect_ratio') {
      if (
        checkElementHasValue($element, '.js-aspect-ratio-width') &&
        checkElementHasValue($element, '.js-aspect-ratio-height')
      ) {
        makeElementEditable($element, '.js-max-width');
        makeElementEditable($element, '.js-max-height');
        makeElementEditable($element, '.js-min-width');
        makeElementEditable($element, '.js-min-height');
      } else {
        makeElementReadOnly($element, '.js-max-width');
        makeElementReadOnly($element, '.js-max-height');
        makeElementReadOnly($element, '.js-min-width');
        makeElementReadOnly($element, '.js-min-height');
      }
    }
  }

  const setHeight = (element, sourceElement, targetElement) => {
    let $element = $(element);

    let aspectRatioWidth = getElementValue($element, '.js-aspect-ratio-width');
    let aspectRatioHeight = getElementValue(
      $element,
      '.js-aspect-ratio-height',
    );
    let minWidth = getElementValue($element, sourceElement);

    if (aspectRatioHeight && aspectRatioWidth && minWidth) {
      let minHeight = Math.round(
        (aspectRatioHeight / aspectRatioWidth) * minWidth,
      );
      setElementValue($element, targetElement, minHeight);
    } else {
      setElementValue($element, targetElement, '');
    }
  };

  const setWidth = (element, sourceElement, targetElement) => {
    let $element = $(element);

    let aspectRatioWidth = getElementValue($element, '.js-aspect-ratio-width');
    let aspectRatioHeight = getElementValue(
      $element,
      '.js-aspect-ratio-height',
    );
    let minHeight = getElementValue($element, sourceElement);

    if (aspectRatioHeight && aspectRatioWidth && minHeight) {
      let minWidth = Math.round(
        (aspectRatioWidth / aspectRatioHeight) * minHeight,
      );
      setElementValue($element, targetElement, minWidth);
    } else {
      setElementValue($element, targetElement, '');
    }
  };

  // When min height is changed
  $(document).on(
    'input change',
    '.acf-field-object-image-aspect-ratio-crop .js-min-width',
    function(event) {
      setHeight(this, '.js-min-width', '.js-min-height');
    },
  );

  // When min width is changed
  $(document).on(
    'input change',
    '.acf-field-object-image-aspect-ratio-crop .js-min-height',
    function(event) {
      setWidth(this, '.js-min-height', '.js-min-width');
    },
  );

  // When max height is changed
  $(document).on(
    'input change',
    '.acf-field-object-image-aspect-ratio-crop .js-max-width',
    function(event) {
      setHeight(this, '.js-max-width', '.js-max-height');
    },
  );

  // When max width is changed
  $(document).on(
    'input change',
    '.acf-field-object-image-aspect-ratio-crop .js-max-height',
    function(event) {
      setWidth(this, '.js-max-height', '.js-max-width');
    },
  );
})(jQuery);
