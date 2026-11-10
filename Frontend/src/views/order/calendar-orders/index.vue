<template>
  <div class="app-container">
    <div class="mb20">
      <el-button type="primary" icon="el-icon-refresh" plain @click="getOrder(types[type])"></el-button>
      <el-radio-group class="mlr10" v-model="type" @change="toggType"><el-radio-button :label="index" v-for="(i, index) in 3" :key="i">{{$t('order.type'+index)}}</el-radio-button></el-radio-group>
      <el-date-picker :class="{mt20: device==='mobile'}" v-model="targetDate" type="date" :placeholder="$t('common.sltDate')" value-format="yyyy-MM-dd"></el-date-picker>
    </div>
    <el-calendar v-model="targetDate" ref="calendar" v-loading="isLoading">
      <template #dateCell="{date, data}">
        <div :class="{df:device==='desktop'}">
          <div>{{date.getDate()}}</div>
          <div class="nos" v-if="device==='desktop'">
            <el-scrollbar wrap-class="scrollbar-wrapper">
              <div class="item" v-for="(item, index) in list.filter(i => (i.payment_at || i.created_at || '').includes(data.day))" :key="index">
                #{{ item.order_no }}
                <span v-if="item.payment_at">({{ $t('order.payment_at') }} {{ item.payment_at }})</span>
                <span v-else>({{ $t('order.created_at') }} {{ item.created_at }})</span>
              </div>
            </el-scrollbar>
          </div>
          <el-popover class="mr10" placement="bottom" trigger="click" v-if="device==='mobile' && list.filter(i => i.created_at.includes(data.day)).length">
            <div class="nos">
              <el-scrollbar wrap-class="scrollbar-wrapper">
                <div class="item" v-for="(item, index) in list.filter(i => (i.payment_at || i.created_at || '').includes(data.day))" :key="index">
                  #{{ item.order_no }}
                  <span v-if="item.payment_at">({{ $t('order.payment_at') }} {{ item.payment_at }})</span>
                  <span v-else>({{ $t('order.created_at') }} {{ item.created_at }})</span>
                </div>
              </el-scrollbar>
            </div>
            <el-button type="text" slot="reference">{{$t('common.view')}}</el-button>
          </el-popover>
        </div>
      </template>
    </el-calendar>
  </div>
</template>

<script>
import { getCalendarOrder } from '@/api'
export default {
  name: 'CalendarOrders',
  data() {
    return {
      targetDate: '',
      isLoading: false,
      type: 0,
      types: [[1,2], [1], [2]],
      list: []
    }
  },
  created() {
    this.getOrder(this.types[this.type])
  },
  methods: {
    // 切换类型
    toggType(e) {
      this.getOrder(this.types[e])
    },
    // 获取日历订单
    async getOrder(indent_type) {
      try {
        this.isLoading = true
        const {results} = await getCalendarOrder({indent_type})
        this.list = results
      } catch (error) {
        
      } finally {
        this.isLoading = false
      }
    }
  }
}
</script>

<style lang="scss">
.nos {
  height: 69px;
  margin-left: 20px;
  .el-scrollbar {
    height: 100%;
    .scrollbar-wrapper {
      overflow-x: hidden !important;
    }
  }
  .item {
    // height: 23px;
    padding-right: 15px;
    line-height: 20px;
    font-size: 13px;
    word-break: break-all;
  }
}
</style>