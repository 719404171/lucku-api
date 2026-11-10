<template>
  <el-input v-model="modelValue" v-bind="config.attr || {}" :placeholder="$t(config.attr.placeholder)" @input="$emit('input', config.select ? `${select},${modelValue || ''}` : modelValue)">
    <template :slot="config.slotName || 'prepend'" v-if="config.select">
      <el-select style="width: 100px" v-model="select" :placeholder="config.selectPlaceholder" @change="$emit('input', config.select ? `${select},${modelValue || ''}` : modelValue)">
        <el-option v-for="(item, index) in config.select" :key="index" :label="item.trans ? item.label : $t(item.label)" :value="item.value"></el-option>
      </el-select>
    </template>
  </el-input>
</template>

<script>
export default {
  name: 'BaseInput',
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    value: String
  },
  data() {
    return {
      select: '',
      modelValue: ''
    }
  },
  watch: {
    value: {
      handler(val) {
        if (this.config.select) {
          const [select, modelValue=''] = val.split(',')
          this.select = select
          this.modelValue = modelValue
        } else {
          this.modelValue = val
        }
      },
      immediate: true
    }
  }
}
</script>
