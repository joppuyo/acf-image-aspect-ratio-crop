<template>
  <teleport to="body">
    <div v-if="cropperOpen" v-bind:class="$style['modal-background']">
      <div v-bind:class="$style['modal-outer']">
        <div
          v-bind:class="$style['modal']"
          class="js-acf-image-aspect-ratio-crop-modal"
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
            v-bind:class="$style['image-area-outer']"
            v-bind:style="{ width: maxWidth }"
          >
            <div
              v-bind:class="$style['image-area']"
              v-bind:style="{ paddingBottom: paddingBottom }"
            >
              <img
                v-bind:class="$style['image-area-inner']"
                v-bind:src="originalImageData.url"
              />
            </div>
          </div>
          <div v-bind:class="$style['footer']">footer</div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script>
export default {
  props: ['cropperOpen', 'originalImageData', 'i18n'],
  data: function() {
    return {
      maxWidth: 0,
      maxHeight: 0,
      paddingBottom: 0,
    };
  },
  methods: {
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

      this.paddingBottom =
        (this.originalImageData.height / this.originalImageData.width) * 100 +
        '%';
    },
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.calculateElementSizes);
  },
  mounted() {
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
}

.image-area-outer {
  position: relative;
  width: 100%;
  background-color: orange;
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
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
    Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
  font-weight: bold;
  font-size: 20px;
  color: #24282d;
  margin-left: 16px;
  margin-right: 16px;
}

.header-end {
  height: 60px;
  width: 60px;
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
}
</style>
