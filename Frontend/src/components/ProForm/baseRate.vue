<template>
  <el-rate v-model="modelValue" v-bind="config.attr || {}" @change="$emit('change', modelValue)"></el-rate>
</template>

<script>
export default {
  name: 'BaseRate',
  model: {
    prop: 'value',
    event: 'change'
  },
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
