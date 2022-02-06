<template>
  <teleport to="body">
    <div
      v-if="cropperOpen"
      class="aiarc-modal"
      v-bind:class="$style['modal-background']"
    >
      <div v-bind:class="$style['modal-outer']">
        <div
          v-bind:class="$style['modal']"
          class="js-acf-image-aspect-ratio-crop-modal"
          v-bind:style="{ width: modalWidth }"
        >
          <div v-bind:class="$style['header']">
            <div v-bind:class="$style['header-start']">
              <div>{{ i18n.modal_title }}</div>
            </div>
            <div v-bind:class="$style['header-end']">
              <button
                v-html="require('bundle-text:./close.svg')"
                v-on:click="closeCropper"
                class="js-acf-image-aspect-ratio-crop-cancel"
              ></button>
            </div>
          </div>
          <div
            v-bind:class="$style['image-wrapper']"
            v-bind:style="{ width: modalWidth }"
          >
            <div
              v-bind:class="$style['image-area-outer']"
              v-bind:style="{ width: maxWidth }"
            >
              <div
                v-bind:class="$style['image-area']"
                v-bind:style="{ paddingBottom: paddingBottom }"
              >
                <vue-cropper
                  ref="cropper"
                  v-bind:class="$style['image-area-inner']"
                  v-bind:src="originalImageData.url"
                  v-bind:aspectRatio="16 / 9"
                  v-bind:viewMode="1"
                  v-bind:autoCropArea="1"
                  v-bind:zoomable="false"
                  v-bind:checkCrossOrigin="false"
                  v-bind:checkOrientation="false"
                  v-bind:responsive="false"
                  v-bind:key="cropJsKey"
                  v-bind:crop="cropChanged"
                />
              </div>
            </div>
          </div>
          <div v-bind:class="$style['footer']">
            <div v-bind:class="$style['footer-info']">
              <div>
                {{text}}
              </div>
            </div>
            <div v-bind:class="$style['footer-controls']">
              <button v-bind:class="$style['footer-button']">
                {{ i18n.reset }}
              </button>
              <button v-bind:class="$style['footer-button']">
                {{ i18n.cancel }}
              </button>
              <button
                  class="js-acf-image-aspect-ratio-crop-crop"
                v-bind:class="[
                  $style['footer-button'],
                  $style['footer-button--primary'],
                ]"
                v-on:click="executeCrop"
              >
                {{ i18n.crop }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script>
import VueCropper from 'vue-cropperjs';
import axios from "axios";

export default {
  props: ['cropperOpen', 'originalImageData', 'i18n', 'context'],
  components: {
    VueCropper,
  },
  data: function() {
    return {
      modalWidth: 0,
      maxWidth: 0,
      maxHeight: 0,
      paddingBottom: 0,
      cropJsKey: Math.round(Math.random() * 1000),
      text: '',
    };
  },
  methods: {
    cropChanged(event) {
      console.log(event);
    },
    executeCrop() {

      this.text = this.i18n.cropping_in_progress;

      let cropData = this.$refs.cropper.getData()

      var data = {
        id: this.originalImageData.id,
        aspectRatioHeight: this.context.aspect_ratio_height,
        aspectRatioWidth: this.context.aspect_ratio_width,
        cropType: this.context.crop_type,
        x: cropData.x,
        y: cropData.y,
        width: cropData.width,
        height: cropData.height,
        key: this.context.key,
      };

      this.$refs.cropper.disable();

      let options = {};

      let url = null;

      url = `${this.context.api_root}/aiarc/v1/crop`;
      options = {
        headers: {
          'X-WP-Nonce': this.context.wp_rest_nonce,
        },
      };

      axios
          .post(url, data, options)
          .then(response => {
            console.log(response.data);
            this.emitter.emit('update-image-by-id', response.data.id);
            this.emitter.emit('close-cropper');
            //self.cropComplete(response.data);
            /*$('.js-acf-image-aspect-ratio-crop-crop').prop('disabled', false);
            $('.js-acf-image-aspect-ratio-crop-reset').prop(
                'disabled',
                false,
            );
            $('.js-acf-image-aspect-ratio-crop-modal-footer-status').empty();
            */
          })
          .catch(response => {
            console.error(response);
            this.$refs.cropper.enable();
            this.text = this.i18n.cropping_failed;
            /*$('.js-acf-image-aspect-ratio-crop-crop').prop('disabled', false);
            $('.js-acf-image-aspect-ratio-crop-reset').prop(
                'disabled',
                false,
            );
            $('.js-acf-image-aspect-ratio-crop-modal-footer-status').empty();
            $('.js-acf-image-aspect-ratio-crop-modal-footer-status').html(
                error,
            );*/
          });
    },
    closeCropper() {
      this.emitter.emit('close-cropper');
    },
    calculateElementSizes() {
      let aspectRatio =
        this.originalImageData.width / this.originalImageData.height;

      const headerHeight = 60;
      const footerHeight = 60;

      const padding = 40 * 2;

      const height =
        (window.innerHeight - padding - headerHeight - footerHeight) *
        aspectRatio;

      const isDesktop = window.innerWidth >= 1024;

      const width = window.innerWidth - (isDesktop ? padding : 0);

      this.maxWidth = Math.min(height, width) + 'px';

      if (window.innerWidth >= 600) {
        this.modalWidth = Math.max(Math.min(height, width), 600) + 'px';
      } else {
        this.modalWidth = '100%';
      }

      this.paddingBottom =
        (this.originalImageData.height / this.originalImageData.width) * 100 +
        '%';

      this.$nextTick(() => {
        // Kludge to refresh cropper instace so it re-initializes when browser size changes.
        this.cropJsKey = Math.round(Math.random() * 1000);
      });
    },
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.calculateElementSizes);
  },
  mounted() {
    window._acf_image_aspect_ratio_cropper = this.$refs.cropper;
    this.$nextTick(function() {
      window.addEventListener('resize', this.calculateElementSizes);
      this.calculateElementSizes();
    });
  },
};
</script>

