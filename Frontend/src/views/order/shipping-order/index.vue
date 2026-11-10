<template>
  <div class="app-container">
    <div class="pb20">
      <el-button v-if="nodePower.includes('del')" type="danger" icon="el-icon-delete" :disabled="!delIds.length" @click="del">{{ $t('common.deletes') }}</el-button>
      <el-input
        v-model="searchOrderNo"
        :placeholder="$t('order.order_no')"
        class="mr10 search-input"
        clearable
        @keyup.enter.native="getList('Orders', { ...getExtraParams(), order_no: searchOrderNo, keyword: searchKeyword })"
        @clear="getList('Orders', getExtraParams())"
      >
        <el-button slot="append" icon="el-icon-search" @click="getList('Orders', { ...getExtraParams(), order_no: searchOrderNo, keyword: searchKeyword })" />
      </el-input>
      <el-input
        v-model="searchKeyword"
        placeholder="用户名 / 手机 / 邮箱"
        class="mr10 search-input"
        clearable
        @keyup.enter.native="getList('Orders', { ...getExtraParams(), order_no: searchOrderNo, keyword: searchKeyword })"
        @clear="getList('Orders', { ...getExtraParams(), order_no: searchOrderNo, keyword: '' })"
      >
        <el-button slot="append" icon="el-icon-search" @click="getList('Orders', { ...getExtraParams(), order_no: searchOrderNo, keyword: searchKeyword })" />
      </el-input>
      <el-button type="primary" icon="el-icon-refresh" plain @click="resetSearch" />
      <el-radio-group v-model="subType" class="ml10" @change="toggleSubType">
        <el-radio-button :label="2">{{ $t('order.subType3') }}</el-radio-button>
        <el-radio-button :label="3">{{ $t('order.subType4') }}</el-radio-button>
      </el-radio-group>
    </div>
    <el-form label-suffix="：" inline>
      <el-form-item v-for="(item, index) in sales" :key="index" :label="$t('order.sales' + index)">{{ item }}</el-form-item>
    </el-form>

    <ProTable
      index
      selection
      border
      :is-loading="isLoading"
      :data="list"
      :page="page"
      :column="column"
      :column-attr="{
        logistics_status: { minWidth: '100px' }
      }"
      @page-change="onPageChange('Orders', $event, getExtraParams())"
      @selection-change="onSelectionChange"
    >
      <template #order_details="{ row }">
        <ProImage
          v-if="row.card_order_details && row.card_order_details.length"
          :src="row.card_order_details.map(({ product_img }) => product_img)"
          :title="row.card_order_details.map(({ product_name }) => product_name).join('\n')"
        />
        <div v-else>--</div>
      </template>
      <template #delivery_name="{ row }">{{ row.order_type === 1 && row.delivery_name ? row.delivery_name : row.user_name }}</template>
      <template #logistics_status="{ row }">
        <template v-if="row.card_order_details && row.card_order_details.length">
          <el-tag v-if="row.logistics_status === 1" type="success" size="small">{{ $t('order.cardShipped') }}</el-tag>
          <el-tag v-else-if="row.card_order_details.every(d => d.exchange_status === 0)" type="info" size="small">{{ $t('order.cardUnexchanged') }}</el-tag>
          <el-tag v-else-if="row.card_order_details.some(d => d.exchange_status === 2)" type="warning" size="small">{{ $t('order.cardShipping') }}</el-tag>
          <el-tag v-else-if="row.card_order_details.some(d => d.exchange_status === 1)" type="success" size="small">{{ $t('order.cardExchanged') }}</el-tag>
          <el-tag v-else type="info" size="small">{{ $t('order.cardUnexchanged') }}</el-tag>
        </template>
        <span v-else>--</span>
      </template>
      <template #payment_status="{ row }">
        <el-tag :type="paymentStatusType(row.payment_status)">{{ $t('order.ps' + row.payment_status) }}</el-tag>
      </template>
      <template #payment_at="{ row }">
        <div v-if="$route.query.order === 'true'" class="df">
          <el-date-picker
            v-model="row._payment_at_copy"
            :placeholder="$t('order.payat_err')"
            value-format="yyyy-MM-dd HH:mm:ss"
            type="datetime"
            :disabled="row.payment_status < 3"
          />
          <el-button type="primary" :loading="isLoadPayat && curId === row.order_id" :disabled="row.payment_status < 3" @click="updatePayat(row)">{{ $t('common.confirm') }}</el-button>
        </div>
        <span v-else>{{ row.created_at || '--' }}</span>
      </template>
      <template #control="{ scope: { row } }">
        <el-link v-if="nodePower.includes('view')" class="row-control-item" :class="$store.state.app.size" type="primary" :underline="false" @click="view(row)">{{ $t('common.view') }}</el-link>
        <el-dropdown @command="handleCommand($event, row)">
          <el-link class="row-control-item" :class="$store.state.app.size" type="primary" :underline="false">{{ $t('common.more') }}<i class="el-icon-arrow-down el-icon--right" /></el-link>
          <el-dropdown-menu slot="dropdown">
            <el-dropdown-item command="getRecord">{{ $t('order.control_record') }}</el-dropdown-item>
            <el-dropdown-item v-if="nodePower.includes('pay')" command="pay" :disabled="row.payment_status >= 3">{{ $t('order.pay') }}</el-dropdown-item>
            <el-dropdown-item v-if="nodePower.includes('download')" command="rai">{{ $t('order.' + (row.payment_status < 3 ? 'invoice' : 'receipt')) }}</el-dropdown-item>
            <el-dropdown-item v-if="nodePower.includes('del')" command="del" divided>
              <el-link class="row-control-item" :class="$store.state.app.size" type="danger" :underline="false">{{ $t('common.del') }}</el-link>
            </el-dropdown-item>
          </el-dropdown-menu>
        </el-dropdown>
      </template>
    </ProTable>

    <!-- 订单详情对话框 -->
    <el-dialog :title="$t('order.detail')" :visible.sync="showDetail" :width="device === 'desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="resetDetail">
      <div v-if="detail">
        <el-descriptions :title="$t('order.baseInfo')" :column="1" border>
          <el-descriptions-item :label="$t('order.order_no')">{{ detail.order_no }}</el-descriptions-item>
          <el-descriptions-item :label="$t('order.type')">{{ $t('order.type' + detail.indent_type) }}</el-descriptions-item>
          <el-descriptions-item :label="$t('order.payment_status')">
            <el-tag :type="paymentStatusType(detail.payment_status)">{{ $t('order.ps' + detail.payment_status) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item :label="$t('order.order_remark')">{{ detail.order_remark || '--' }}</el-descriptions-item>
          <el-descriptions-item :label="$t('order.apoTime')">{{ detail.order_time }}</el-descriptions-item>
        </el-descriptions>

        <!-- 物流信息 -->
        <el-descriptions v-if="detail.card_order_details && detail.card_order_details.length" class="mtb20" :title="$t('order.logistics_info')" :column="1" border>
          <el-descriptions-item :label="$t('order.logistics_status')">
            <el-tag v-if="detail.logistics_status === 1" type="success" size="small">{{ $t('order.cardShipped') }}</el-tag>
            <el-tag v-else-if="detail.card_order_details.every(d => d.exchange_status === 0)" type="info" size="small">{{ $t('order.cardUnexchanged') }}</el-tag>
            <el-tag v-else-if="detail.card_order_details.some(d => d.exchange_status === 2)" type="warning" size="small">{{ $t('order.cardShipping') }}</el-tag>
            <el-tag v-else-if="detail.card_order_details.some(d => d.exchange_status === 1)" type="success" size="small">{{ $t('order.cardExchanged') }}</el-tag>
            <el-tag v-else type="info" size="small">{{ $t('order.cardUnexchanged') }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item v-if="detail.logistics_number" :label="$t('order.logistics_no')">
            <el-link type="primary" :underline="false" @click="openTracking(detail.logistics_number)">{{ detail.logistics_number }}</el-link>
          </el-descriptions-item>
          <el-descriptions-item v-if="nodePower.includes('logistics') && detail.logistics_status === 0 && detail.card_order_details.some(d => d.exchange_status >= 1)">
            <el-popover placement="bottom" trigger="click" @show="showCardLogiInp(detail.order_id)" @hide="hideLogiInp">
              <div class="df">
                <el-input v-model="logiForm.logistics_number" class="mr10" :placeholder="$t('common.enter')" />
                <el-button type="primary" :loading="isLoadLogi" @click="cardLogistics()">{{ $t('common.confirm') }}</el-button>
              </div>
              <el-button slot="reference" type="primary" size="small">{{ $t('order.logistics') }}</el-button>
            </el-popover>
          </el-descriptions-item>
        </el-descriptions>

        <el-descriptions class="mtb20" :title="$t('order.contact')" :column="1" border>
          <el-descriptions-item :label="$t('order.delivery_name')">{{ detail.delivery_name || detail.user_name }}</el-descriptions-item>
          <el-descriptions-item :label="$t('order.delivery_phone')">{{ detail.delivery_phone || detail.user_phone }}</el-descriptions-item>
          <el-descriptions-item :label="$t('order.delivery_mail')">{{ detail.delivery_mail || detail.user_email }}</el-descriptions-item>
          <el-descriptions-item v-if="detail.delivery_area || detail.delivery_address" :label="$t('order.delivery_address')">{{ detail.delivery_area }} {{ detail.delivery_address }}</el-descriptions-item>
        </el-descriptions>

        <el-descriptions class="mtb20" :title="$t('order.payInfo')" :column="1" border>
          <el-descriptions-item :label="$t('order.order_money')">{{ detail.order_money }}</el-descriptions-item>
          <el-descriptions-item :label="$t('order.payment_at')">{{ detail.created_at || '--' }}</el-descriptions-item>
        </el-descriptions>

        <h4 class="mtb20">{{ $t('order.indType' + detail.indent_type) }}</h4>
        <!-- 抽卡项目置底 -->
        <el-card v-for="(goods, idx) in detail.card_order_details" :key="idx" class="mb20">
          <div class="goods">
            <el-row>
              <el-col :span="device === 'desktop' ? 16 : 24">
                <el-row>
                  <el-col :span="device === 'desktop' ? 3 : 4">
                    <ProImage :src="goods.product_img" />
                  </el-col>
                  <el-col :span="device === 'desktop' ? 21 : 20">
                    <div class="mlr20">
                      <div class="goods-title">{{ goods.product_name }}</div>
                      <div class="goods-txt df jcsb">
                        <span>{{ $t('offlineOrder.uniPrice') }}：${{ goods.product_price }}</span>
                        <span>x{{ goods.product_number }}</span>
                      </div>
                      <!-- 兑换状态 -->
                      <div class="goods-txt">
                        <el-tag v-if="goods.exchange_status === 0" type="info" size="small">{{ $t('order.cardUnexchanged') }}</el-tag>
                        <el-tag v-if="goods.exchange_status === 1" type="danger" size="small">{{ $t('order.cardExchanged') }}</el-tag>
                      </div>
                    </div>
                  </el-col>
                </el-row>
              </el-col>
            </el-row>
          </div>
        </el-card>

      </div>
    </el-dialog>

    <!-- 操作记录对话框 -->
    <el-dialog :title="$t('order.control_record')" :visible.sync="showRecord" :width="device === 'desktop' ? '1000px' : '100%'" :close-on-click-modal="false">
      <div class="df aic mb20">
        <h4>{{ $t('order.record_type') }}：</h4>
        <el-select v-model="recordType" :placeholder="$t('common.selectPh')" clearable>
          <el-option v-for="item in recordTypes" :key="item.value" :label="$t(item.label)" :value="item.value" />
        </el-select>
      </div>
      <ProTable index border :is-loading="recordLoad" :data="recordList" :column="recordCol" :page="recordPage" @page-change="onRecordPageChange">
        <template #activity_value="{ row }"><span>{{ language === 'zh' ? row.activity_value : row.en_activity_value }}</span></template>
        <template #activity_type="{ row }"><span>{{ $t('order.recordType' + row.activity_type) }}</span></template>
      </ProTable>
    </el-dialog>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import {
  addCardLogistic,
  completeOrder,
  confirmPay,
  delOrder,
  controlRecord,
  editPaymentat
} from '@/api'
import paging from '@/mixins/paging'

export default {
  name: 'CardOrder',
  mixins: [paging],
  data() {
    return {
      delIds: [],
      isLoading: false,
      subType: 2,
      searchOrderNo: '',
      searchKeyword: '',
      sales: [],
      column: {
        order_no: 'order.order_no',
        order_details: 'order.goods',
        delivery_name: 'order.user',
        logistics_status: 'order.logistics_status',
        payment_status: 'order.payment_status',
        order_money: 'order.money',
        payment_at: 'order.payTime'
      },
      showDetail: false,
      detail: null,
      isLoadLogi: false,
      logiIndex: '',
      logiForm: { logistics_number: '' },
      showRecord: false,
      recordLoad: false,
      recordType: '',
      recordTypes: [
        { value: 6, label: 'order.recordType6' },
        { value: 7, label: 'order.recordType7' },
        { value: 8, label: 'order.recordType8' }
      ],
      record_id: '',
      recordList: [],
      recordPage: {
        current: 1,
        size: 10,
        total: 0
      },
      recordCol: {
        activity_value: 'order.record_value',
        activity_type: 'order.record_type',
        admin_name: 'coupon.admin',
        created_at: 'order.record_date'
      },
      curId: '',
      isLoadPayat: false
    }
  },
  mounted() {
    this.getList('Orders', this.getExtraParams())
  },
  computed: {
    ...mapGetters(['language', 'device'])
  },
  watch: {
    recordType() {
      this.getRecord({ order_id: this.record_id })
    }
  },
  methods: {
    resetSearch() {
      this.searchOrderNo = ''
      this.searchKeyword = ''
      this.subType = 2
      this.getList('Orders', this.getExtraParams())
    },
    paymentStatusType(status) {
      const map = { 1: 'info', 2: 'warning', 3: 'success', 4: 'danger', 5: 'success' }
      return map[status] || ''
    },
    toggleSubType() {
      this.getList('Orders', this.getExtraParams())
    },
    getExtraParams() {
      const params = { indent_type: [0] }
      if (this.subType >= 0) {
        params.exchange_status = this.subType
        if (this.subType === 2) {
          params.logistics_status = 0
        }
        if (this.subType === 3) {
          params.exchange_status = 2
          params.logistics_status = 1
        }
      }
      return params
    },
    onScanSearch(orderNo) {
      const params = this.getExtraParams()
      params.order_no = orderNo
      this.onSearch('Orders', params)
    },
    openTracking(trackingNo) {
      if (!trackingNo) return
      const lang = this.language === 'zh' ? 'zh-cn' : 'en'
      const url = `https://www.17track.net/${lang}/track?nums=${trackingNo}`
      window.open(url, '_blank')
    },
    onSelectionChange(rows) {
      this.delIds = (rows || []).map(r => r.order_id)
    },
    handleCommand(funcName, row) {
      this[funcName](row)
    },
    view(row) {
      this.detail = row
      this.showDetail = true
    },
    hideLogiInp() {
      this.logiForm = { logistics_number: '' }
      this.logiIndex = ''
    },
    // 抽卡订单 - 显示物流输入
    showCardLogiInp(orderId) {
      this.logiForm = {
        order_id: orderId,
        logistics_number: ''
      }
    },
    // 抽卡订单 - 添加物流
    async cardLogistics() {
      if (!this.logiForm.logistics_number) {
        return this.$message({ message: this.$t('order.logi_err'), type: 'warning' })
      }
      try {
        this.isLoadLogi = true
        await addCardLogistic({
          order_id: this.logiForm.order_id,
          logistics_number: this.logiForm.logistics_number
        })
        // 更新本地物流记录
        this.$set(this.detail, 'logistics_number', this.logiForm.logistics_number)
        this.$set(this.detail, 'logistics_status', 1)
        this.$message({ message: this.$t('common.success'), type: 'success' })
        this.hideLogiInp()
      } catch (e) {
        // 错误由拦截器处理
      } finally {
        this.isLoadLogi = false
      }
    },
    async rac(detailsId, index) {
      const message = this.$t('order.complete_con')
      try {
        await this.$confirm(message, this.$t('common.tips'), { type: 'warning' })
        await completeOrder({ order_details_id: detailsId })
        this.$set(this.detail, 'payment_status', 5)
        this.$set(this.detail.card_order_details[index], 'logistics_status', 2)
        this.$message({ message: this.$t('common.success'), type: 'success' })
      } catch (e) {
        // 取消或错误由拦截器处理
      }
    },
    resetDetail() {
      this.detail = null
    },
    async getRecord({ order_id }) {
      this.record_id = order_id
      this.showRecord = true
      this.recordLoad = true
      try {
        const { results: { data, count }} = await controlRecord({
          page: this.recordPage.current,
          limit: this.recordPage.size,
          order_id,
          activity_type: this.recordType
        })
        this.recordList = data
        this.recordPage.total = count
      } catch (e) {
        // 错误由拦截器处理
      } finally {
        this.recordLoad = false
      }
    },
    onRecordPageChange() {
      this.getRecord({ order_id: this.record_id })
    },
    async pay({ order_id }) {
      try {
        await this.$confirm(this.$t('order.pay_con'), this.$t('common.tips'), { type: 'warning' })
        await confirmPay({ order_id })
        const { current, size } = this.page
        this.getList('Orders', { page: current, limit: size, ...this.getExtraParams() })
        this.$message({ message: this.$t('common.success'), type: 'success' })
      } catch (e) {
        // 取消或错误由拦截器处理
      }
    },
    async rai({ order_id }) {
      const url = process.env.VUE_APP_BASE_API + '/generateInvoiceWeb?order_id=' + order_id
      window.open(url, '_blank')
    },
    async del({ order_id } = {}) {
      const ids = order_id ? [order_id] : this.delIds
      if (!ids.length) return
      try {
        await this.$confirm(this.$t('common.delConfirm'), this.$t('common.tips'), { type: 'warning' })
        await delOrder({ order_id: ids })
        if (order_id) {
          this.list = this.list.filter(item => item.order_id !== order_id)
          this.page.total--
        } else {
          this.list = this.list.filter(item => !this.delIds.includes(item.order_id))
          this.page.total -= this.delIds.length
        }
        this.delIds = []
        this.$message({ message: this.$t('common.success'), type: 'success' })
      } catch (e) {
        // 取消或错误由拦截器处理
      }
    },
    async updatePayat({ order_id, _payment_at_copy }) {
      if (!_payment_at_copy) {
        return this.$message({ message: this.$t('order.payat_err'), type: 'warning' })
      }
      try {
        this.curId = order_id
        this.isLoadPayat = true
        await editPaymentat({ order_id, payment_at: _payment_at_copy })
        const item = this.list.find(item => item.order_id === order_id)
        if (item) item.payment_at = _payment_at_copy
        this.$message({ message: this.$t('common.success'), type: 'success' })
      } catch (e) {
        // 错误由拦截器处理
      } finally {
        this.isLoadPayat = false
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.goods {
  .goods-title {
    font-weight: bold;
  }
  .goods-txt {
    margin: 5px 0;
    font-size: 12px;
  }
  .btns {
    display: flex;
    justify-content: center;
  }
}
.row-control-item {
  margin: 0 5px;
}
.search-input {
  width: 240px;
}
</style>
