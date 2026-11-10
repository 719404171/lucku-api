<template>
  <el-color-picker v-model="modelValue" v-bind="config.attr || {}" @change="$emit('input', modelValue)"></el-color-picker>
</template>

<script>

export default {
  name: 'BaseColorPicker',
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    value: String
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
