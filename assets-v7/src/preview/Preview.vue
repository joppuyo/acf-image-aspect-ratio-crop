<template>
  <div
    v-bind:class="$style['image-wrapper']"
    v-bind:style="{ maxWidth: this.previewWidth }"
  >
    <div v-bind:class="$style['preview-image-outer']">
      <div
        v-bind:class="$style['preview-image']"
        v-bind:style="{ paddingBottom: paddingBottom }"
      >
        <img
          ref="preview"
          loading="lazy"
          src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
          v-bind:class="$style['preview-image-inner']"
          v-bind:srcset="srcset"
          alt=""
          v-bind:style="{ maxWidth: this.previewWidth }"
          v-bind:sizes="imageWidth"
        />
        <div v-bind:class="$style['actions']">
          <button
            class="js-aiarc-actions-crop"
            v-bind:class="[$style['action'], $style['action--crop']]"
            v-html="require('bundle-text:./crop.svg')"
            v-on:click.prevent="reCrop"
            v-bind:disabled="loading"
          ></button>
          <button
            class="js-aiarc-actions-edit"
            v-bind:class="$style['action']"
            v-html="require('bundle-text:./pencil.svg')"
            v-on:click.prevent="openEditModal"
            v-bind:disabled="loading"
          ></button>
          <button
            class="js-aiarc-actions-delete"
            v-bind:class="[$style['action'], $style['action--delete']]"
            v-html="require('bundle-text:./close.svg')"
            v-on:click.prevent="deleteImage"
            v-bind:disabled="loading"
          ></button>
        </div>
        <div
          v-bind:class="$style['loading-overlay']"
          v-if="loading"
          v-html="require('bundle-text:../global/spinner.svg')"
        ></div>
      </div>
    </div>
  </div>
</template>

<script>
import assertEqualsWithDelta from '../assertEqualsWithDelta';

export default {
  props: ['imageData', 'previewSize', 'i18n', 'loading'],
  data: function () {
    return {
      imageWidth: null,
      srcset: '',
      src: '',
    };
  },
  mounted() {
    this.updateImageWidth();

    this.$nextTick(function () {
      window.addEventListener('resize', this.updateImageWidth);
      //this.updateImageWidth();
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
    paddingBottom() {
      if (this.imageData && this.imageData.width && this.imageData.width) {
        return (this.imageData.height / this.imageData.width) * 100 + '%';
      }
      return (3 / 4) * 100 + '%';
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
          srcset.push(size.url + ' ' + size.width + 'w');
        }
      }
      this.srcset = srcset.join(', ');
    },
    reCrop() {
      this.emitter.emit('re-crop');
    },
  },
  watch: {
    imageData(newValue, oldValue) {
      this.updateSrcset();
    },
  },
};
</script>

<style lang="scss" module>
.preview-image-outer {
  position: relative;
  width: 100%;
}

.preview-image {
  position: relative;
  padding-bottom: calc(100% * 9 / 16);
}

.preview-image-inner {
  all: revert;
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  height: 100%;
  width: 100%;
  overflow: hidden;
}

//.preview-image {
//  width: 100% !important;
//  height: auto !important;
//}

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

  &:hover {
    background-color: #191e23;
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

.loading-overlay {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
}

.loading-overlay svg {
  opacity: 1;
  animation-name: aiarc-v7-spin;
  animation-duration: 2000ms;
  animation-iteration-count: infinite;
  animation-timing-function: linear;
  path {
    fill: white;
  }
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
