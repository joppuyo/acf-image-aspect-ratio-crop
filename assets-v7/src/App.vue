<template>
  <div>
    <div v-if="!imageData && !loading">
      <button v-on:click="openMediaModal">Open Media Modal</button>
    </div>
    <Preview
      v-bind:imageData="imageData"
      v-if="imageData && !loading"
      v-bind:previewSize="context.preview_size"
      v-bind:i18n="i18n"
    />
    <ErrorComponent
      v-if="error"
      v-bind:errorMessage="errorMessage"
      v-bind:errorDetails="errorDetails"
    ></ErrorComponent>
    <div v-if="loading">
      <Loader v-bind:previewSize="context.preview_size" />
    </div>
    <Cropper
      v-bind:i18n="this.context.i18n"
      v-if="cropperOpen"
      v-bind:cropperOpen="cropperOpen"
      v-bind:originalImageData="originalImageData"
    />
  </div>
</template>

<script>
import Preview from './preview/Preview';
import axios from 'axios';
import Loader from './Loader';
import Cropper from './Cropper';
import ErrorComponent from './Error';

export default {
  props: ['context'],
  components: {
    Preview,
    Loader,
    Cropper,
    ErrorComponent,
  },
  data: function() {
    return {
      i18n: this.context.i18n,
      imageData: null,
      imageWidth: null,
      loading: false,
      error: false,
      errorMessage: null,
      errorDetails: null,
      cropperOpen: false,
      originalImageData: null,
    };
  },
  mounted() {
    this.emitter.on('close-cropper', () => {
      this.cropperOpen = false;
    });

    this.emitter.on('delete-image', () => {
      this.imageData = null;
      this.updateACFFieldValue(null);
    });

    // If we have initial image data from the server, use it
    if (this.context.initial_image_data) {
      this.imageData = this.context.initial_image_data;
    }
  },
  beforeDestroy() {},
  computed: {},
  methods: {
    startCrop(imageData) {
      //this.imageData = imageData;

      this.loading = true;

      const options = {
        headers: {
          'X-WP-Nonce': this.context.wp_rest_nonce,
        },
      };

      axios
        .get(`${this.context.api_root}/aiarc/v1/get/${imageData.id}`, options)
        .then(response => {
          this.loading = false;
          this.error = false;
          this.errorDetails = null;
          this.errorMessage = null;
          this.originalImageData = response.data;
          console.log(response.data);

          if (!response.data.width || !response.data.height) {
            throw new Error('Malformed image. Height or width data missing.');
          }

          //TODO handle error

          this.cropperOpen = true;

          //let attachment = new window.Backbone.Model(response.data);
          //this.render(attachment);
        })
        .catch(error => {
          this.loading = false;
          this.error = true;
          this.errorMessage = this.i18n.get_data_error + ' ' + error.message;

          if (
            error.response &&
            error.response.data &&
            error.response.data.data.length &&
            error.response.data.data[0].message
          ) {
            this.errorMessage =
              this.i18n.get_data_error +
              ' ' +
              error.response.data.data[0].message;
          }

          if (error.response) {
            this.errorDetails = JSON.stringify(error.response, null, 4);
          } else if (JSON.stringify(error) !== '{}') {
            this.errorDetails = JSON.stringify(error, null, 4);
          }
        });

      // Fire event so ACF can update field value
      const event = new CustomEvent('aiarc-update-field-value', {
        detail: {
          id: this.context.field_name,
          value: imageData.id,
        },
      });

      document.dispatchEvent(event);
    },
    updateACFFieldValue(value) {
      // Fire event so ACF can update field value
      const event = new CustomEvent('aiarc-update-field-value', {
        detail: {
          id: this.context.field_name,
          value: value,
        },
      });
      document.dispatchEvent(event);
    },
    openMediaModal: function() {
      acf.media.popup({
        title: acf._e('image', 'select'),
        mode: 'select',
        type: 'image',
        field: this.context.key,
        multiple: false,
        library: 'all', // TODO
        mime_types: '', // TODO

        select: (attachment, i) => {
          console.log('Got following data from media modal', attachment, i);
          console.log(
            'Convert backbone to normal object',
            attachment.toJSON(),
            i,
          );
          this.startCrop(attachment.toJSON());
        },
      });
    },
  },
};
</script>

<style lang="scss">
.aiarc-modal {
  @import '../../node_modules/cropperjs/dist/cropper';

  .cropper-point.point-se {
    cursor: nwse-resize;
    height: 5px;
    width: 5px;
  }

  @media (min-width: 768px) {
    .cropper-point.point-se {
      height: 5px;
      width: 5px;
    }
  }

  @media (min-width: 992px) {
    .cropper-point.point-se {
      height: 5px;
      width: 5px;
    }
  }

  @media (min-width: 1200px) {
    .cropper-point.point-se {
      height: 5px;
      opacity: 0.75;
      width: 5px;
    }
  }

  .cropper-view-box {
    outline: 2px solid white;
  }

  .cropper-line {
    outline-color: white;
  }

  .cropper-point {
    background-color: white;
    border-radius: 50%;
    opacity: 1;
    transform: scale(2);
  }

  .cropper-dashed {
    border-style: solid;
  }

  .cropper-line {
    outline: transparent;
    background-color: transparent;
  }
}
</style>
