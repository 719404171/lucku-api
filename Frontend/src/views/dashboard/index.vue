<template>
  <div class="dashboard-container">
    <div>
      <el-row :gutter="40">
        <el-col :span="device==='desktop' ? 6 : 24" v-for="(info, key) in labs" :key="key">
          <el-card class="cp mb40">
            <div class="plain" :class="{disabled: key==='coupon'}" @click="toggle(key, info.title)">
              <i class="icon" :class="info.icon" :style="{color: info.color}"></i>
              <el-statistic :title="$t(info.title)">
                <template slot="formatter"> {{plains[key] && plains[key].count || 0}} </template>
              </el-statistic>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
    <el-row v-loading="loading" style="background:#fff;padding:16px 16px 0;margin-bottom:32px;">
      <line-chart :chart-data="lineChartData" />
    </el-row>
    <el-button type="primary" @click="showAsk=true">Ask</el-button>
    <el-button type="primary" @click="showFile=true">File</el-button>
    <ProAsk :show.sync="showAsk" height="400px" pop></ProAsk>
    <ProFile :show.sync="showFile"></ProFile>
  </div>
</template>

<script>
import LineChart from './components/LineChart'

import {getHomeData} from '@/api'

export default {
  name: 'Dashboard',
  components: {LineChart},
  data() {
    return {
      labs: {
        user: {
          title: 'db.user',
          icon: 'el-icon-user-solid',
          color: '#40C9C6'
        },
        order: {
          title: 'db.order',
          icon: 'el-icon-s-order',
          color: '#36A3F7'
        },
        sales_volume: {
          title: 'db.sales_volume',
          icon: 'el-icon-s-finance',
          color: '#ef7a82'
        },
        user_message: {
          title: 'db.user_message',
          icon: 'el-icon-s-comment',
          color: '#b0a4e3'
        }
      },
      plains: {},
      lineChartData: {},
      loading: false,
      showAsk: false,
      showFile: false
    }
  },
  created() {
    this.getData()
  },
  methods: {
    async getData() {
      try {
        this.loading = true
        const data = await getHomeData()
        this.plains = data
        this.lineChartData = {title: {text: this.$t('db.user'), left: '30px'}, data: this.plains.user.data}
      } catch (error) {
        
      } finally {
        this.loading = false
      }
    },
    toggle(key, text) {
      this.lineChartData = {title: {text: this.$t(text), left: '30px'}, data: this.plains[key].data}
    }
  }
}
</script>

<style lang="scss" scoped>
.dashboard {
  &-container {
    margin: 30px;
  }
}
.plain {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 0;
  .icon {
    font-size: 30px;
  }
}
</style>
