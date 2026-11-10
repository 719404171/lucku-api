<template>
  <div :class="className" :style="{height:height,width:width}" />
</template>

<script>
import * as echarts from 'echarts'
require('echarts/theme/macarons') // echarts theme
import resize from '@/mixins/resize'
import 'echarts/i18n/langZH'

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
      default: '420px'
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
    initChart({ data = [], aData = [], fData = [] } = {}) {
      if (!this.chart) {
        this.chart = echarts.init(this.$el, 'macarons', { locale: this.$store.getters.language.toUpperCase() })
      }

      this.chart.setOption({
        title: { text: this.$t('multiDraw.chartTitle') },
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'shadow'
          }
        },
        toolbox: {
          feature: {
            dataView: { show: true, readOnly: false },
            saveAsImage: { show: true }
          }
        },
        grid: {
          left: '3%',
          right: '4%',
          bottom: this.device === 'desktop' ? '3%' : '12%',
          containLabel: true
        },
        legend: {
          data: this.device === 'desktop' ? [this.$t('multiDraw.userCount'), this.$t('multiDraw.drawCount')] : []
        },
        xAxis: [
          {
            type: 'category',
            data,
            axisLabel: {
              interval: 0,
              rotate: this.device === 'desktop' ? 20 : 45,
              formatter: value => (value && value.length > 16 ? value.substr(0, 16) + '…' : value)
            },
            axisPointer: {
              type: 'shadow'
            }
          }
        ],
        yAxis: [
          {
            show: true,
            type: 'value',
            name: this.$t('multiDraw.times'),
            min: 0,
            minInterval: 1
          }
        ],
        series: [
          {
            name: this.$t('multiDraw.userCount'),
            type: 'bar',
            barMaxWidth: 36,
            label: { show: true, position: 'top' },
            itemStyle: { color: '#E6A23C' },
            data: aData
          },
          {
            name: this.$t('multiDraw.drawCount'),
            type: 'bar',
            barMaxWidth: 36,
            label: { show: true, position: 'top' },
            itemStyle: { color: '#409EFF' },
            data: fData
          }
        ]
      })
    }
  }
}
</script>
