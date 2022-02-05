<template>
  <div>
    <img
      ref="preview"
      loading="lazy"
      src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
      v-bind:class="$style['preview-image']"
      v-bind:srcset="srcset"
      alt=""
      v-bind:style="{ maxWidth: this.previewWidth }"
      v-bind:sizes="imageWidth"
      v-bind:height="imageData.height"
      v-bind:width="imageData.width"
    />
  </div>
</template>

<script>
import assertEqualsWithDelta from './assertEqualsWithDelta';
import Preview from './Preview';

export default {
  props: ['imageData', 'previewSize'],
  data: function() {
    return {
      imageWidth: null,
      srcset: '',
    };
  },
  mounted() {
    this.$nextTick(function() {
      window.addEventListener('resize', this.updateImageWidth);
      this.updateImageWidth();
    });
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.updateImageWidth);
  },
  computed: {
    previewWidth() {
      if (this.previewSize && this.previewSize.width) {
        return this.previewSize.width + 'px';
      }
      return 'auto';
    },
  },
  methods: {
    updateImageWidth() {
      if (this.imageData) {
        setTimeout(() => {
          this.imageWidth =
            this.$refs.preview.getBoundingClientRect().width + 'px';
          this.updateSrcset();
        }, 1);
      }
    },
    updateSrcset() {
      if (!this.imageWidth) {
        return;
      }

      let targetAspectRatio = this.imageData.width / this.imageData.height;

      console.log(targetAspectRatio);

      let srcset = [];
      for (const size of Object.values(this.imageData.sizes)) {
        const aspectRatio = size.width / size.height;

        if (assertEqualsWithDelta(aspectRatio, targetAspectRatio, 0.1)) {
          srcset.push(size.url + '?xxx=yyy ' + size.width + 'w');
        }
      }
      this.srcset = srcset.join(', ');
    },
  },
};
</script>

<style module>
.preview-image {
  width: 100% !important;
  height: auto !important;
}
</style>
