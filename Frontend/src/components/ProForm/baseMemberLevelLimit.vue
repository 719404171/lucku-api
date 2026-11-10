<template>
  <div class="base-member-level-limit f1">
    <el-checkbox-group v-model="selected" v-bind="config.attr || {}" @change="emitValue">
      <el-checkbox v-for="item in config.options" :key="item.value" :label="item.value">{{ item.label }}</el-checkbox>
    </el-checkbox-group>
    <div v-if="selected.length" class="limit-rows">
      <div class="limit-row" v-for="id in selected" :key="id">
        <span class="limit-name">{{ labelOf(id) }}</span>
        <el-input-number
          :value="limitOf(id)"
          :min="0"
          :step="1"
          size="small"
          controls-position="right"
          @change="onLimitChange(id, $event)"
        />
        <span class="limit-unit">{{ $t('classificationList.levelDrawLimitUnit') }}</span>
      </div>
    </div>
    <div class="limit-tip">{{ $t('classificationList.memberLevelLimitMultiTip') }}</div>
  </div>
</template>

<script>
export default {
  name: 'BaseMemberLevelLimit',
  props: {
    config: {
      type: Object,
      default: () => ({})
    },
    value: {
      type: [Array, Object, String, Number],
      default: () => []
    }
  },
  data() {
    return {
      selected: [],
      limits: {}
    }
  },
  created() {
    this.syncFromValue(this.value)
  },
  watch: {
    value: {
      handler(val) {
        this.syncFromValue(val)
      },
      deep: true
    }
  },
  methods: {
    parse(val) {
      let list = val
      if (typeof list === 'string') {
        try {
          list = JSON.parse(list || '[]')
        } catch (e) {
          list = []
        }
      }
      if (!Array.isArray(list)) return []
      return list
        .map(item => {
          if (item && typeof item === 'object') {
            return {
              member_id: Number(item.member_id || item.value || 0),
              draw_limit: Math.max(0, Number(item.draw_limit || item.limit || 0))
            }
          }
          return { member_id: Number(item || 0), draw_limit: 0 }
        })
        .filter(item => item.member_id > 0)
    },
    syncFromValue(val) {
      const list = this.parse(val)
      const limits = {}
      list.forEach(item => {
        limits[item.member_id] = item.draw_limit
      })
      this.limits = limits
      this.selected = this.config.options
        .map(opt => Number(opt.value))
        .filter(id => id > 0 && Object.prototype.hasOwnProperty.call(limits, id))
    },
    labelOf(id) {
      const opt = (this.config.options || []).find(o => Number(o.value) === Number(id))
      return opt ? opt.label : 'ID:' + id
    },
    limitOf(id) {
      return this.limits[id] || 0
    },
    onLimitChange(id, val) {
      this.limits = { ...this.limits, [id]: Math.max(0, Number(val) || 0) }
      this.emitValue()
    },
    emitValue() {
      // 按後台等級下拉的展示順序輸出，便於閱讀與保存
      const order = (this.config.options || []).map(opt => Number(opt.value))
      const result = this.selected
        .filter(id => id > 0)
        .sort((a, b) => order.indexOf(a) - order.indexOf(b))
        .map(id => ({ member_id: Number(id), draw_limit: this.limitOf(id) }))
      this.$emit('input', result)
    }
  }
}
</script>

<style scoped>
.base-member-level-limit {
  width: 100%;
}
.limit-rows {
  margin-top: 8px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.limit-row {
  display: flex;
  align-items: center;
  gap: 10px;
}
.limit-name {
  min-width: 90px;
  color: #606266;
}
.limit-unit {
  color: #909399;
  font-size: 13px;
}
.limit-tip {
  margin-top: 6px;
  color: #909399;
  font-size: 12px;
  line-height: 1.5;
}
</style>
