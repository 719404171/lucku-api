<template>
  <div>
    <el-switch v-model="modelValue" v-bind="config.attr || {}" @change="$emit('change', modelValue)"></el-switch>
  </div>
</template>

<script>
export default {
  name: 'BaseSwitch',
  model: {
    prop: 'value',
    event: 'change'
  },
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    value: [Boolean, String, Number]
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
