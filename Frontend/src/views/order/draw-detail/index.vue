<template>
  <div class="app-container">
    <div class="df mb20" v-if="device==='desktop'">
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('DrawDetail')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('DrawDetail')"></el-button>
    </div>
    <div class="df mb20" v-else>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('DrawDetail')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('DrawDetail')"></el-button>
    </div>

    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column"
      :column-attr="{ order_no: { minWidth: '180px' }, user_id: { minWidth: '80px' }, product_img: { minWidth: '90px' }, product_name: { minWidth: '220px' } }"
      @page-change="onPageChange('DrawDetail', $event)">
      <template #product_img="{row}">
        <ProImage v-if="row.product_img" :src="row.product_img" :size="50" />
        <span v-else>--</span>
      </template>
      <template #product_name="{row}">{{ cardName(row) }}</template>
    </ProTable>
  </div>
</template>

<script>
import { drawDetail } from '@/form'
import paging from '@/mixins/paging'
export default {
  name: 'DrawDetail',
  mixins: [paging],
  data() {
    return {
      SFC: drawDetail,
      column: {
        order_no: 'drawDetail.orderNo',
        user_id: 'drawDetail.userId',
        user_name: 'drawDetail.userName',
        user_email: 'common.email',
        product_img: 'drawDetail.cardImg',
        product_name: 'drawDetail.cardName',
        card_sort_name: 'drawDetail.cardSort',
        product_price: 'drawDetail.cardPrice',
        created_at: 'drawDetail.time',
      }
    }
  },
  created() {
    this.getList('DrawDetail')
  },
  methods: {
    cardName(row) {
      return this.$store.getters.language === 'en' ? (row.en_product_name || row.product_name) : row.product_name
    }
  }
}
</script>
