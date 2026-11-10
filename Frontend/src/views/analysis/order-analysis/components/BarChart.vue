<template>
  <div :class="className" :style="{height:height,width:width}" />
</template>

<script>
import * as echarts from 'echarts';
// require('echarts/theme/macarons') // echarts theme
import resize from '@/mixins/resize'
import "echarts/i18n/langZH"

const animationDuration = 6000

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
        this.initChart(val)
      }
    }
  },
  mounted() {
    this.$nextTick(() => {
      this.initChart(this.chartData)
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
    initChart({data, bData, mData, nData}={}) {
      this.chart = echarts.init(this.$el, 'macarons',{locale: this.$store.getters.language.toUpperCase()})
      const colors = ['#5470C6', '#91CC75', '#EE6666'];

      this.chart.setOption({
        title: {text:this.$t('route.OrderAnalysis')},
        color: colors,
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'cross'
          }
        },
        grid: {
          right: '20%'
        },
        toolbox: {
          feature: {
            dataView: { show: true, readOnly: false },
            saveAsImage: { show: true }
          }
        },
        legend: {
          data: this.device==='desktop' ? [this.$t('db.sales_volume'), this.$t('analysis.sale_number'), this.$t('analysis.browse')] : []
        },
        xAxis: [
          {
            type: 'category',
            axisTick: {
              alignWithLabel: true
            },
            // prettier-ignore
            data
          }
        ],
        yAxis: [
          {
            type: 'value',
            name: this.$t('analysis.sale_number'),
            position: 'right',
            alignTicks: true,
            axisLine: {
              show: true,
              lineStyle: {
                color: colors[0]
              }
            },
            axisLabel: {
              formatter: '{value}'
            }
          },
          {
            type: 'value',
            name: this.$t('analysis.browse'),
            position: 'right',
            alignTicks: true,
            offset: 80,
            axisLine: {
              show: true,
              lineStyle: {
                color: colors[1]
              }
            },
            axisLabel: {
              formatter: '{value}'
            }
          },
          {
            type: 'value',
            name: this.$t('db.sales_volume'),
            position: 'left',
            alignTicks: true,
            axisLine: {
              show: true,
              lineStyle: {
                color: colors[2]
              }
            },
            axisLabel: {
              formatter: '${value}'
            }
          }
        ],
        series: [
          {
            name: this.$t('analysis.sale_number'),
            type: 'bar',
            data: nData
          },
          {
            name: this.$t('analysis.browse'),
            type: 'bar',
            yAxisIndex: 1,
            data: bData
          },
          {
            name: this.$t('db.sales_volume'),
            type: 'line',
            yAxisIndex: 2,
            data: mData
          }
        ]
      })
    }
  }
}
</script>
