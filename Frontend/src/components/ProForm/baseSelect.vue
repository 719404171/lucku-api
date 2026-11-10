<template>
<el-select class="f1" v-model="modelValue" v-bind="config.attr || {}" :placeholder="$t(config.attr.placeholder)" @change="$emit('change', modelValue)">
  <el-option v-for="(item, index) in config.options" :key="index" :label="item.trans ? item.label : $t(item.label)" :value="item.value"></el-option>
</el-select>
</template>

<script>
export default {
  name: "BaseSelect",
  model: {
    prop: 'value',
    event: 'change'
  },
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    value: [String, Number, Boolean, Array]
  },
  data() {
    return {
      modelValue: this.value,
    };
  },
  mounted() {
    this.modelValue = this.value
  },
  watch: {
    value(val) {
      this.modelValue = val
    }
  }
};
</script>
