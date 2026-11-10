<template>
  <div class="app-container">
    <div class="df mb20" v-if="device === 'desktop'">
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC" />
        <el-button type="primary" @click="search">{{ $t('common.confirm') }}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="refresh"></el-button>
      <el-button type="success" icon="el-icon-download" :loading="isExporting" @click="exportExcel">{{ $t('multiDraw.exportExcel') }}</el-button>
      <el-switch class="ml20" v-model="autoRefresh" :active-text="$t('multiDraw.autoRefresh')" />
    </div>
    <div class="df mb20" v-else>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC" />
        <el-button type="primary" @click="search">{{ $t('common.confirm') }}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="refresh"></el-button>
      <el-button type="success" icon="el-icon-download" :loading="isExporting" @click="exportExcel"></el-button>
      <el-switch class="ml10" v-model="autoRefresh" />
    </div>

    <el-card class="mb20">
      <div class="alert-legend">
        <el-tag type="warning" size="small">{{ $t('multiDraw.alertMulti') }}</el-tag>
        <span class="legend-tip">{{ $t('multiDraw.alertMultiTip') }}</span>
        <el-tag type="danger" size="small" class="ml20">{{ $t('multiDraw.alertOversell') }}</el-tag>
        <span class="legend-tip">{{ $t('multiDraw.alertOversellTip') }}</span>
      </div>
      <MultiDrawChart v-loading="isLoading" :chart-data="chartData" />
    </el-card>

    <ProTable
      index
      border
      :is-loading="isLoading"
      :data="list"
      :page="page"
      :column="column"
      :column-attr="{
        card_id: { minWidth: '80px' },
        card_name: { minWidth: '220px' },
        card_sort_name: { minWidth: '140px' },
        users: { minWidth: '220px' },
        first_draw_at: { minWidth: '160px' },
        last_draw_at: { minWidth: '160px' }
      }"
      @page-change="onPageChange('MultiDrawCheck', $event)"
    >
      <template #card_name="{ row }">
        <div class="df aic">
          <ProImage v-if="row.card_img" :src="row.card_img" :size="40" />
          <span class="ml5">{{ row.card_name || '--' }}</span>
        </div>
      </template>
      <template #users="{ row }">
        <el-tag
          v-for="user in (row.users || []).slice(0, 3)"
          :key="user.user_id"
          size="mini"
          style="margin: 2px 4px 2px 0"
        >{{ (user.user_name || '--') }} ×{{ user.draw_count }}</el-tag>
        <span v-if="(row.users || []).length > 3" style="color: #909399">
          {{ $t('multiDraw.moreUsers').replace('{n}', (row.users || []).length - 3) }}
        </span>
      </template>
      <template #card_stock="{ row }">
        <span :style="row.card_stock < 0 ? 'color:#F56C6C;font-weight:600' : ''">{{ row.card_stock === null ? '--' : row.card_stock }}</span>
      </template>
      <template #alert_level="{ row }">
        <el-tag :type="row.alert_level === 2 ? 'danger' : 'warning'" size="small">
          {{ row.alert_level === 2 ? $t('multiDraw.alertOversell') : $t('multiDraw.alertMulti') }}
        </el-tag>
      </template>
      <template #control="{ scope }">
        <el-link class="row-control-item" :class="$store.state.app.size" type="primary" :underline="false" @click="openDetail(scope.row)">{{ $t('multiDraw.view') }}</el-link>
      </template>
    </ProTable>

    <el-dialog
      :title="detailTitle"
      :visible.sync="showDetail"
      :width="device === 'desktop' ? '1080px' : '100%'"
      :close-on-click-modal="false"
    >
      <div v-loading="detailLoading" element-loading-text="Loading...">
        <el-descriptions :column="device === 'desktop' ? 4 : 1" border size="small" class="mb20">
          <el-descriptions-item :label="$t('multiDraw.cardId')">{{ detail.card.card_id || '--' }}</el-descriptions-item>
          <el-descriptions-item :label="$t('multiDraw.cardName')">{{ detail.card.card_name || '--' }}</el-descriptions-item>
          <el-descriptions-item :label="$t('multiDraw.cardSort')">{{ detail.card.card_sort_name || '--' }}</el-descriptions-item>
          <el-descriptions-item :label="$t('multiDraw.stock')">
            <span :style="detail.card.card_stock < 0 ? 'color:#F56C6C;font-weight:600' : ''">{{ detail.card.card_stock === null ? '--' : detail.card.card_stock }}</span>
          </el-descriptions-item>
          <el-descriptions-item :label="$t('multiDraw.drawCount')">{{ detail.draw_count || 0 }}</el-descriptions-item>
          <el-descriptions-item :label="$t('multiDraw.userCount')">{{ detail.user_count || 0 }}</el-descriptions-item>
          <el-descriptions-item :label="$t('multiDraw.alert')">
            <el-tag :type="detail.card.alert_level === 2 ? 'danger' : 'warning'" size="small">
              {{ detail.card.alert_level === 2 ? $t('multiDraw.alertOversell') : $t('multiDraw.alertMulti') }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>

        <el-alert
          v-if="detail.is_truncated === 1"
          type="warning"
          show-icon
          :closable="false"
          class="mb20"
          :title="$t('multiDraw.truncatedWarning').replace('{n}', detail.detail_limit)"
        />

        <div class="detail-title">{{ $t('multiDraw.detail') }}</div>
        <el-table :data="detail.data || []" border size="mini" max-height="420">
          <el-table-column type="index" label="#" width="50" />
          <el-table-column prop="created_at" :label="$t('multiDraw.drawTime')" width="170" />
          <el-table-column prop="user_id" :label="$t('multiDraw.userId')" width="90" />
          <el-table-column prop="user_name" :label="$t('multiDraw.userName')" min-width="140" />
          <el-table-column prop="user_email" :label="$t('common.email')" min-width="200" />
          <el-table-column prop="order_no" :label="$t('drawDetail.orderNo')" min-width="190" />
          <el-table-column prop="draw_task_id" :label="$t('multiDraw.taskId')" width="100" />
          <el-table-column prop="product_price" :label="$t('drawDetail.cardPrice')" width="100" />
        </el-table>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import api from '@/api'
import { mapGetters } from 'vuex'
import { multiDrawCheck } from '@/form'
import paging from '@/mixins/paging'
import MultiDrawChart from './components/MultiDrawChart.vue'
import { export_json_to_excel } from '@/utils/Export2Excel'

export default {
  name: 'MultiDrawCheck',
  components: { MultiDrawChart },
  mixins: [paging],
  data() {
    return {
      SFC: multiDrawCheck,
      column: {
        card_id: 'multiDraw.cardId',
        card_name: 'multiDraw.cardName',
        card_sort_name: 'multiDraw.cardSort',
        draw_count: 'multiDraw.drawCount',
        user_count: 'multiDraw.userCount',
        users: 'multiDraw.users',
        card_stock: 'multiDraw.stock',
        alert_level: 'multiDraw.alert',
        first_draw_at: 'multiDraw.firstTime',
        last_draw_at: 'multiDraw.lastTime'
      },
      chartData: { data: [], aData: [], fData: [] },
      autoRefresh: true,
      refreshTimer: null,
      // 非空時後端寫入操作日誌（僅用戶主動查詢/刷新時記錄，翻頁與自動刷新不記）
      auditAction: '',
      isExporting: false,
      showDetail: false,
      detailLoading: false,
      detail: { card: {}, draw_count: 0, user_count: 0, data: [] }
    }
  },
  computed: {
    ...mapGetters(['language']),
    detailTitle() {
      return this.detail.card && this.detail.card.card_id
        ? this.$t('multiDraw.detailTitle').replace('{id}', this.detail.card.card_id).replace('{name}', this.detail.card.card_name || '')
        : this.$t('multiDraw.detailTitle').replace('{id}', '').replace('{name}', '')
    }
  },
  created() {
    this.auditAction = '查詢'
    this.getList('MultiDrawCheck')
    this.startAutoRefresh()
  },
  beforeDestroy() {
    this.stopAutoRefresh()
  },
  watch: {
    autoRefresh(val) {
      val ? this.startAutoRefresh() : this.stopAutoRefresh()
    }
  },
  methods: {
    async getList(name, { page = 1, limit = 10, ...elseParams } = {}) {
      this.isLoading = true
      try {
        const { results: { data, count, chart } } = await api['req' + name]({ page, limit, ...elseParams, log_action: this.auditAction })
        this.list = data
        this.page.current = page
        this.page.size = limit
        this.page.total = count
        this.chartData = chart || { data: [], aData: [], fData: [] }
      } catch (error) {
        console.log(error)
      } finally {
        this.isLoading = false
        this.auditAction = ''
      }
    },
    // 搜索（記錄操作日誌）
    search() {
      this.auditAction = '查詢'
      this.onSearch('MultiDrawCheck')
    },
    // 刷新（記錄操作日誌）
    refresh() {
      this.auditAction = '查詢'
      this.getList('MultiDrawCheck', { ...this.searchForm })
    },
    startAutoRefresh() {
      this.stopAutoRefresh()
      if (!this.autoRefresh) return
      this.refreshTimer = setInterval(() => {
        this.getList('MultiDrawCheck', { ...this.searchForm })
      }, 30000)
    },
    stopAutoRefresh() {
      if (this.refreshTimer) {
        clearInterval(this.refreshTimer)
        this.refreshTimer = null
      }
    },
    async openDetail(row) {
      this.showDetail = true
      this.detailLoading = true
      this.detail = { card: {}, draw_count: 0, user_count: 0, data: [] }
      try {
        const { results } = await api.reqMultiDrawDetail({ card_id: row.card_id, ...this.searchForm })
        this.detail = results || { card: {}, draw_count: 0, user_count: 0, data: [] }
      } catch (error) {
        console.log(error)
      } finally {
        this.detailLoading = false
      }
    },
    async exportExcel() {
      this.isExporting = true
      try {
        const { results: { data } } = await api.reqMultiDrawCheck({ ...this.searchForm, page: 1, limit: 5000, is_export: 1 })
        const header = this.language === 'zh'
          ? ['卡牌ID', '卡牌名称', '卡池', '抽取次数', '抽取用户数', '当前库存', '预警', '首次抽取时间', '最近抽取时间', '抽取用户']
          : ['Card ID', 'Card Name', 'Card Pack', 'Draw Count', 'User Count', 'Stock', 'Alert', 'First Draw Time', 'Last Draw Time', 'Drawn By']
        const rows = (data || []).map(item => [
          item.card_id,
          item.card_name,
          item.card_sort_name,
          item.draw_count,
          item.user_count,
          item.card_stock === null ? '' : item.card_stock,
          item.alert_level === 2 ? this.$t('multiDraw.alertOversell') : this.$t('multiDraw.alertMulti'),
          item.first_draw_at,
          item.last_draw_at,
          (item.users || []).map(user => (user.user_name || '--') + '(ID:' + user.user_id + ' ×' + user.draw_count + ')').join('; ')
        ])
        export_json_to_excel({ header, data: rows, filename: this.$t('multiDraw.fileName') })
      } catch (error) {
        console.log(error)
      } finally {
        this.isExporting = false
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.alert-legend {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  margin-bottom: 10px;
  .legend-tip {
    margin-left: 6px;
    color: #909399;
    font-size: 12px;
  }
}
.detail-title {
  margin-bottom: 10px;
  font-weight: 600;
}
</style>
