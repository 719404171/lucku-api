<template>
  <div class="app-container">
    <!-- 今日統計卡片 -->
    <div class="stat-cards mb20">
      <div class="stat-card stat-blue">
        <div class="stat-num">{{ todayCount }}</div>
        <div class="stat-label">今日領取人數</div>
      </div>
      <div class="stat-card stat-green">
        <div class="stat-num">{{ todayTotal }}</div>
        <div class="stat-label">今日發放總額</div>
      </div>
    </div>

    <div class="df aic mb20" v-if="device==='desktop'">
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('DailyBonusLog')">确定</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">搜索</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('DailyBonusLog')"></el-button>
    </div>
    <div class="df aic mb20" v-else>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('DailyBonusLog')">确定</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('DailyBonusLog')"></el-button>
    </div>

    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column" :columnAttr="columnAttr"
      @page-change="onPageChange('DailyBonusLog', $event)" @sort-change="onSortChange">
      <template #wallet_explain="{row}">
        <el-tag v-if="row.wallet_explain === '每日登錄送金幣'" size="small" type="warning">🪙 金币</el-tag>
        <el-tag v-else-if="row.wallet_explain === '每日登錄送鑽石'" size="small" type="success">💎 钻石</el-tag>
        <el-tag v-else size="small">{{row.wallet_explain}}</el-tag>
      </template>
      <template #wallet_type="{row}">
        <span v-if="row.wallet_type === 2">🪙 金币</span>
        <span v-else-if="row.wallet_type === 4">💎 钻石</span>
        <span v-else>{{row.wallet_type}}</span>
      </template>
      <template #wallet_money="{row}">
        <span :style="{ color: row.wallet_type === 2 ? '#e6a23c' : '#67c23a', fontWeight: 'bold' }">
          +{{ row.wallet_money }}
        </span>
      </template>
      <template #user_id="{row}">
        <span v-if="row.user_id">{{row.user_id}}</span>
        <span v-else style="color:#ccc">—</span>
      </template>
      <template #user_name="{row}">
        <span v-if="row.user_name">{{row.user_name}}</span>
        <span v-else style="color:#ccc">—</span>
      </template>
      <template #user_status="{row}">
        <el-tag v-if="row.user_status === 1" size="small" type="success">已通过</el-tag>
        <el-tag v-else-if="row.user_status === 0" size="small" type="warning">未验证</el-tag>
        <el-tag v-else size="small" type="danger">封禁</el-tag>
        <span v-else style="color:#ccc">—</span>
      </template>
    </ProTable>
  </div>
</template>

<script>
import paging from '@/mixins/paging'
export default {
  name: 'DailyBonusLog',
  mixins: [paging],
  data() {
    return {
      todayCount: 0,
      todayTotal: 0,
      SFC: {
        multi: { label: '用户ID/用户名/邮箱', component: 'el-input', value: '', clearable: true },
        wallet_explain: { label: '奖励类型', component: 'el-select', value: '', clearable: true, options: [
          { label: '全部', value: '' },
          { label: '金币', value: '每日登錄送金幣' },
          { label: '钻石', value: '每日登錄送鑽石' },
        ]},
      },
      column: {
        wallet_id: 'ID',
        user_id: '用户ID',
        user_name: '用户名',
        user_email: '邮箱',
        wallet_explain: '奖励类型',
        wallet_money: '数量',
        user_status: '验证状态',
        created_at: '领取时间',
      },
      columnAttr: {
        wallet_id:     { sortable: 'custom', minWidth: '60px' },
        wallet_money:  { sortable: 'custom', minWidth: '80px' },
        created_at:    { sortable: 'custom', minWidth: '160px' },
        user_email:    { minWidth: '180px' },
      },
    }
  },
  methods: {
    onSortChange({ prop, order }) {
      this.getList('DailyBonusLog', { sort: prop, order: order || 'desc' })
    },
    // 覆寫 getList 取 today_count / today_total
    async getList(name, params = {}) {
      try {
        this.isLoading = true
        const res = await this.$store.dispatch('req' + name, { page: 1, limit: 10, ...params })
        const { results: { data, count, today_count, today_total } } = res
        this.list = data
        this.page.current = params.page || 1
        this.page.size = params.limit || 10
        this.page.total = count
        this.todayCount = today_count ?? 0
        this.todayTotal = today_total ?? 0
        this.isLoading = false
      } catch (e) {
        console.log(e)
        this.isLoading = false
      }
    }
  },
  created() {
    this.getList('DailyBonusLog')
  },
}
</script>

<style lang="scss" scoped>
.stat-cards {
  display: flex;
  gap: 16px;
}
.stat-card {
  flex: 1;
  max-width: 240px;
  padding: 18px 20px;
  border-radius: 8px;
  color: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
  .stat-num { font-size: 28px; font-weight: bold; line-height: 1.2; }
  .stat-label { font-size: 13px; opacity: .85; margin-top: 6px; }
}
.stat-blue  { background: linear-gradient(135deg, #409EFF, #66b1ff); }
.stat-green { background: linear-gradient(135deg, #67C23A, #85ce61); }
</style>
