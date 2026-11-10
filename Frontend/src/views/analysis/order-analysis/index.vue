<template>
  <div class="app-container">
    <div class="mb20">
      <el-popover class="mr10" placement="bottom" trigger="click" :width="device==='mobile' ? '320' : ''">
        <ProForm ref="searchForm" :config="SFC" />
        <el-button type="primary" @click="onSearch('OA', device==='mobile' ? {limit:3} : {})">{{ $t('common.confirm') }}</el-button>
        <el-button v-if="device==='desktop'" slot="reference" type="primary" icon="el-icon-search">{{ $t('common.search') }}</el-button>
        <el-button v-else slot="reference" type="primary" icon="el-icon-search" />
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'OA', device==='mobile' ? {limit:3} : {})" />
    </div>

    <el-card v-loading="isLoading" class="mb30">
      <div class="export-btns">
        <el-button type="primary" @click="exportExcel">Excel汇出</el-button>
        <el-button type="primary" @click="exportWord">Word汇出</el-button>
      </div>
      <BarChart :chart-data="userCharData" />
    </el-card>

    <!--  -->
    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column" @page-change="onPageChange('OA', $event)">
      <template #product_img="{row}"><ProImage :src="row.product_img" /></template>
      <template #product_stock="{row}">{{ row.product_type===1 ? row.product_stock : '--' }}</template>
    </ProTable>

  </div>
</template>

<script>
import { orderCons } from '@/form'
import paging from '@/mixins/paging'
import BarChart from './components/BarChart.vue'
import { export_json_to_excel } from '@/utils/Export2Excel'
import { export_json_to_word } from '@/utils/Export2Word'
import { mapGetters } from 'vuex'

export default {
  name: 'OrderAnalysis',
  components: { BarChart },
  mixins: [paging],
  data() {
    return {
      SFC: orderCons,
      column: {
        product_img: 'goods.image',
        product_name: 'goods.name',
        product_price: 'goods.price',
        product_stock: 'goods.stock',
        browse_number: 'analysis.browse',
        user_collect_number: 'analysis.collect',
        sale_number: 'analysis.sale_number',
        sale_money: 'db.sales_volume'
      },
      userCharData: { data: [], bData: [], nData: [], mData: [] }
    }
  },
  created() {
    this.getList('OA', this.device === 'mobile' ? { limit: 3 } : {})
  },
  computed: {
    ...mapGetters(['language'])
  },
  watch: {
    list(val) {
      if (val.length) {
        this.userCharData = {
          data: val.map(item => item.product_name),
          bData: val.map(item => item.browse_number),
          nData: val.map(item => item.sale_number),
          mData: val.map(item => item.sale_money)
        }
      }
    }
  },
  methods: {
    exportExcel() {
      let header = null
      if (this.language === 'zh') {
        header = [
          '商品名称',
          '浏览次数',
          '销售数量',
          '销售金额'
        ]
      } else {
        header = [
          'Product name',
          'Number of views',
          'Sales volumes',
          'Sales amount'
        ]
      }
      const data = this.list.map(item => [
        item.product_name,
        item.browse_number,
        item.sale_number,
        item.sale_money
      ])

      export_json_to_excel({
        header,
        data,
        filename: this.language === 'zh' ? '订单分析' : 'Order analysis'
      })
    },

    exportWord() {
      let header = null
      if (this.language === 'zh') {
        header = [
          '商品名称',
          '浏览次数',
          '销售数量',
          '销售金额'
        ]
      } else {
        header = [
          'Product name',
          'Number of views',
          'Sales volumes',
          'Sales amount'
        ]
      }
      const data = this.list.map(item => [
        item.product_name,
        item.browse_number,
        item.sale_number,
        item.sale_money
      ])

      export_json_to_word({
        header,
        data,
        filename: this.language === 'zh' ? '订单分析' : 'Order analysis',
        title: this.language === 'zh' ? '订单分析报告' : 'Order Analysis Report'
      })
    }
  }
}
</script>

<style scoped>
.export-btns {
  margin-bottom: 20px;
}
</style>
