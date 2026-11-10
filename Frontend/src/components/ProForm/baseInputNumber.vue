<template>
  <el-input-number class="f1" v-model.number="modelValue" v-bind="config.attr || {}" @change="$emit('input', modelValue)"></el-input-number>
</template>

<script>
export default {
  name: 'baseInputNumber',
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    value: [String, Number]
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
