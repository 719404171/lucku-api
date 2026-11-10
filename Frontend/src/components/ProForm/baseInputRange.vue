<template>
  <div class="ir">
    <el-input class="inp" v-model="modelValue[0]"></el-input>
    <span>~</span>
    <el-input class="inp" v-model="modelValue[1]"></el-input>
    <span>{{text}}</span>
  </div>
</template>

<script>
export default {
  name: 'BaseInputRange',
  props: {
    config: {
      type: Object,
      default: () => ({min: 0, max: Infinity}),
    },
    text:{
      type: String,
      default: "",
    },
    value: Array
  },
  data() {
    return {
      modelValue: this.value
    }
  },
  watch: {
    modelValue(val) {
      this.$emit('input', val)
    },
    value(val) {
      this.modelValue = val
    }
  }
}
</script>

<style scoped lang="scss">
  .ir {
    display: flex;
    justify-content: center;
    align-items: center;
    .inp {
      width: 60px;
    }
    span {
      margin: 0 10px;
    }
  }
</style>