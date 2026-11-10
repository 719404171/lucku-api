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
    setOptions({ title, sData, xData=[] } = {}) {
      xData = (function() {
        const data = []
        xData.forEach(item => {
          data.push(item)
        });
        return data
      }())

      this.chart.setOption({
				title: {text: title, textStyle: {fontSize:this.device==='mobile' ? 10 : 14}},
				tooltip: {
					trigger: 'axis',
					axisPointer: {
						type: 'shadow'
					}
				},
        toolbox: {
          feature: {
            dataView: { show: true, readOnly: false },
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
				legend: this.device==='mobile' ? {data: []} : {top:'5%'},
				grid: {
          top:'32%',
					left: '3%',
					right: '4%',
					bottom: '3%',
					containLabel: true
				},
				xAxis: [
					{
						type: 'category',
            splitLine: {
              show: false
            },
            axisTick: {
              show: false
            },
            splitArea: {
              show: false
            },
            axisLabel: {
              interval: 0
            },
						data: xData
					}
				],
				yAxis: [
					{
						type: 'value',
            show: true,
            axisLine: {
              show: true
            },
            splitLine: {
              show: false
            },
            axisTick: {
              show: false
            },
            axisLabel: {
              interval: 0
            },
            splitArea: {
              show: false
            }
					}
				],
        dataZoom: [{
          show: true,
          height: 30,
          xAxisIndex: [
            0
          ],
          bottom: 0,
          start: 0,
          end: 50,
          handleIcon: 'path://M306.1,413c0,2.2-1.8,4-4,4h-59.8c-2.2,0-4-1.8-4-4V200.8c0-2.2,1.8-4,4-4h59.8c2.2,0,4,1.8,4,4V413z',
          handleSize: '110%',
          handleStyle: {color: '#d3dee5'},
          textStyle: { color: '#000' },
          borderColor: '#90979c'
        }, {
          type: 'inside',
          show: true,
          height: 15,
          start: 1,
          end: 35
        }],
				series: sData
			})
    },
    exportToExcel() {
      const header = ['日期', ...this.chartData.sData.map(item => item.name)]
      const data = this.chartData.xData.map((date, index) => {
        return [
          date,
          ...this.chartData.sData.map(series => series.data[index])
        ]
      })
      
      import('@/utils/Export2Excel').then(excel => {
        excel.export_json_to_excel({
          header,
          data,
          filename: this.chartData.title,
          autoWidth: true,
          bookType: 'xlsx'
        })
      })
    }
  }
}
</script>
