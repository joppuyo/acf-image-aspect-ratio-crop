<template>
  <div
    v-bind:class="$style['image-wrapper']"
    v-bind:style="{ maxWidth: this.previewWidth }"
  >
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
    <div v-bind:class="$style['actions']">
      <button
        v-bind:class="[$style['action'], $style['action--crop']]"
        v-html="require('bundle-text:./crop.svg')"
      ></button>
      <button
        v-bind:class="$style['action']"
        v-html="require('bundle-text:./pencil.svg')"
        v-on:click="openEditModal"
      ></button>
      <button
        v-bind:class="[$style['action'], $style['action--delete']]"
        v-html="require('bundle-text:./close.svg')"
        v-on:click="deleteImage"
      ></button>
    </div>
  </div>
</template>

<script>
import assertEqualsWithDelta from '../assertEqualsWithDelta';
import Preview from './Preview';

export default {
  props: ['imageData', 'previewSize', 'i18n'],
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
    deleteImage() {
      this.emitter.emit('delete-image');
    },
    openEditModal() {
      window.acf.media.popup({
        title: this.i18n.edit_image,
        button: this.i18n.update_image,
        mode: 'edit',
        attachment: this.imageData.id,
      });
    },
    updateImageWidth() {
      if (this.imageData && this.$refs.preview) {
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

<style lang="scss" module>
.preview-image {
  width: 100% !important;
  height: auto !important;
}

.image-wrapper {
  position: relative;
}

.actions {
  position: absolute;
  top: 0;
  right: 0;
  padding: 5px;
  display: flex;
}

.action {
  all: unset;
  border-color: transparent !important;
  background: #23282d;
  height: 26px;
  width: 26px;
  border: transparent solid 1px;
  border-radius: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-left: 4px;
  cursor: pointer;

  svg {
    fill: #eee;
    width: 20px;
    height: auto;
  }

  &:hover svg,
  &:active svg {
    fill: #00b9eb;
  }
}

.action--crop svg {
  height: 16px;
}

.action--crop:hover svg *,
.action--crop:active svg * {
  fill: #00b9eb;
}

.action--delete:hover svg,
.action--delete:active svg {
  fill: #dc3232;
}
</style>
