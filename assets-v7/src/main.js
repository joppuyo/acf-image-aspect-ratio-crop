import { createApp } from 'vue/dist/vue.esm-bundler';
import App from './App.vue';
import mitt from 'mitt';

(function($) {
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

  function initialize_field($field) {
    const rootElement = $field[0].querySelector('.js-aiarc-field-root');

    let context = JSON.parse(rootElement.dataset.context);

    context.field_name = $field[0].querySelector('input').name;
    context.key = $field[0].dataset.key;

    console.log($field[0].querySelector('input').name);

    //console.log(context);

    //console.log(App.styles) // ["/* inlined css */"]

    // convert into custom element constructor
    //const CropperElement = defineCustomElement(App)

    // register
    //customElements.define('aiarc-cropper', CropperElement)

    const app = createApp(
      {
        components: {
          'aiarc-cropper': App,
        },
      },
      {
        context,
      },
    );

    const emitter = mitt();
    app.config.globalProperties.emitter = emitter;

    app.mount(rootElement);

    // Add event lister

    let acfFieldInstance = window.acf.getField($field);

    // Event comes from React

    document.addEventListener('aiarc-update-field-value', function({ detail }) {
      console.log(detail);
      console.log(detail.id, context.field_name);

      if (detail.id === context.field_name) {
        console.log('!!!');

        acfFieldInstance.val(detail.value);
      }
    });
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
