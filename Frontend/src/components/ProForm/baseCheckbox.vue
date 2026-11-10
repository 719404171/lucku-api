<template>
<el-checkbox-group v-model="modelValue" v-bind="config.attr || {}" @change="$emit('change', modelValue)">
  <el-checkbox v-for="item in config.options" :key="item.value" v-bind="item">{{item.label}}</el-checkbox>
</el-checkbox-group>
</template>

<script>
export default {
  name: 'BaseCheckbox',
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    value: Array
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
