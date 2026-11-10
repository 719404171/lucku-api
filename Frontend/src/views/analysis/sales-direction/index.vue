<template>
  <div class="app-container">
    <el-card v-loading="isLoading">
      <div class="export-btns">
        <el-button type="primary" @click="exportExcel('total')">Excel汇出</el-button>
        <el-button type="primary" @click="exportWord('total')">Word汇出</el-button>
      </div>
      <BarChart :chart-data="tsData" />
    </el-card>
    <el-card v-loading="isLoading" class="mtb30">
      <div class="export-btns">
        <el-button type="primary" @click="exportExcel('money')">Excel汇出</el-button>
        <el-button type="primary" @click="exportWord('money')">Word汇出</el-button>
      </div>
      <StackBarChart :chart-data="mData" />
    </el-card>
    <el-card v-loading="isLoading">
      <div class="export-btns">
        <el-button type="primary" @click="exportExcel('number')">Excel汇出</el-button>
        <el-button type="primary" @click="exportWord('number')">Word汇出</el-button>
      </div>
      <StackBarChart :chart-data="nData" />
    </el-card>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import { getSD } from '@/api'
import { export_json_to_excel } from '@/utils/Export2Excel'
import { export_json_to_word } from '@/utils/Export2Word'
import BarChart from './components/BarChart.vue'
import StackBarChart from './components/StackBarChart.vue'
export default {
  name: 'SalesDirection',
  components: { BarChart, StackBarChart },
  data() {
    return {
      isLoading: false,
      tsData: {},
      mData: {},
      nData: {}
    }
  },
  created() {
    this.getSaleDire()
  },
  computed: {
    ...mapGetters(['language'])
  },
  methods: {
    // 获取销售方向数据
    async getSaleDire() {
      try {
        this.isLoading = true
        const { results: { totalSales, saleData }} = await getSD()
        this.tsData = {
          data: totalSales.map(item => item.sort_number),
          xData: totalSales.map(item => item.product_sort_name)
        }

        const dates = [...new Set(saleData.map(item => item.payment_date))]

        const pds = [...new Set(saleData.map(item => item.product_name))]

        this.mData = {
          title: this.$t('analysis.sdT2'),
          xData: dates,
          sData: pds.map(name => (
            {
              name,
              type: 'bar',
              stack: 'Ad',
              emphasis: { focus: 'series' },
              data: dates.map(date => saleData.find(({ payment_date, product_name }) => payment_date === date && product_name === name)?.money || 0)
            }
          ))
        }

        this.nData = {
          title: this.$t('analysis.sdT3'),
          xData: dates,
          sData: pds.map(name => (
            {
              name,
              type: 'bar',
              stack: 'Ad',
              emphasis: { focus: 'series' },
              data: dates.map(date => saleData.find(({ payment_date, product_name }) => payment_date === date && product_name === name)?.product_number || 0)
            }
          ))
        }
      } catch (error) {

      } finally {
        this.isLoading = false
      }
    },
    exportExcel(type) {
      let header, data, filename

      switch (type) {
        case 'total':
          header = this.language === 'zh' ? ['产品类别', '销售数量'] : ['Product Category', 'Sales volumes']
          data = this.tsData.xData.map((name, index) => [
            name,
            this.tsData.data[index]
          ])
          filename = this.language === 'zh' ? '总销售分析' : 'Total sales analysis'
          break
        case 'money':
          header = [this.language === 'zh' ? '日期' : 'Date', ...this.mData.sData.map(item => item.name)]
          data = this.mData.xData.map((date, index) => [
            date,
            ...this.mData.sData.map(item => item.data[index])
          ])
          filename = this.language === 'zh' ? '销售金额分析' : 'Sales amount analysis'
          break
        case 'number':
          header = [this.language === 'zh' ? '日期' : 'Date', ...this.nData.sData.map(item => item.name)]
          data = this.nData.xData.map((date, index) => [
            date,
            ...this.nData.sData.map(item => item.data[index])
          ])
          filename = this.language === 'zh' ? '销售数量分析' : 'Sales quantity analysis'
          break
      }

      export_json_to_excel({
        header,
        data,
        filename
      })
    },
    exportWord(type) {
      let header, data, filename, title

      switch (type) {
        case 'total':
          header = this.language === 'zh' ? ['产品类别', '销售数量'] : ['Product Category', 'Sales volumes']
          data = this.tsData.xData.map((name, index) => [
            name,
            this.tsData.data[index]
          ])
          filename = this.language === 'zh' ? '总销售分析' : 'Total sales analysis'
          title = this.language === 'zh' ? '总销售分析报告' : 'Total Sales Analysis Report'
          break
        case 'money':
          header = [this.language === 'zh' ? '日期' : 'Date', ...this.mData.sData.map(item => item.name)]
          data = this.mData.xData.map((date, index) => [
            date,
            ...this.mData.sData.map(item => item.data[index])
          ])
          filename = this.language === 'zh' ? '销售金额分析' : 'Sales amount analysis'
          title = '销售金额分析报告'
          break
        case 'number':
          header = [this.language === 'zh' ? '日期' : 'Date', ...this.nData.sData.map(item => item.name)]
          data = this.nData.xData.map((date, index) => [
            date,
            ...this.nData.sData.map(item => item.data[index])
          ])
          filename = this.language === 'zh' ? '销售数量分析' : 'Sales quantity analysis'
          title = this.language === 'zh' ? '销售数量分析报告' : 'Sales Quantity Analysis Report'
          break
      }

      export_json_to_word({
        header,
        data,
        filename,
        title
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
