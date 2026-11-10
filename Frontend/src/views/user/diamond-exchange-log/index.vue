<template>
  <div class="app-container">
    <div class="df aic mb20" v-if="device==='desktop'">
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('DiamondExchangeLog')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('DiamondExchangeLog')"></el-button>
      <el-form label-suffix="：" inline class="ml20">
        <el-form-item :label="$t('diamondExchangeLog.totalDiamond')">{{ summary.total_diamond }}</el-form-item>
        <el-form-item :label="$t('diamondExchangeLog.totalCoin')">{{ summary.total_coin }}</el-form-item>
      </el-form>
    </div>
    <div class="df aic mb20" v-else>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('DiamondExchangeLog')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('DiamondExchangeLog')"></el-button>
    </div>

    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column"
      :column-attr="{ user_email: { minWidth: '180px' }, created_at: { minWidth: '150px' } }"
      @page-change="onPageChange('DiamondExchangeLog', $event)">
    </ProTable>
  </div>
</template>

<script>
import api from '@/api'
import { diamondExchangeLog } from '@/form'
import paging from '@/mixins/paging'
export default {
  name: 'DiamondExchangeLog',
  mixins: [paging],
  data() {
    return {
      SFC: diamondExchangeLog,
      summary: { total_diamond: '0.00', total_coin: '0.00' },
      column: {
        id: 'diamondExchangeLog.logId',
        user_id: 'diamondExchangeLog.userId',
        user_name: 'diamondExchangeLog.userName',
        user_phone: 'common.tel',
        user_email: 'common.email',
        diamond_amount: 'diamondExchangeLog.diamondAmount',
        coin_amount: 'diamondExchangeLog.coinAmount',
        rate: 'diamondExchangeLog.rate',
        user_balance: 'diamondExchangeLog.balance',
        created_at: 'diamondExchangeLog.createdAt'
      }
    }
  },
  created() {
    this.getList('DiamondExchangeLog')
  },
  methods: {
    // 覆写 paging 的 getList，额外取合计钻石/金币
    async getList(name, { page = 1, limit = 10, ...elseParams } = {}) {
      try {
        this.isLoading = true
        const { results } = await api.reqDiamondExchangeLog({ page, limit, ...elseParams })
        this.list = results.data || []
        this.page.current = page
        this.page.size = limit
        this.page.total = results.count || 0
        this.summary = {
          total_diamond: results.total_diamond || '0.00',
          total_coin: results.total_coin || '0.00'
        }
      } catch (error) {
        console.log(error)
      } finally {
        this.isLoading = false
      }
    }
  }
}
</script>
