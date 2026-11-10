<template>
  <el-time-picker  v-model="modelValue" v-bind="config.attr || {}" @change="$emit('change', modelValue)"></el-time-picker>
</template>

<script>
export default {
  name: "BaseDatePicker",
  model: {
    prop: "value",
    event: "change",
  },
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    value: [String, Number, Date, Array],
  },
  data() {
    return {
      modelValue: this.value
    };
  },
  watch: {
    value(val) {
      this.modelValue = val;
    },
  },
};
</script>