<template>
  <el-cascader class="f1" v-model="modelValue" v-bind="config.attr || {}" :placeholder="$t(config.attr.placeholder)" :props="config.props || {}" @change="$emit('change', modelValue)"></el-cascader>
</template>

<script>
export default {
  name: "BaseCascader",
  model: {
    prop: 'value',
    event: 'change'
  },
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    value: Array
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
};
</script>
