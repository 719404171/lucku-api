<template>
  <div class="app-container">
    <el-tabs v-model="tab" @tab-click="onTabChange">
      <!-- 抽卡对账 -->
      <el-tab-pane :label="$t('audit.tabDraw')" name="draw">
        <div class="df aic mb20" v-if="device === 'desktop'">
          <el-popover class="mlr10" placement="bottom" trigger="click">
            <ProForm ref="drawSearchForm" :config="SFC"></ProForm>
            <el-button type="primary" @click="onSearch('draw')">{{ $t('common.confirm') }}</el-button>
            <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
          </el-popover>
          <el-button type="primary" icon="el-icon-refresh" plain @click="loadDraw()"></el-button>
          <el-checkbox class="ml20" v-model="drawSearch.only_abnormal" @change="onOnlyAbnormalChange">{{ $t('audit.onlyAbnormal') }}</el-checkbox>
          <el-form label-suffix="：" inline class="ml20">
            <el-form-item :label="$t('audit.totalCost')">{{ drawSummary.total_money }}</el-form-item>
            <el-form-item :label="$t('audit.abnormalCount')">
              <el-tag :type="drawSummary.abnormal_count > 0 ? 'danger' : 'success'">{{ drawSummary.abnormal_count }}</el-tag>
            </el-form-item>
          </el-form>
        </div>
        <div class="df aic mb20" v-else>
          <el-popover class="mlr10" placement="bottom" trigger="click">
            <ProForm ref="drawSearchForm" :config="SFC"></ProForm>
            <el-button type="primary" @click="onSearch('draw')">{{ $t('common.confirm') }}</el-button>
            <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
          </el-popover>
          <el-button type="primary" icon="el-icon-refresh" plain @click="loadDraw()"></el-button>
          <el-checkbox class="ml20" v-model="drawSearch.only_abnormal" @change="onOnlyAbnormalChange">{{ $t('audit.onlyAbnormal') }}</el-checkbox>
        </div>

        <ProTable
          index
          border
          :is-loading="drawLoading"
          :data="drawList"
          :page="drawPage"
          :column="drawColumn"
          :column-attr="{
            created_at: { minWidth: '150px' },
            user_id: { minWidth: '80px' },
            cards_text: { minWidth: '220px' },
            audit_msg: { minWidth: '200px' },
            audit_advice: { minWidth: '280px' }
          }"
          @page-change="onPageChange('draw', $event)"
        >
          <template #pay_status="{ row }">
            <el-tag size="small" :type="Number(row.pay_status) === 1 ? 'success' : 'danger'">
              {{ Number(row.pay_status) === 1 ? $t('audit.payOk') : $t('audit.payFail') }}
            </el-tag>
          </template>
          <template #got_cards="{ row }">
            <el-tag size="small" :type="Number(row.got_cards) === 1 ? 'success' : 'danger'">
              {{ Number(row.got_cards) === 1 ? $t('audit.gotYes') : $t('audit.gotNo') }}
            </el-tag>
          </template>
          <template #audit_status="{ row }">
            <el-tag size="small" :type="Number(row.audit_status) === 1 ? 'success' : 'danger'">
              {{ Number(row.audit_status) === 1 ? $t('audit.ok') : $t('audit.abnormal') }}
            </el-tag>
          </template>
          <template #audit_msg="{ row }">
            <span :class="{ 'abnormal-text': Number(row.audit_status) !== 1 }">{{ row.audit_msg || '-' }}</span>
          </template>
          <template #audit_advice="{ row }">
            <span :class="{ 'abnormal-text': Number(row.audit_status) !== 1 }">{{ row.audit_advice || '-' }}</span>
          </template>
          <template #control="{ scope }">
            <el-link type="primary" :underline="false" @click="openBill(scope.row)">{{ $t('audit.bill') }}</el-link>
          </template>
        </ProTable>
      </el-tab-pane>

      <!-- 充值对账 -->
      <el-tab-pane :label="$t('audit.tabRecharge')" name="recharge" lazy>
        <div class="df aic mb20" v-if="device === 'desktop'">
          <el-popover class="mlr10" placement="bottom" trigger="click">
            <ProForm ref="rechargeSearchForm" :config="RFC"></ProForm>
            <el-button type="primary" @click="onSearch('recharge')">{{ $t('common.confirm') }}</el-button>
            <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
          </el-popover>
          <el-button type="primary" icon="el-icon-refresh" plain @click="loadRecharge()"></el-button>
          <el-form label-suffix="：" inline class="ml20">
            <el-form-item :label="$t('audit.totalMoney')">{{ rechargeSummary.total_money }}</el-form-item>
          </el-form>
        </div>
        <div class="df aic mb20" v-else>
          <el-popover class="mlr10" placement="bottom" trigger="click">
            <ProForm ref="rechargeSearchForm" :config="RFC"></ProForm>
            <el-button type="primary" @click="onSearch('recharge')">{{ $t('common.confirm') }}</el-button>
            <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
          </el-popover>
          <el-button type="primary" icon="el-icon-refresh" plain @click="loadRecharge()"></el-button>
        </div>

        <ProTable
          index
          border
          :is-loading="rechargeLoading"
          :data="rechargeList"
          :page="rechargePage"
          :column="rechargeColumn"
          :column-attr="{
            created_at: { minWidth: '150px' },
            user_id: { minWidth: '80px' },
            trade_no: { minWidth: '200px' },
            wonder_order_no: { minWidth: '200px' },
            audit_msg: { minWidth: '160px' }
          }"
          @page-change="onPageChange('recharge', $event)"
        >
          <template #payment_mode="{ row }">{{ $t('recharge.m' + row.payment_mode) }}</template>
          <!-- 支付流水號（我方 reference_number）：可到 Wonder 商家後台按參考號搜單 -->
          <template #trade_no="{ row }">
            <span>{{ row.trade_no }}</span>
            <el-link v-if="row.trade_no" class="ml10" type="primary" :underline="false" @click="onCopy(row.trade_no)">{{ $t('common.copy') }}</el-link>
          </template>
          <!-- Wonder 網關訂單號：複製後可直接到 Wonder 商家後台搜出該筆交易 -->
          <template #wonder_order_no="{ row }">
            <template v-if="row.payment_mode == 3 && row.wonder_order_no">
              <span>{{ row.wonder_order_no }}</span>
              <el-link class="ml10" type="primary" :underline="false" @click="onCopy(row.wonder_order_no)">{{ $t('common.copy') }}</el-link>
              <el-link class="ml10" type="primary" :underline="false" href="https://hq.wonder.app/" target="_blank">{{ $t('recharge.wonderBackend') }}</el-link>
            </template>
            <span v-else>-</span>
          </template>
          <template #audit_msg="{ row }">
            <el-tag size="small" :type="Number(row.audit_status) === 1 ? 'success' : 'danger'">
              {{ Number(row.audit_status) === 1 ? $t('audit.ok') : (row.audit_msg || $t('audit.abnormal')) }}
            </el-tag>
          </template>
        </ProTable>
      </el-tab-pane>
    </el-tabs>

    <!-- 对账单：追溯该批次的付款、发卡与账户资金变化 -->
    <el-drawer :title="$t('audit.billTitle') + (billTaskId ? ' #' + billTaskId : '')" :visible.sync="billVisible" size="72%" @closed="onBillClosed">
      <div v-loading="billLoading" class="bill-box">
        <template v-if="bill">
          <el-alert
            class="mb20"
            :closable="false"
            show-icon
            :type="Number(bill.audit.audit_status) === 1 ? 'success' : 'error'"
            :title="Number(bill.audit.audit_status) === 1 ? $t('audit.ok') : (bill.audit.msg || $t('audit.abnormal'))"
          ></el-alert>

          <!-- 账单异常时可临时封禁账号 -->
          <div class="df aic mb20">
            <span class="mr10">{{ $t('audit.banStatus') }}：</span>
            <el-tag :type="Number(bill.account.is_banned) === 1 ? 'danger' : 'success'" class="mr10">
              {{ Number(bill.account.is_banned) === 1 ? $t('audit.banned') : $t('audit.normal') }}
            </el-tag>
            <span v-if="Number(bill.account.is_banned) === 1" class="mr10 fs12">
              {{ bill.account.ban_until ? $t('audit.bannedUntil') + '：' + bill.account.ban_until : $t('audit.banForever') }}
            </span>
            <span v-if="bill.account.ban_reason" class="mr20 fs12 abnormal-text">
              {{ $t('audit.banReason') }}：{{ bill.account.ban_reason }}
            </span>
            <el-button v-if="Number(bill.account.is_banned) !== 1" type="danger" size="small" @click="openBan">
              {{ $t('audit.banAccount') }}
            </el-button>
            <el-button v-else type="success" size="small" @click="onUnban">{{ $t('audit.unbanAccount') }}</el-button>
          </div>

          <div class="fs15 fwb mb10">{{ $t('audit.basicInfo') }}</div>
          <el-descriptions class="mb20" :column="3" border size="small">
            <el-descriptions-item :label="$t('audit.taskId')">{{ bill.task.task_id }}</el-descriptions-item>
            <el-descriptions-item :label="$t('audit.time')">{{ bill.task.created_at }}</el-descriptions-item>
            <el-descriptions-item :label="$t('audit.userId')">{{ bill.task.user_id }}</el-descriptions-item>
            <el-descriptions-item :label="$t('audit.userName')">{{ bill.task.user_name }}</el-descriptions-item>
            <el-descriptions-item :label="$t('common.email')">{{ bill.task.user_email }}</el-descriptions-item>
            <el-descriptions-item :label="$t('audit.cardSort')">{{ bill.task.card_sort_name }}</el-descriptions-item>
            <el-descriptions-item :label="$t('audit.cost')">{{ bill.task.total_cost }}</el-descriptions-item>
            <el-descriptions-item :label="$t('audit.actualPay')">{{ money(bill.payment.wallet_money) }}</el-descriptions-item>
            <el-descriptions-item :label="$t('audit.diff')">
              <span :class="{ 'abnormal-text': Number(bill.audit.pay_status) !== 1 }">{{ money(bill.payment.diff) }}</span>
            </el-descriptions-item>
            <el-descriptions-item :label="$t('audit.payStatus')">
              <el-tag size="small" :type="Number(bill.audit.pay_status) === 1 ? 'success' : 'danger'">
                {{ Number(bill.audit.pay_status) === 1 ? $t('audit.payOk') : $t('audit.payFail') }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item :label="$t('audit.issuedCards')">
              <el-tag size="small" :type="Number(bill.audit.got_cards) === 1 ? 'success' : 'danger'">
                {{ Number(bill.audit.got_cards) === 1 ? $t('audit.issued') : $t('audit.notIssued') }}
              </el-tag>
              <span class="ml10 fs12">({{ $t('audit.issuedCount', { n: bill.issued_count }) }})</span>
            </el-descriptions-item>
            <el-descriptions-item :label="$t('audit.buildStatus')">{{ bill.task.status_text }}</el-descriptions-item>
          </el-descriptions>

          <div class="fs15 fwb mb10">{{ $t('audit.accountInfo') }}</div>
          <el-descriptions class="mb10" :column="3" border size="small">
            <el-descriptions-item :label="$t('audit.beforeBalance')">{{ money(bill.balance.before_draw) }}</el-descriptions-item>
            <el-descriptions-item :label="$t('audit.afterBalance')">{{ money(bill.balance.after_draw) }}</el-descriptions-item>
            <el-descriptions-item :label="$t('audit.currentBalance')">{{ money(bill.balance.current) }}</el-descriptions-item>
            <el-descriptions-item :label="$t('audit.incomeAfter')">{{ money(bill.balance.income_after) }}</el-descriptions-item>
            <el-descriptions-item :label="$t('audit.expenseAfter')">{{ money(bill.balance.expense_after) }}</el-descriptions-item>
            <el-descriptions-item :label="$t('audit.netAfter')">{{ money(bill.balance.net_after) }}</el-descriptions-item>
          </el-descriptions>
          <div class="tips mb20">{{ bill.balance.note }}</div>

          <div class="fs15 fwb mb10">{{ $t('audit.cardList') }}</div>
          <el-table class="mb20" border size="small" :data="bill.cards">
            <el-table-column prop="card_id" :label="$t('audit.cardSortId')" width="90"></el-table-column>
            <el-table-column prop="card_name" :label="$t('audit.cards')"></el-table-column>
            <el-table-column prop="card_price" :label="$t('audit.cost')" width="100"></el-table-column>
            <el-table-column :label="$t('audit.issuedCards')" width="110">
              <template slot-scope="{ row }">
                <el-tag size="small" :type="Number(row.issued) === 1 ? 'success' : 'danger'">
                  {{ Number(row.issued) === 1 ? $t('audit.issued') : $t('audit.notIssued') }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>

          <div class="fs15 fwb mb10">
            {{ $t('audit.orderList') }}
            <el-tag class="ml10" size="mini" type="info">{{ orderModeText }}</el-tag>
          </div>
          <el-table class="mb20" border size="small" :data="bill.orders.rows">
            <el-table-column prop="order_no" :label="$t('audit.orderNo')" min-width="150"></el-table-column>
            <el-table-column prop="order_money" :label="$t('audit.orderMoney')" width="110"></el-table-column>
            <el-table-column prop="order_status" :label="$t('audit.orderStatus')" width="90"></el-table-column>
            <el-table-column prop="created_at" :label="$t('audit.time')" width="150"></el-table-column>
            <el-table-column :label="$t('audit.details')" min-width="220">
              <template slot-scope="{ row }">
                <span v-for="detail in row.details" :key="detail.details_id" class="mr10">{{ detail.product_name }}（{{ detail.product_price }}）</span>
              </template>
            </el-table-column>
          </el-table>

          <div class="fs15 fwb mb10">{{ $t('audit.flow') }}</div>
          <el-table border size="small" :data="bill.timeline" :row-class-name="flowRowClass">
            <el-table-column prop="wallet_id" :label="$t('audit.walletId')" width="90"></el-table-column>
            <el-table-column prop="created_at" :label="$t('audit.time')" width="150"></el-table-column>
            <el-table-column prop="wallet_explain" :label="$t('audit.explain')" min-width="160"></el-table-column>
            <el-table-column :label="$t('audit.direction')" width="80">
              <template slot-scope="{ row }">
                <el-tag size="mini" :type="row.direction === 'in' ? 'success' : 'danger'">
                  {{ row.direction === 'in' ? $t('audit.in') : $t('audit.out') }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column :label="$t('audit.money')" width="110">
              <template slot-scope="{ row }">{{ (row.direction === 'in' ? '+' : '-') + money(row.money) }}</template>
            </el-table-column>
            <el-table-column :label="$t('audit.balanceAfter')" width="120">
              <template slot-scope="{ row }">{{ money(row.balance_after) }}</template>
            </el-table-column>
          </el-table>
        </template>
      </div>
    </el-drawer>

    <!-- 封禁账号 -->
    <el-dialog :title="$t('audit.banAccount')" :visible.sync="banVisible" width="440px" append-to-body>
      <el-form label-width="90px">
        <el-form-item :label="$t('audit.banDays')">
          <el-select v-model="banForm.ban_days" style="width: 200px">
            <el-option :label="$t('audit.banForever')" :value="0"></el-option>
            <el-option v-for="d in [1, 3, 7, 15, 30]" :key="d" :label="d + ' ' + $t('audit.dayUnit')" :value="d"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item :label="$t('audit.banReason')">
          <el-input
            v-model="banForm.ban_reason"
            type="textarea"
            :rows="3"
            maxlength="200"
            show-word-limit
            :placeholder="$t('audit.banReasonPlaceholder')"
          ></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer">
        <el-button @click="banVisible = false">{{ $t('common.cancel') }}</el-button>
        <el-button type="danger" :loading="banLoading" @click="onBan">{{ $t('common.confirm') }}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import api from '@/api'
import { drawAudit, rechargeRecord } from '@/form'
import { copyText } from '@/utils'

export default {
  name: 'AccountAudit',
  data() {
    return {
      tab: 'draw',
      SFC: drawAudit,
      RFC: rechargeRecord,
      drawList: [],
      drawPage: { current: 1, size: 10, total: 0 },
      drawLoading: false,
      drawSearch: { only_abnormal: false },
      drawSummary: { abnormal_count: 0, total_money: '0.00' },
      rechargeList: [],
      rechargePage: { current: 1, size: 10, total: 0 },
      rechargeLoading: false,
      rechargeSearch: {},
      rechargeSummary: { total_money: '0.00' },
      rechargeLoaded: false,
      billVisible: false,
      billLoading: false,
      billTaskId: 0,
      bill: null,
      banVisible: false,
      banLoading: false,
      banForm: { ban_days: 7, ban_reason: '' },
      drawColumn: {
        task_id: 'audit.taskId',
        created_at: 'audit.time',
        user_id: 'audit.userId',
        user_name: 'audit.userName',
        user_email: 'common.email',
        card_sort_name: 'audit.cardSort',
        cards_text: 'audit.cards',
        card_count: 'audit.cardCount',
        total_cost: 'audit.cost',
        pay_status: 'audit.payStatus',
        got_cards: 'audit.gotCards',
        user_balance: 'audit.balance',
        audit_status: 'audit.auditResult',
        audit_msg: 'audit.reason',
        audit_advice: 'audit.advice'
      },
      rechargeColumn: {
        id: 'recharge.id',
        created_at: 'audit.time',
        user_id: 'recharge.userId',
        user_name: 'recharge.userName',
        user_email: 'common.email',
        recharge_money: 'recharge.money',
        payment_mode: 'recharge.paymentMode',
        trade_no: 'recharge.tradeNo',
        user_balance: 'audit.balance',
        audit_msg: 'audit.auditResult'
      }
    }
  },
  created() {
    this.loadDraw()
  },
  computed: {
    orderModeText() {
      const mode = this.bill && this.bill.orders ? this.bill.orders.mode : 'none'
      return { task_id: this.$t('audit.modeTaskId'), time_window: this.$t('audit.modeTimeWindow') }[mode] || this.$t('audit.modeNone')
    }
  },
  methods: {
    // 对账单
    openBill(row) {
      this.billVisible = true
      this.billTaskId = row.task_id
      this.bill = null
      this.loadBill(row.task_id)
    },
    loadBill(taskId) {
      this.billLoading = true
      api.reqDrawBill({ task_id: taskId }).then(({ results }) => {
        this.bill = results
      }).finally(() => {
        this.billLoading = false
      })
    },
    onBillClosed() {
      this.bill = null
      this.billTaskId = 0
    },
    // 封禁账号（账单异常时临时封禁）
    openBan() {
      this.banForm = { ban_days: 7, ban_reason: '' }
      this.banVisible = true
    },
    onBan() {
      this.$confirm(this.$t('audit.banConfirm'), this.$t('common.tips'), { type: 'warning' }).then(() => {
        this.banLoading = true
        api.reqBanUser({
          user_id: this.bill.task.user_id,
          ban_days: this.banForm.ban_days,
          ban_reason: this.banForm.ban_reason
        }).then(({ results }) => {
          this.$message({ message: this.$t('common.success'), type: 'success' })
          this.applyBanInfo(results)
          this.banVisible = false
          this.loadDraw()
        }).finally(() => {
          this.banLoading = false
        })
      }).catch(() => {})
    },
    onUnban() {
      this.$confirm(this.$t('audit.unbanConfirm'), this.$t('common.tips'), { type: 'warning' }).then(() => {
        api.reqUnbanUser({ user_id: this.bill.task.user_id }).then(({ results }) => {
          this.$message({ message: this.$t('common.success'), type: 'success' })
          this.applyBanInfo(results)
          this.loadDraw()
        })
      }).catch(() => {})
    },
    applyBanInfo(info) {
      if (!this.bill || !this.bill.account || !info) return
      this.$set(this.bill.account, 'user_status', info.user_status)
      this.$set(this.bill.account, 'ban_until', info.ban_until)
      this.$set(this.bill.account, 'ban_reason', info.ban_reason)
      this.$set(this.bill.account, 'is_banned', info.is_banned)
    },
    money(value) {
      return value === null || value === undefined || value === '' ? '--' : value
    },
    onCopy(text) {
      this.$message(copyText(text)
        ? { message: this.$t('common.copySuccess'), type: 'success' }
        : { message: this.$t('common.copyFail'), type: 'warning' })
    },
    flowRowClass({ row }) {
      return Number(row.is_current) === 1 ? 'warning-row' : ''
    },
    onTabChange(tab) {
      if (tab.name === 'recharge' && !this.rechargeLoaded) {
        this.loadRecharge()
      }
    },
    // 抽卡对账
    loadDraw(params = {}) {
      const { only_abnormal, ...rest } = this.drawSearch
      const query = {
        page: this.drawPage.current,
        limit: this.drawPage.size,
        ...rest,
        ...params
      }
      if (only_abnormal) query.only_abnormal = 1
      this.drawLoading = true
      api.reqDrawAudit(query).then(({ results }) => {
        this.drawList = results.data || []
        this.drawPage.total = results.count || 0
        this.drawSummary = {
          abnormal_count: results.abnormal_count || 0,
          total_money: results.total_money || '0.00'
        }
      }).finally(() => {
        this.drawLoading = false
      })
    },
    onOnlyAbnormalChange() {
      this.drawPage.current = 1
      this.loadDraw()
    },
    // 充值对账
    loadRecharge() {
      const query = { page: this.rechargePage.current, limit: this.rechargePage.size, ...this.rechargeSearch }
      this.rechargeLoading = true
      api.reqRechargeRecord(query).then(({ results }) => {
        this.rechargeList = results.data || []
        this.rechargePage.total = results.count || 0
        this.rechargeSummary = { total_money: results.total_money || '0.00' }
        this.rechargeLoaded = true
      }).finally(() => {
        this.rechargeLoading = false
      })
    },
    onSearch(type) {
      const isDraw = type === 'draw'
      const refName = isDraw ? 'drawSearchForm' : 'rechargeSearchForm'
      this.$refs[refName].submit((params) => {
        if (isDraw) {
          this.drawSearch = { ...this.drawSearch, ...params }
          this.drawPage.current = 1
          this.loadDraw()
        } else {
          this.rechargeSearch = params
          this.rechargePage.current = 1
          this.loadRecharge()
        }
      })
    },
    onPageChange(type, { e, type: changeType }) {
      if (type === 'draw') {
        changeType === 'size' ? (this.drawPage.size = e) : (this.drawPage.current = e)
        this.loadDraw()
      } else {
        changeType === 'size' ? (this.rechargePage.size = e) : (this.rechargePage.current = e)
        this.loadRecharge()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.abnormal-text {
  color: #f56c6c;
}
.bill-box {
  padding: 0 20px 20px;
}
</style>
