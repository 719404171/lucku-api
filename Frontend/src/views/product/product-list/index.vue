<template>
<div class="app-container">
  <div v-if="device === 'desktop'" class="mb20">
    <el-button v-if="nodePower.includes('add')" type="success" icon="el-icon-plus" @click="show = true">{{ $t("common.add") }}</el-button>
    <el-button type="primary" icon="el-icon-picture-outline" @click="showCardPicker = true">卡库</el-button>
    <el-button v-if="nodePower.includes('del')" type="danger" icon="el-icon-delete" :disabled="!!!delIds.length" @click="del">{{ $t("common.deletes") }}</el-button>
    <el-button :loading="downloadLoading" type="primary" icon="el-icon-document" @click="handleDownload">{{ $t('common.export') }} Excel</el-button>
    <el-popover class="mlr10" placement="bottom" trigger="click">
      <ProForm ref="searchForm" :config="SFC" />
      <el-button type="primary" @click="onSearch('Goods')">{{
          $t("common.confirm")
        }}</el-button>
      <el-button slot="reference" type="primary" icon="el-icon-search">{{
          $t("common.search")
        }}</el-button>
    </el-popover>
    <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Goods')" />
  </div>
  <div v-else class="mb20">
    <el-button v-if="nodePower.includes('add')" type="success" icon="el-icon-plus" @click="show = true" />
    <el-button type="primary" icon="el-icon-picture-outline" @click="showCardPicker = true" />
    <el-button v-if="nodePower.includes('del')" type="danger" icon="el-icon-delete" :disabled="!!!delIds.length" @click="del" />
    <el-button :loading="downloadLoading" type="primary" icon="el-icon-document" @click="handleDownload" />
    <el-popover class="mlr10" placement="bottom" trigger="click">
      <ProForm ref="searchForm" :config="SFC" />
      <el-button type="primary" @click="onSearch('Goods')">{{
          $t("common.confirm")
        }}</el-button>
      <el-button slot="reference" type="primary" icon="el-icon-search" />
    </el-popover>
    <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Goods')" />
  </div>

  <!--  -->
  <ProTable index selection border :table-row-class-name="tableRowClassName" :is-loading="isLoading" :data="list" :page="page" :column="column" :row-control="['view', 'edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('Goods', $event)" @selection-change="delIds = $event.map(({ product_id }) => product_id)">
