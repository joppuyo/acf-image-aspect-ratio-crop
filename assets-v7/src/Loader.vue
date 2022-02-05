<template>
  <div>
    <div
      v-bind:style="{ maxWidth: previewWidth }"
      v-bind:class="$style['loader']"
    >
      <div
        v-bind:class="$style['spinner']"
        v-html="require('bundle-text:./spinner.svg')"
      ></div>
    </div>
  </div>
</template>

<script>
export default {
  props: ['previewSize'],
  computed: {
    previewWidth() {
      if (this.previewSize && this.previewSize.width) {
        return this.previewSize.width + 'px';
      }
      return 'auto';
    },
  },
};
</script>

<style module>
.loader {
  width: 100%;
  aspect-ratio: 4/3;
  background-color: #f0f0f1;
  display: flex;
  justify-content: center;
  align-items: center;
}
.spinner svg {
  opacity: 1;
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
