<template>
  <div class="mdp" v-if="device==='mobile' && config.attr.type==='datetimerange'">
    <input type="date" :placeholder="$t('common.start')" @change="$emit('change', mobVal.map(i => i && (i+' 00:00:00')))" v-model="mobVal[0]"><span>-</span>
    <input type="date" :placeholder="$t('common.end')" @change="$emit('change', mobVal.map(i => i+' 00:00:00'))" v-model="mobVal[1]">
  </div>
  <el-date-picker
    v-else
    class="f1"
    v-model="modelValue"
    v-bind="config.attr || {}"
    :placeholder="$t(config.attr.placeholder)"
    :start-placeholder="$t('common.start')"
    :end-placeholder="$t('common.end')"
    :picker-options="pickerOptions"
    @change="$emit('change', modelValue)"
  >
  </el-date-picker>
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
      mobVal: [],
      modelValue: this.value,
      shortcuts: [
        {
          text: '今天',
          onClick(picker) {
            picker.$emit("pick", new Date());
          },
        },
        {
          text: '昨天',
          onClick(picker) {
            const date = new Date();
            date.setTime(date.getTime() - 3600 * 1000 * 24);
            picker.$emit("pick", date);
          },
        },
        {
          text: '一周前',
          onClick(picker) {
            const date = new Date();
            date.setTime(date.getTime() - 3600 * 1000 * 24 * 7);
            picker.$emit("pick", date);
          },
        },
        {
          text: '最近一周',
          onClick(picker) {
            const end = new Date();
            const start = new Date();
            start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
            picker.$emit("pick", [start, end]);
          },
        },
        {
          text: '最近一个月',
          onClick(picker) {
            const end = new Date();
            const start = new Date();
            start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
            picker.$emit("pick", [start, end]);
          },
        },
        {
          text: '最近三个月',
          onClick(picker) {
            const end = new Date();
            const start = new Date();
            start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
            picker.$emit("pick", [start, end]);
          },
        },
      ],
    };
  },
  computed: {
    pickerOptions() {
      const result = [], target = this.config?.pickerOptions || []
      target.forEach(item => {
        result.push(...this.shortcuts.filter(subItem => subItem.text === item))
      });
      return result.length ? {shortcuts: result} : ''
    },
  },
  watch: {
    value(val) {
      this.modelValue = val;
      if (this.device==='mobile' && this.config.attr.type==='datetimerange') {
        this.mobVal = val.map(item => item.split(' ')[0])
      }
    }
  },
};
</script>

<style scoped>
.mdp {
  display: flex;
  align-items: center;
}
</style>