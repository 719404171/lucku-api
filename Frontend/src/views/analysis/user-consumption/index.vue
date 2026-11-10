<template>
  <div class="app-container">
    <div class="mb20">
      <el-popover class="mr10" placement="bottom" trigger="click" :width="device==='mobile' ? '320' : ''">
        <ProForm ref="searchForm" :config="SFC" />
        <el-button type="primary" @click="onSearch('UA', Object.assign({type}, device==='mobile' ? {limit:3} : {}))">{{ $t('common.confirm') }}</el-button>
        <el-button v-if="device==='desktop'" slot="reference" type="primary" icon="el-icon-search">{{ $t('common.search') }}</el-button>
        <el-button v-else slot="reference" type="primary" icon="el-icon-search" />
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'UA', Object.assign({type}, device==='mobile' ? {limit:3} : {}))" />
      <el-radio-group v-model="type" class="ml10"><el-radio-button v-for="(i, index) in 2" :key="index" :label="index">{{ $t('analysis.ut'+index) }}</el-radio-button></el-radio-group>
    </div>

    <el-card v-loading="isLoading" class="mb30">
      <div class="export-btns">
        <el-button type="primary" @click="exportExcel">Excel汇出</el-button>
        <el-button type="primary" @click="exportWord">Word汇出</el-button>
      </div>
      <BarChart :chart-data="userCharData" />
    </el-card>

    <!--  -->
    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column" @page-change="onPageChange('UA', $event, {type})" />

  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import { userCons } from '@/form'
import paging from '@/mixins/paging'
import BarChart from './components/BarChart.vue'
import { export_json_to_excel } from '@/utils/Export2Excel'
import { export_json_to_word } from '@/utils/Export2Word'

export default {
  name: 'UserConsumption',
  components: { BarChart },
  mixins: [paging],
  data() {
    return {
      SFC: userCons,
      type: 0,
      column: {
        user_name: 'user.name',
        member_name: 'analysis.level',
        order_money: 'analysis.ut0',
        order_number: 'analysis.ut1'
      },
      userCharData: { data: [], aData: [], fData: [] }
    }
  },
  created() {
    this.getList('UA', Object.assign({ type: '0' }, this.device === 'mobile' ? { limit: 3 } : {}))
  },
  computed: {
    ...mapGetters(['language'])
  },
  watch: {
    type(type) {
      this.getList('UA', Object.assign({ type }, this.device === 'mobile' ? { limit: 3 } : {}))
    },
    list(val) {
      if (val.length) {
        this.userCharData = {
          data: val.map(item => item.user_name),
          aData: val.map(item => item.order_money),
          fData: val.map(item => item.order_number)
        }
      }
    }
  },
  methods: {
    exportExcel() {
      const header = this.language === 'zh' ? ['用户名称', '会员等级', this.type === 0 ? '消费金额' : '消费次数'] : ['Username', 'Membership level', this.type === 0 ? 'Consumption amount' : 'Consumption frequency']
      const data = this.list.map(item => [
        item.user_name,
        item.member_name,
        this.type === 0 ? item.order_money : item.order_number
      ])

      export_json_to_excel({
        header,
        data,
        filename: this.language === 'zh' ? (this.type === 0 ? '用户消费金额分析' : '用户消费次数分析') : (this.type === 0 ? 'Analysis of User Consumption Amount' : 'Analysis of User Consumption Frequency')
      })
    },

    exportWord() {
      const header = this.language === 'zh' ? ['用户名称', '会员等级', this.type === 0 ? '消费金额' : '消费次数'] : ['Username', 'Membership level', this.type === 0 ? 'Consumption amount' : 'Consumption frequency']
      const data = this.list.map(item => [
        item.user_name,
        item.member_name,
        this.type === 0 ? item.order_money : item.order_number
      ])

      export_json_to_word({
        header,
        data,
        filename: this.language === 'zh' ? (this.type === 0 ? '用户消费金额分析' : '用户消费次数分析') : (this.type === 0 ? 'Analysis of User Consumption Amount' : 'Analysis of User Consumption Frequency'),
        title: this.language === 'zh' ? (this.type === 0 ? '用户消费金额分析报告' : '用户消费次数分析报告') : (this.type === 0 ? 'User Consumption Amount Analysis Report' : 'User Consumption Frequency Analysis Report')
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
