<template>
  <div :class="className" :style="{height:height,width:width}" />
</template>

<script>
import * as echarts from 'echarts';
require('echarts/theme/macarons') // echarts theme
import resize from '@/mixins/resize'
import "echarts/i18n/langZH"

export default {
  mixins: [resize],
  props: {
    className: {
      type: String,
      default: 'chart'
    },
    width: {
      type: String,
      default: '100%'
    },
    height: {
      type: String,
      default: '500px'
    },
    autoResize: {
      type: Boolean,
      default: true
    },
    chartData: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      chart: null
    }
  },
  watch: {
    chartData: {
      deep: true,
      handler(val) {
        this.setOptions(val)
      }
    }
  },
  mounted() {
    this.$nextTick(() => {
      this.initChart()
    })
  },
  beforeDestroy() {
    if (!this.chart) {
      return
    }
    this.chart.dispose()
    this.chart = null
  },
  methods: {
    initChart() {
      this.chart = echarts.init(this.$el, 'macarons',{locale: this.$store.getters.language.toUpperCase()})
      this.setOptions(this.chartData)
    },
    setOptions({ data, xData } = {}) {
      this.chart.setOption({
        title: {text: this.$t('analysis.sdT1'), textStyle: {fontSize:this.device==='mobile' ? 10 : 14}},
        xAxis: {
          type: 'category',
          data: xData
        },
        grid: {
          left: 10,
          right: 10,
          bottom: 20,
          top: 30,
          containLabel: true
        },
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'cross'
          },
          padding: [5, 10]
        },
        toolbox: {
          feature: this.device==='mobile' ? {
            saveAsImage: { show: true },
            myExportExcel: {
              show: true,
              title: '导出Excel',
              icon: 'path://M4.7,22.9L29.3,45.5L54.7,23.4M4.6,43.6L4.6,58L53.8,58L53.8,43.6M29.2,45.1L29.2,0',
              onclick: () => {
                this.exportToExcel()
              }
            }
          } : {
            dataView: { show: true, readOnly: false },
            magicType: { show: true, type: ['line', 'bar'] },
            saveAsImage: { show: true },
            myExportExcel: {
              show: true,
              title: '导出Excel',
              icon: 'path://M4.7,22.9L29.3,45.5L54.7,23.4M4.6,43.6L4.6,58L53.8,58L53.8,43.6M29.2,45.1L29.2,0',
              onclick: () => {
                this.exportToExcel()
              }
            }
          }
        },
        yAxis: {
          type: 'value',
          show: true,
          axisLine: {
            show: true
          },
          axisTick: {
            show: false
          }
        },
        series: [
          {
            data,
            type: 'bar'
          }
        ]
      })
    },
    exportToExcel() {
      const header = ['类别', '数量']
      const data = this.chartData.xData.map((item, index) => [
        item,
        this.chartData.data[index]
      ])
      
      import('@/utils/Export2Excel').then(excel => {
        excel.export_json_to_excel({
          header,
          data,
          filename: this.$t('analysis.sdT1'),
          autoWidth: true,
          bookType: 'xlsx'
        })
      })
    }
  }
}
</script>