<style lang="scss" module>
.modal-background {
  position: fixed;
  z-index: 159900;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.7);
  display: flex;
  justify-content: center;
  align-items: center;
  //padding: 40px;
}

.modal-outer {
  display: flex;
  flex-direction: column;
  z-index: 159901;
  //width: 100%;
  max-width: 100%;
  max-height: 100%;
}

.modal {
  background-color: white;
  border-radius: 2px;
}

.image-wrapper {
  display: flex;
  flex-shrink: 1;
  justify-content: center;
  background-color: #f0f0f1;
}

.image-area-outer {
  position: relative;
  width: 100%;
  //max-height: calc(100vh - 40px);
  //max-width: calc(100vh - 40px);
}

.image-area {
  position: relative;
  padding-bottom: calc(100% * 9 / 16);
}

.image-area-inner {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  height: 100%;
  width: 100%;
}

.header {
  height: 60px;
  display: flex;
  width: 100%;
}

.header-start {
  flex-grow: 1;
  display: flex;
  align-items: center;
}

.header-start div {
  all: unset;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
    Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
  font-weight: 600;
  font-size: 16px;
  color: #24282d;
  margin-left: 25px;
  margin-right: 25px;
}

.header-end {
  //height: 60px;
  //width: 60px;
}

.header-end button {
  all: unset;
  height: 60px;
  width: 60px;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
}

.footer {
  height: 60px;
  display: flex;
}

.footer-controls {
  display: flex;
  align-items: center;
  margin-right: 25px;
  margin-left: 25px;
  flex-shrink: 0;
}

.footer-button {
  display: inline-flex;
  text-decoration: none;
  font-weight: normal;
  font-size: 13px;
  margin: 0px;
  border: 0px;
  cursor: pointer;
  appearance: none;
  background: none;
  transition: box-shadow 0.1s linear 0s;
  height: 36px;
  align-items: center;
  box-sizing: border-box;
  padding: 6px 12px;
  border-radius: 2px;
  color: rgb(30, 30, 30);
}

.footer-button--primary {
  white-space: nowrap;
  background: var(--wp-admin-theme-color);
  color: #fff;
  text-decoration: none;
  text-shadow: none;
  outline: 1px solid transparent;
}

.footer-info {
  flex-grow: 1;
  display: flex;
  //margin-right: 32px;
  margin-left: 25px;
  align-items: center;
  flex-shrink: 1;
  min-width: 0;

  div {
    overflow: auto;
    min-width: 0;
    white-space: nowrap;
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
</style>
