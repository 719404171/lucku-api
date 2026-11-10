<template>
  <div class="app-container">
    <div class="pb20">
      <el-button
        v-if="nodePower.includes('edit')"
        type="warning"
        icon="el-icon-refresh-left"
        :disabled="!selectedRows.length"
        :loading="restoring"
        @click="onRestore()"
      >批量恢復（{{ selectedRows.length }}）</el-button>
    </div>

    <el-form inline class="pb10">
      <el-form-item label="用戶">
        <el-input v-model="filters.keyword" placeholder="用戶名 / 郵箱" clearable class="search-input" @keyup.enter.native="onSearch" />
      </el-form-item>
      <el-form-item label="卡牌名">
        <el-input v-model="filters.card_name" placeholder="卡牌名稱" clearable class="search-input" @keyup.enter.native="onSearch" />
      </el-form-item>
      <el-form-item label="價格區間">
        <el-input-number v-model="filters.price_min" :min="0" :controls="false" placeholder="最低" style="width:110px" />
        <span class="ml5 mr5">-</span>
        <el-input-number v-model="filters.price_max" :min="0" :controls="false" placeholder="最高" style="width:110px" />
      </el-form-item>
      <el-form-item label="兌換時間">
        <el-date-picker
          v-model="dateRange"
          type="daterange"
          value-format="yyyy-MM-dd"
          start-placeholder="開始日期"
          end-placeholder="結束日期"
        />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" icon="el-icon-search" @click="onSearch">搜索</el-button>
        <el-button icon="el-icon-refresh" plain @click="onReset" />
      </el-form-item>
    </el-form>

    <ProTable
      selection
      border
      :is-loading="isLoading"
      :data="list"
      :page="page"
      :column="column"
      :column-attr="{ product_name: { minWidth: '220px' }, order_no: { minWidth: '170px' }, user_name: { minWidth: '120px' } }"
      @page-change="onPageChange('ExchangedCards', $event, buildParams())"
      @selection-change="onSelectionChange"
    >
      <template #product_name="{ row }">
        <div style="display:flex;align-items:center;gap:8px">
          <el-image v-if="row.product_img" :src="row.product_img" style="width:40px;height:40px" fit="contain" />
          <span>{{ row.product_name }}</span>
        </div>
      </template>
      <template #restore_amount="{ row }">{{ row.restore_amount }}</template>
      <template #user_coin="{ row }">
        <el-tag :type="row.user_coin >= row.restore_amount ? 'success' : 'danger'" size="small">{{ row.user_coin }}</el-tag>
      </template>
      <template #handle="{ row }">
        <el-button
          v-if="nodePower.includes('edit')"
          type="warning"
          size="mini"
          :disabled="row.user_coin < row.restore_amount"
          @click="onRestore(row)"
        >恢復</el-button>
      </template>
    </ProTable>
  </div>
</template>

<script>
import paging from '@/mixins/paging'
import { restoreExchangedCards } from '@/api'

export default {
  name: 'ExchangedCards',
  mixins: [paging],
  data() {
    return {
      filters: { keyword: '', card_name: '', price_min: undefined, price_max: undefined },
      dateRange: null,
      selectedRows: [],
      restoring: false,
      column: {
        details_id: 'ID',
        order_no: '訂單號',
        user_name: '用戶名',
        user_email: '郵箱',
        product_name: '卡牌',
        restore_amount: '兌換金額',
        user_coin: '當前點數',
        exchanged_at: '兌換時間',
        handle: '操作'
      }
    }
  },
  created() {
    this.getList('ExchangedCards')
  },
  methods: {
    buildParams() {
      const params = { ...this.filters }
      if (this.dateRange && this.dateRange.length === 2) {
        params.start_time = this.dateRange[0]
        params.end_time = this.dateRange[1]
      }
      return params
    },
    onSearch() {
      this.getList('ExchangedCards', this.setSearchForm(this.buildParams()))
    },
    onReset() {
      this.filters = { keyword: '', card_name: '', price_min: undefined, price_max: undefined }
      this.dateRange = null
      this.searchForm = {}
      this.getList('ExchangedCards')
    },
    onSelectionChange(rows) {
      this.selectedRows = rows
    },
    onRestore(row) {
      const rows = row ? [row] : this.selectedRows
      if (!rows.length) return
      const lack = rows.filter(r => r.user_coin < r.restore_amount)
      if (lack.length) {
        this.$message.error(`有 ${lack.length} 張卡片所屬用戶點數不足，無法恢復`)
        return
      }
      const total = rows.reduce((s, r) => s + Number(r.restore_amount), 0)
      this.$confirm(`確認恢復 ${rows.length} 張卡片？將從用戶賬號扣除共 ${total} 點數，卡片回到背包「未處理」狀態。`, '恢復確認', { type: 'warning' })
        .then(async () => {
          this.restoring = true
          try {
            await restoreExchangedCards({ details_ids: rows.map(r => r.details_id) })
            this.$message.success('恢復成功')
            this.getList('ExchangedCards', { page: this.page.current, limit: this.page.size, ...this.searchForm })
          } finally {
            this.restoring = false
          }
        })
        .catch(() => {})
    }
  }
}
</script>
