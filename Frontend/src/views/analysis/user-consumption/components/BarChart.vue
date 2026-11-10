<template>
  <div :class="className" :style="{height:height,width:width}" />
</template>

<script>
import * as echarts from 'echarts';
require('echarts/theme/macarons') // echarts theme
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
    initChart({data, aData, fData}={}) {
      this.chart = echarts.init(this.$el, 'macarons',{locale: this.$store.getters.language.toUpperCase()})

      this.chart.setOption({
        title: {text: this.$t('route.UserConsumption')},
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'cross',
            crossStyle: {
              color: '#999'
            }
          }
        },
        toolbox: {
          feature: {
            dataView: { show: true, readOnly: false },
            saveAsImage: { show: true }
          }
        },
        legend: {
          data: this.device==='desktop' ? [this.$t('analysis.ut0'), this.$t('analysis.ut1')] : []
        },
        xAxis: [
          {
            type: 'category',
            data,
            axisPointer: {
              type: 'shadow'
            }
          }
        ],
        yAxis: [
          {
            show: true,
            axisLine: {
              show: true
            },
            type: 'value',
            name: this.$t('analysis.ut0'),
            min: 0,
            axisLabel: {
              formatter: '${value}'
            }
          },
          {
            show: true,
            axisLine: {
              show: true
            },
            type: 'value',
            name: this.$t('analysis.ut1'),
            min: 0,
            axisLabel: {
              formatter: `{value} ${this.$t('analysis.sec')}`
            }
          }
        ],
        series: [
          {
            name: this.$t('analysis.ut0'),
            type: 'bar',
            tooltip: {
              valueFormatter: value => '$'+value
            },
            data: aData
          },
          {
            name: this.$t('analysis.ut1'),
            type: 'line',
            yAxisIndex: 1,
            tooltip: {
              valueFormatter: value => value + this.$t('analysis.sec')
            },
            data: fData
          }
        ]
      })
    }
  }
}
</script>
