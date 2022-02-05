<template>
  <div>
    <div v-bind:class="$style['message']">
      <span
        v-bind:class="$style['message-icon']"
        v-html="require('bundle-text:./error-triangle.svg')"
      ></span>
      <span v-bind:class="$style['message-text']">{{ errorMessage }}</span>
    </div>
    <!--<div v-if="errorDetails">
      <pre>{{errorDetails}}</pre>
    </div>-->
  </div>
</template>

<script>
export default {
  props: ['error', 'errorMessage', 'errorDetails'],
};
</script>

<style module lang="scss">
.message {
  background-color: rgba(244, 67, 54, 0.3);
  padding: 10px;
  color: black;
  border-radius: 3px;
  margin-bottom: 20px;
  margin-top: 20px;
  display: flex;
  align-items: center;
}
.message-icon {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-right: 10px;
  svg {
    fill: rgba(244, 67, 54, 1);
  }
}
</style>
