<template>
  <div :style="{width: config.width || '100%'}">
    <el-slider v-model="modelValue" v-bind="config.attr || {}" @input="$emit('input', modelValue)"></el-slider>
  </div>
</template>

<script>
export default {
  name: 'BaseSlider',
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    value: Number
  },
  data() {
    return {
      modelValue: this.value
    }
  },
  watch: {
    value(val) {
      this.modelValue = val
    }
  }
}
</script>
