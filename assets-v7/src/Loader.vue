<template>
  <div>
    <div v-bind:style="{ maxWidth: previewWidth }">
      <div v-bind:class="$style['loader-outer']">
        <div
          v-bind:class="$style['loader']"
          v-bind:style="{ paddingBottom: paddingBottom }"
        >
          <div v-bind:class="$style['loader-inner']">
            <div
              v-bind:class="$style['spinner']"
              v-html="require('bundle-text:./global/spinner.svg')"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: ['previewSize', 'context'],
  computed: {
    previewWidth() {
      if (this.previewSize && this.previewSize.width) {
        return this.previewSize.width + 'px';
      }
      return 'auto';
    },
    paddingBottom() {
      if (this.context.aspect_ratio_width && this.context.aspect_ratio_height) {
        return (
          (this.context.aspect_ratio_height / this.context.aspect_ratio_width) *
            100 +
          '%'
        );
      }
      return (3 / 4) * 100 + '%';
    },
  },
};
</script>

<style module>
.loader-outer {
  position: relative;
  width: 100%;
}

.loader {
  position: relative;
  padding-bottom: calc(100% * 9 / 16);
}

.loader-inner {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  height: 100%;
  width: 100%;
  background-color: #f0f0f1;
  display: flex;
  justify-content: center;
  align-items: center;
}

.spinner svg {
  opacity: 0.25;
  animation-name: aiarc-v7-spin;
  animation-duration: 2000ms;
  animation-iteration-count: infinite;
  animation-timing-function: linear;
}

@keyframes aiarc-v7-spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
