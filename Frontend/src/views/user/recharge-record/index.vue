<template>
  <div class="app-container">
    <div class="df aic mb20" v-if="device==='desktop'">
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('RechargeRecord')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('RechargeRecord')"></el-button>
      <el-form label-suffix="：" inline class="ml20">
        <el-form-item :label="$t('recharge.totalMoney')">{{ sales[2] }}</el-form-item>
      </el-form>
    </div>
    <div class="df aic mb20" v-else>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('RechargeRecord')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('RechargeRecord')"></el-button>
    </div>

    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column"
      :column-attr="{ user_id: { minWidth: '80px' }, recharge_money: { minWidth: '100px' }, trade_no: { minWidth: '200px' } }"
      @page-change="onPageChange('RechargeRecord', $event)">
      <template #payment_mode="{row}">{{ $t('recharge.m' + row.payment_mode) }}</template>
      <template #status="{row}">
        <el-tag size="mini" :type="row.status == 1 ? 'success' : 'info'">{{ $t('recharge.s' + row.status) }}</el-tag>
      </template>
      <!-- 支付流水號（我方 reference_number）：可到 Wonder 商家後台按參考號搜單 -->
      <template #trade_no="{row}">
        <span>{{ row.trade_no }}</span>
        <el-link v-if="row.trade_no" class="ml10" type="primary" :underline="false" @click="onCopy(row.trade_no)">{{ $t('common.copy') }}</el-link>
      </template>
    </ProTable>
  </div>
</template>

<script>
import { rechargeRecord } from '@/form'
import { copyText } from '@/utils'
import paging from '@/mixins/paging'
export default {
  name: 'RechargeRecord',
  mixins: [paging],
  data() {
    return {
      SFC: rechargeRecord,
      sales: [],
      column: {
        id: 'recharge.id',
        user_id: 'recharge.userId',
        user_name: 'recharge.userName',
        user_email: 'common.email',
        user_phone: 'common.tel',
        paid_amount: 'recharge.paidAmount',
        recharge_money: 'recharge.money',
        bonus_coin: 'recharge.bonusCoin',
        payment_mode: 'recharge.paymentMode',
        status: 'recharge.status',
        trade_no: 'recharge.tradeNo',
        created_at: 'recharge.time',
      }
    }
  },
  created() {
    this.getList('RechargeRecord')
  },
  methods: {
    onCopy(text) {
      this.$message(copyText(text)
        ? { message: this.$t('common.copySuccess'), type: 'success' }
        : { message: this.$t('common.copyFail'), type: 'warning' })
    }
  }
}
</script>