<!--    <template #product_id="{ row }">{{-->
<!--        $t(row.product_id)-->
<!--      }}</template>-->
    <template #product_status="{ row }">{{
        $t(goodsStatus[row.product_status])
      }}</template>
    <template #product_img="{ row }">
      <ProImage :src="row.product_img || ''" /></template>
    <template #product_type="{ row }">{{
        $t(goodsType[row.product_type])
      }}</template>
    <template #product_stock="{ row }"><span :class="{ danger: row.product_type === 1 && row.is_min_stock }">{{ row.product_type === 1 ? row.product_stock : "--" }}</span></template>
    <template #product_pay_type="{ row }">{{
        goodsPayType[row.product_pay_type] || goodsPayType[3]
      }}</template>
    <!-- <template #status="{row}"><el-tag :type="row.status ? '' : 'danger'">{{$t(statusTxt[row.status])}}</el-tag></template> -->
  </ProTable>

  <!--  -->
  <el-dialog :title="
        $t(`goodsCate.${this.baseForm.product_id ? 'editCate' : 'addCate'}`)
      " :visible.sync="show" :width="device === 'desktop' ? '1100px' : '100%'" :close-on-click-modal="false" @closed="resetForm">
    <el-tabs v-model="activeName">
      <!--  -->
      <el-tab-pane :label="$t('goods.base')" name="base">
        <!-- <ProForm ref="baseForm" :echo="baseForm" :config="EFC" :rules="rules" :msg="$t('goods.noBase')"></ProForm> -->
        <el-form ref="baseForm" :model="baseForm" :rules="rules" label-position="top">
          <el-row :gutter="20">
            <el-col :span="24">
              <el-form-item :label="$t('goods.image')" prop="product_img">
                <BaseFile v-model="baseForm.product_img" :config="{ attr: { limit: 1 } }" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="device === 'desktop' ? 12 : 24">
              <el-form-item :label="$t('goods.status')" prop="product_status">
                <el-select v-model="baseForm.product_status" class="f1">
                  <el-option v-for="(v, i) in statusOption" :key="i" :label="$t('goods.' + v)" :value="i*1" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="24">
              <el-form-item :label="$t('goods.zh_name')" prop="product_name">
                <el-input v-model="baseForm.product_name" :placeholder="$t('common.enter')" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="24">
              <el-form-item :label="$t('goods.zh_syno')" prop="product_synopsis">
                <el-input v-model="baseForm.product_synopsis" :placeholder="$t('common.enter')" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="24">
              <el-form-item :label="$t('goods.deal_condition')" prop="deal_condition">
          <el-input v-model="baseForm.deal_condition" :placeholder="$t('common.enter')" />
        </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="24">
              <el-form-item :label="$t('goods.cate')" prop="product_sort_id">
                <div class="df">
                  <el-select v-model="baseForm.product_sort_id" class="f1">
                    <el-option v-for="(item, index) in psOption" :key="index" :label="item.label" :value="item.value" />
                  </el-select>
                </div>
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="device === 'desktop' ? 12 : 24">
              <el-form-item :label="$t('goods.price')" prop="product_price">
                <div class="df">
                  <el-input-number v-model="baseForm.product_price" class="f1" :min="0" />
                </div>
              </el-form-item>
            </el-col>
            <el-col :span="device === 'desktop' ? 12 : 24">
              <el-form-item :label="$t('goods.cost')" prop="product_cost">
                <div class="df">
                  <el-input-number v-model="baseForm.product_cost" class="f1" :min="0" />
                </div>
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="device === 'desktop' ? 12 : 24">
              <el-form-item :label="$t('goods.stock')" prop="product_stock">
                <div class="df">
                  <el-input-number v-model="baseForm.product_stock" class="f1" :min="0" clearable />
                </div>
              </el-form-item>
            </el-col>
          </el-row>
        </el-form>
        <el-button type="primary" @click="activeName = 'detail'">{{
            $t("common.next")
          }}</el-button>
      </el-tab-pane>
      <!--  -->
      <el-tab-pane :label="$t('goods.detail')" name="detail">
        <el-form ref="detailForm" :model="detailForm" label-position="top">
          <el-form-item prop="album">
            <div slot="label" class="df aic">
              <span class="mr10">{{ $t("goods.album") }}</span>
              <el-switch v-model="detailForm.is_album" :active-value="1" :inactive-value="0" />
            </div>
            <BaseFile v-if="detailForm.is_album === 1" v-model="detailForm.album" :config="{ attr: { limit: 10 } }" />
          </el-form-item>
          <el-form-item :label="$t('goods.zh_desc')" prop="product_describe">
            <QuillEditor v-model="detailForm.product_describe" width="1000" />
          </el-form-item>
          <el-button type="primary" @click="activeName = 'base'">{{
              $t("common.prev")
            }}</el-button>
          <el-button type="primary" @click="activeName = 'else'">{{
              $t("common.next")
            }}</el-button>
        </el-form>
      </el-tab-pane>
      <!--  -->
      <el-tab-pane :label="$t('goods.else')" name="else">
        <el-form ref="elseForm" :model="elseForm" label-position="top">
          <el-form-item>
            <div slot="label" class="df aic">
              <span class="mr10">{{ $t("goods.is_priority") }}</span>
              <el-switch v-model="detailForm.is_priority" :active-value="1" :inactive-value="0" />
            </div>
          </el-form-item>
          <el-form-item :label="$t('goods.pay_type')" prop="product_pay_type">
            <el-select v-model="baseForm.product_pay_type" class="f1">
              <el-option :label="'金币支付'" :value="1" />
              <el-option :label="'钻石支付'" :value="2" />
              <el-option :label="'全部'" :value="3" />
            </el-select>
          </el-form-item>
          <el-button type="primary" @click="activeName = 'detail'">{{
              $t("common.prev")
            }}</el-button>
          <el-button type="primary" :loading="isLoadSub" @click="submit">{{
              $t("common.confirm")
            }}</el-button>
        </el-form>
      </el-tab-pane>
    </el-tabs>
  </el-dialog>
  <!--  -->
  <el-dialog :title="$t('goods.details')" :visible.sync="showDetails" :width="device === 'desktop' ? '1100px' : '100%'" :close-on-click-modal="false" @closed="details = null">
    <template v-if="details">
      <el-descriptions :title="$t('goods.base')" :column="device === 'desktop' ? 2 : 1" border>
        <template v-for="(lab, key, index) in baseLab">
          <el-descriptions-item v-if="key === 'product_img'" :key="index" :label="$t(lab)">
            <ProImage :src="details.product_img" />
          </el-descriptions-item>
          <el-descriptions-item v-else-if="key === 'product_id'" :key="index" :label="$t(lab)">
            <ProQrcode :value="details.product_id" :size="128" />
          </el-descriptions-item>
          <el-descriptions-item v-else-if="key === 'product_type'" :key="index" :label="$t(lab)">{{ $t(goodsType[details.product_type]) }}</el-descriptions-item>
          <el-descriptions-item v-else :key="index" :label="$t(lab)">{{
              details[key]
            }}</el-descriptions-item>
        </template>
      </el-descriptions>
      <el-descriptions class="mt40" :title="$t('goods.detail')" :column="1" :direction="device === 'desktop' ? 'horizontal' : 'vertical'" border>
        <el-descriptions-item :label="`${$t('goods.album')}（${$t(
              switchTxts[details.is_album]
            )}）`">
          <ProImage :src="details.product_album.map(({ image_url }) => image_url)" expand />
        </el-descriptions-item>
        <el-descriptions-item :label="$t('goods.zh_desc')">
          <ProPreview :content="details.product_describe" />
        </el-descriptions-item>
      </el-descriptions>
      <el-descriptions class="mt40" :title="$t('goods.else')" :column="1" border>
        <el-descriptions-item :label="$t('goods.is_priority')">{{
            $t(switchTxts[details.is_priority])
          }}</el-descriptions-item>
        <!-- <el-descriptions-item :label="$t('common.created_at')">{{ details.created_at }}</el-descriptions-item>
          <el-descriptions-item :label="$t('common.updated_at')">{{ details.updated_at }}</el-descriptions-item> -->
      </el-descriptions>
    </template>
  </el-dialog>
  <!-- 卡库卡牌选择器 -->
  <el-dialog title="选择卡牌" :visible.sync="showCardPicker" width="1200px" :close-on-click-modal="false" @opened="loadCardPickerData" @closed="resetCardPicker" class="card-picker-dialog" :destroy-on-close="true" append-to-body>
    <div v-loading="cardLoading">
      <div v-if="cardList.length" class="card-picker-grid">
        <div
          v-for="card in cardList"
          :key="card.id"
          :class="['card-picker-item', { 'card-picker-selected': cardPickedIds.includes(card.id) }]"
          @click="toggleCardPick(card.id)"
        >
          <el-image
            style="width:120px;height:120px;border-radius:6px"
            :src="card.card_img"
            fit="cover"
          >
            <div slot="error" class="card-img-error">
              <i class="el-icon-picture-outline"></i>
            </div>
          </el-image>
          <div class="card-picker-name">{{ card.card_name }}</div>
          <div class="card-picker-price">¥{{ card.deal_price || card.card_price || 0 }}</div>
          <div class="card-picker-condition">{{ card.deal_condition || '' }}</div>
          <i v-if="cardPickedIds.includes(card.id)" class="el-icon-check card-picker-check"></i>
        </div>
      </div>
      <el-empty v-else description="暂无卡牌" />
    </div>
    <span slot="footer">
      <el-button @click="showCardPicker = false">取消</el-button>
      <el-button type="primary" :disabled="!cardPickedIds.length" :loading="cardImporting" @click="importFromCardPicker">
        新增至产品列表 ({{ cardPickedIds.length }})
      </el-button>
    </span>
  </el-dialog>
</div>
</template>

<script>
import { mapGetters } from 'vuex'
import {
  getGoodsCate,
  addGoods,
  editGoods,
  delGoods,
  addGoodsCate,
  reqSpiderCard,
  getStore as reqGetStore
} from '@/api'
import { goods } from '@/form'
import paging from '@/mixins/paging'
import BaseFile from '@/components/ProForm/baseFile.vue'
import QuillEditor from '@/components/QuillEditor'

export default {
  name: 'ProductList',
  components: { BaseFile, QuillEditor },
  mixins: [paging],
  data() {
    return {
      SFC: goods.SFC,
      // EFC: goods.EFC,
      activeName: 'base',
      show: false,
      delIds: [],
      downloadLoading: false,
      column: {
        product_id: 'goods.id',
        product_status: 'goods.status',
        product_img: 'goods.image',
        product_name: 'goods.zh_name',
        product_type: 'goods.type',
        product_sort_name: 'goods.cate',
        product_price: 'goods.price',
        product_cost: 'goods.cost',
        product_stock: 'goods.stock',
        product_pay_type: 'goods.pay_type',
        deal_condition: 'goods.deal_condition'
      },
      goodsType: ['', 'goods.product', 'goods.services'],
      goodsPayType: { 1: '金币支付', 2: '钻石支付', 3: '全部' },
      goodsStatus: {
        0: 'goods.forbidden',
        '-1': 'goods.delete',
        1: 'goods.normal'
      },
      product_stock: 0,
      baseForm: {
        product_img: '',
        product_name: '',
        product_synopsis: '',
        product_type: 1,
        product_sort_id: '',
        product_status: 1,
        product_cost: 0,
        product_price: 0,
        product_stock: 0,
        product_pay_type: 3,
        deal_condition: ''
      },
      statusOption: {
        0: 'forbidden',
        1: 'normal'
      },
      psOption: [],
      // 卡库图片选择器
      showCardPicker: false,
      cardLoading: false,
      cardImporting: false,
      cardList: [],
      cardPickedIds: [],
      cardSortId: null,
      detailForm: {
        is_album: 1,
        album: [],
        product_describe: '',
        is_priority: 0
      },
      elseForm: {},
      checked: [],
      options: [],
      tagInputVisible: false,
      isLoadSub: false,
      showDetails: false,
      details: null,
      baseLab: {
        product_name: 'goods.zh_name',
        product_synopsis: 'goods.zh_syno',
        product_type: 'goods.type',
        product_sort_name: 'goods.cate',
        product_price: 'goods.price',
        product_cost: 'goods.cost',
        product_stock: 'goods.stock',
        product_img: 'goods.image',
        product_pay_type: 'goods.pay_type',
        product_id: 'goods.qrcode',
        deal_condition: 'goods.deal_condition'
      },
      switchTxts: ['common.close', 'common.open'],
      rules: {
        product_img: [
          {
            required: true,
            message: this.$t('common.required'),
            trigger: 'blur'
          }
        ],
        product_name: [
          {
            required: true,
            message: this.$t('common.required'),
            trigger: 'blur'
          }
        ],
        product_synopsis: [
          {
            required: true,
            message: this.$t('common.required'),
            trigger: 'blur'
          }
        ],
        product_sort_id: [
          {
            required: true,
            message: this.$t('common.required'),
            trigger: 'blur'
          }
        ]
      }
    }
  },
  created() {
    this.init()
    this.getList('Goods')
  },
  computed: {
    ...mapGetters(['language'])
  },
  watch: {
    'baseForm.product_type'(val) {
      this.baseForm.product_stock = val === 1 ? this.product_stock : 0
    },
    list(val) {
      val.forEach((row) => {
        if (row.product_type === 1 && row.is_min_stock) {
          setTimeout(() => {
            this.$notify({
              type: 'error',
              title: this.$t('goods.minStockErr'),
              message:
                row[(this.language === 'zh' ? '' : 'en_') + 'product_name'],
              position: 'bottom-right'
            })
          })
        }
      })
    }
  },
  methods: {
    // 导出excel
    handleDownload() {
      this.downloadLoading = true
      import('@/vendor/Export2Excel').then(excel => {
        const [tHeader, filterVal] = Object.entries(this.column).reduce(
          (arr, [key, val]) => {
            arr[0].push(this.$t(val))
            arr[1].push(key)
            return arr
          },
          [[], []]
        )
        const list = this.list
        const data = this.formatJson(filterVal, list)
        excel.export_json_to_excel({
          header: tHeader,
          data,
          filename: 'Product List',
          autoWidth: true,
          bookType: 'xlsx'
        })
        this.downloadLoading = false
      })
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map(v => filterVal.map(j => {
        if (j === 'timestamp') {
          return parseTime(v[j])
        } else {
          return v[j]
        }
      }))
    },
    // 低库存提醒
    tableRowClassName({ row }) {
      if (row.product_type === 1 && row.is_min_stock) {
        return 'warning-row'
      }
      return ''
    },
    async init() {
      getGoodsCate().then(({ results }) => {
        const cate = results.map(({ product_sort_id, product_sort_name }) => ({
          label: product_sort_name,
          value: product_sort_id,
          trans: '1'
        }))
        this.$set(this.SFC.formItems[3].item, 'options', cate)
        this.psOption = cate
      })
    },
    // 查看
    view(scope) {
      this.details = this.$lodash.cloneDeep(scope)
      this.details.product_describe =
        this.details.product_describe &&
        this.details.product_describe.replace(
          /<img/g,
          '<img style="width:100%"'
        )
      this.showDetails = true
    },
    // 编辑
    edit({
      product_id,
      product_cost,
      product_img,
      product_name,
      product_price,
      product_status,
      product_sort_id,
      product_stock,
      product_synopsis,
      product_pay_type,
      deal_condition,
      is_album,
      product_album,
      product_describe,
      is_priority,
    }) {
      const album = product_album.map(({ image_url }) => image_url)
      this.product_stock = product_stock
      this.baseForm = {
        product_id,
        product_type: 1,
        product_cost,
        product_img,
        product_name,
        product_price,
        product_sort_id,
        product_stock,
        product_synopsis,
        product_status,
        product_pay_type: product_pay_type || 3,
        deal_condition: deal_condition || '',
      }
      this.detailForm = {
        is_album,
        album,
        product_describe,
        is_priority
      }
      this.checked = []
      this.elseForm = {}
      this.show = true
    },
    // 批量删除
    async del({ product_id } = {}) {
      this.asyncMsg(async(instance, done) => {
        instance.confirmButtonLoading = true
        try {
          await delGoods({
            product_id: product_id ? [product_id] : this.delIds
          })
          if (product_id) {
            this.list = this.list.filter(
              (item) => item.product_id !== product_id
            )
            this.page.total--
          } else {
            this.list = this.list.filter(
              (item) => !this.delIds.includes(item.product_id)
            )
            this.page.total -= this.delIds.length
          }
          this.delIds = []
          done()
        } catch (error) {
        } finally {
          instance.confirmButtonLoading = false
        }
      })
    },
    // 操作
    onRcc({ scope: { row }, type }) {
      this[type](row)
    },
    // 卡库图片选择器：载入数据
    async loadCardPickerData() {
      this.cardLoading = true
      try {
        const { results } = await reqSpiderCard({ page: 1, limit: 9999 })
        this.cardList = results.data || []
      } catch (e) {
        console.log(e)
      } finally {
        this.cardLoading = false
      }
    },
    // 卡库图片选择器：切换选中
    toggleCardPick(id) {
      const idx = this.cardPickedIds.indexOf(id)
      if (idx > -1) {
        this.cardPickedIds.splice(idx, 1)
      } else {
        this.cardPickedIds.push(id)
      }
    },
    // 卡库图片选择器：重置
    resetCardPicker() {
      this.cardList = []
      this.cardPickedIds = []
      this.cardSortId = null
    },
    // 卡库图片选择器：导入到产品列表
    async importFromCardPicker() {
      if (!this.cardPickedIds.length) return
      this.cardImporting = true
      try {
        // 1. 获取或创建「卡牌分类」
        let sortId = this.cardSortId
        if (!sortId) {
          const cateRes = await getGoodsCate()
          const allCates = cateRes.results || []
          const targetCate = allCates.find(c => c.product_sort_name === '卡牌')
          if (targetCate) {
            sortId = targetCate.product_sort_id
          } else {
            const createRes = await addGoodsCate({
              product_sort_name: '卡牌',
              en_product_sort_name: 'Cards',
              status: 1
            })
            // 重新获取分类列表取得新 ID
            const refreshRes = await getGoodsCate()
            const newCate = (refreshRes.results || []).find(c => c.product_sort_name === '卡牌')
            sortId = newCate ? newCate.product_sort_id : null
          }
          this.cardSortId = sortId
        }
        if (!sortId) {
          this.$message({ message: '无法获取卡牌分类', type: 'error' })
          return
        }
        // 2. 读取卡牌设置
        let recyclePercent = 0, sellPercent = 100, importStock = 1
        try {
          const { results: storeResults } = await reqGetStore()
          if (storeResults) {
            recyclePercent = storeResults.card_recycle_percent ?? 0
            sellPercent = storeResults.card_sell_percent ?? 100
            importStock = storeResults.card_import_stock ?? 1
          }
        } catch (e) {}
        // 3. 逐一新增产品
        const pickedCards = this.cardList.filter(c => this.cardPickedIds.includes(c.id))
        let successCount = 0
        for (const card of pickedCards) {
          try {
            await addGoods({
              product_img: card.card_img || '',
              product_name: card.card_name || '未命名卡牌',
              product_synopsis: card.card_name || '未命名卡牌',
              product_describe: card.card_name || '未命名卡牌',
              crawler_id: card.crawler_id || null,
              spider_cards_id: card.id || null,
              product_type: 1,
              product_sort_id: sortId,
              product_status: 1,
              product_price: Math.round(((card.deal_price || card.card_price || 0) * sellPercent / 100) * 100) / 100,
              product_cost: card.deal_price || card.card_price || 0,
              product_stock: importStock,
              deal_condition: card.deal_condition || ''
            })
            successCount++
          } catch (e) {
            console.log(e)
          }
        }
        this.$message({ message: `成功新增 ${successCount} 个产品`, type: 'success' })
        this.showCardPicker = false
        this.getList('Goods')
      } catch (e) {
        console.log(e)
        this.$message({ message: '导入失败', type: 'error' })
      } finally {
        this.cardImporting = false
      }
    },
    // 重置产品表单
    resetForm() {
      this.activeName = 'base'
      this.$refs.baseForm.resetFields()
      this.$refs.detailForm.resetFields()
      this.$refs.elseForm.resetFields()
      this.product_stock = 0
      this.baseForm = {
        product_img: '',
        product_name: '',
        product_synopsis: '',
        product_type: 1,
        product_sort_id: '',
        product_cost: 0,
        product_price: 0,
        product_stock: 0,
        product_pay_type: 3,
        deal_condition: ''
      }
      this.detailForm = {
        is_album: 1,
        album: [],
        product_describe: '',
        is_priority: 0
      }
      this.elseForm = {}
      this.checked = []
    },
    // 提交产品表单
    submit() {
      this.$refs.baseForm.validate(async(valid) => {
        if (!valid) {
          return this.$message({
            message: this.$t('goods.noBase'),
            type: 'warning'
          })
        }
        const { product_price, product_cost } = this.baseForm
        if ([product_price, product_cost].includes(0)) {
          return this.$message({
            message: this.$t('goods.tips2'),
            type: 'warning'
          })
        }
        if (this.detailForm.is_album && !this.detailForm.album.length) {
          return this.$message({
            message: this.$t('goods.noAlbum'),
            type: 'warning'
          })
        }
        try {
          this.isLoadSub = true
          const params = Object.assign(
            {},
            this.baseForm,
            this.detailForm,
            this.elseForm
          )
          if (params.product_id) {
            await editGoods(params)
          } else {
            await addGoods(params)
          }
          this.$message({
            message: this.$t('common.success'),
            type: 'success'
          })
          this.show = false
          this.getList('Goods')
        } catch (error) {
          console.log(error)
        } finally {
          this.isLoadSub = false
        }
      })
    }
  }
}
</script>

<style scoped lang="scss">
.danger {
  color: #f56c6c;
}
.el-tag {
  margin-right: 10px;
}

.lab {
  margin-bottom: 10px;
  font-size: 12px;
  font-weight: bold;
}

table,
th,
td {
  border: 1px solid #dddddd;
  border-collapse: collapse;
}
th,
td {
  padding: 5px 20px;
}
.thd-txt {
  max-width: 120px;
  word-break: keep-all;
  text-overflow: ellipsis;
  overflow: hidden;
  box-sizing: border-box;
}
.card-picker-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  padding: 12px 0;
  overflow-y: auto;
}
.card-picker-item {
  position: relative;
  width: 120px;
  cursor: pointer;
  border: 2px solid transparent;
  border-radius: 8px;
  padding: 6px;
  transition: all 0.2s;
}
.card-picker-item:hover {
  border-color: #409eff;
}
.card-picker-selected {
  border-color: #409eff;
  background: #ecf5ff;
}
.card-picker-name {
  text-align: center;
  font-size: 12px;
  margin-top: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.card-picker-desc {
  text-align: center;
  font-size: 11px;
  color: #909399;
  margin-top: 2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.card-picker-price {
  text-align: center;
  font-size: 13px;
  color: #e6a23c;
  font-weight: bold;
  margin-top: 2px;
}
.card-picker-condition {
  text-align: center;
  font-size: 11px;
  color: #67c23a;
  margin-top: 2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.card-picker-check {
  position: absolute;
  top: 6px;
  right: 6px;
  background: #409eff;
  color: #fff;
  border-radius: 50%;
  padding: 2px;
  font-size: 14px;
}
.card-img-error {
  width: 120px;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f7fa;
  border-radius: 6px;
  color: #c0c4cc;
}
</style>

<style>
.card-picker-dialog .el-dialog__body {
  height: 75vh !important;
  overflow-y: auto;
}
.el-notification__content {
  word-break: break-all;
}
</style>
