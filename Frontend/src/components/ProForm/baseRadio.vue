<template>
<div>
  <el-radio-group v-model="modelValue" v-bind="config.attr || {}" @change="$emit('change', modelValue)">
    <el-radio v-for="item in config.options" :key="item.value" :label="item.value">{{item.label}}</el-radio>
  </el-radio-group>
</div>
</template>

<script>
export default {
  name: "BaseRadio",
  model: {
    prop: 'value',
    event: 'change'
  },
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    value: [String, Number, Boolean]
  },
  data() {
    return {
      modelValue: this.value,
    };
  },
  watch: {
    value(val) {
      this.modelValue = val
    }
  }
}
</script>
