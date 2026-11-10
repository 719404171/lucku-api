<template>
  <div class="app-container">
    <div v-if="device === 'desktop'" class="mb20">
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC" />
        <el-button type="primary" @click="onSearch('SpiderCard')">{{ $t('common.confirm') }}</el-button>
        <el-button slot="reference" type="primary" icon="el-icon-search">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'SpiderCard'); conditionKeyword = ''" />
      <el-select v-model="conditionKeyword" placeholder="成色" clearable style="width:140px;margin-right:10px;" @change="applyConditionFilter">
        <el-option label="A" value="A" />
        <el-option label="B" value="B" />
        <el-option label="C" value="C" />
        <el-option label="D" value="D" />
        <el-option label="PSA 10" value="PSA 10" />
        <el-option label="PSA 9" value="PSA 9" />
        <el-option label="PSA 8 or under" value="PSA 8 or under" />
        <el-option label="BGS 10 BL" value="BGS 10 BL" />
        <el-option label="BGS 10 GL" value="BGS 10 GL" />
      </el-select>
      <el-button type="warning" icon="el-icon-setting" @click="showCardSettings = true">卡牌设置</el-button>
      <el-button type="primary" icon="el-icon-plus" @click="showAddCard = true">新增卡牌</el-button>
    </div>
    <div v-else class="mb20">
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC" />
        <el-button type="primary" @click="onSearch('SpiderCard')">{{ $t('common.confirm') }}</el-button>
        <el-button slot="reference" type="primary" icon="el-icon-search" />
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'SpiderCard'); conditionKeyword = ''" />
      <el-select v-model="conditionKeyword" placeholder="成色" clearable style="width:140px;margin-right:10px;" @change="applyConditionFilter">
        <el-option label="A" value="A" />
        <el-option label="B" value="B" />
        <el-option label="C" value="C" />
        <el-option label="D" value="D" />
        <el-option label="PSA 10" value="PSA 10" />
        <el-option label="PSA 9" value="PSA 9" />
        <el-option label="PSA 8 or under" value="PSA 8 or under" />
        <el-option label="BGS 10 BL" value="BGS 10 BL" />
        <el-option label="BGS 10 GL" value="BGS 10 GL" />
      </el-select>
      <el-button type="warning" icon="el-icon-setting" @click="showCardSettings = true" />
      <el-button type="primary" icon="el-icon-plus" @click="showAddCard = true" />
    </div>

    <ProTable
      index
      selection
      border
      :is-loading="isLoading"
      :data="list"
      :page="page"
      :column="column"
      :column-attr="columnAttr"
      @selection-change="onSelectionChange"
      @page-change="onPageChange('SpiderCard', $event, getExtraParams())"
      @sort-change="onSortChange"
    >
      <template #card_img="{ row }">
        <div style="position:relative;display:inline-block;width:120px;height:120px;">
          <ProImage :src="row.card_img || ''" size="120" />
          <span v-if="row.conditions" class="card-condition-badge">{{ row.conditions }}</span>
        </div>
      </template>
      <template #control="{ scope }">
        <el-button type="warning" size="mini" icon="el-icon-edit" @click="onEdit(scope.row)">{{ $t('common.edit') }}</el-button>
        <el-button type="danger" size="mini" icon="el-icon-delete" @click="onDelete(scope.row)">{{ $t('common.del') }}</el-button>
      </template>
      <template #is_update="{ row }">
        <el-tag :type="row.is_update ? 'success' : 'info'">{{ row.is_update ? $t('common.yes') : $t('common.no') }}</el-tag>
      </template>
    </ProTable>
    <div v-if="selectedIds.length > 0" style="margin-top:10px;">
      <el-button type="danger" icon="el-icon-delete" @click="onBatchDelete">删除所选（{{ selectedIds.length }}）</el-button>
    </div>
  <!-- 卡牌设置对话框 -->
  <el-dialog title="卡牌设置" :visible.sync="showCardSettings" width="500px" :close-on-click-modal="false" :destroy-on-close="true" append-to-body>
    <el-form label-position="top">
      <el-form-item label="回收百分比（%）">
        <el-input-number v-model="cardSettings.recycle_percent" :min="0" :max="100" :precision="0" style="width:100%" />
        <div class="el-form-item__tip">导入产品时，回收价格 = 卡牌价格 × 回收百分比</div>
      </el-form-item>
      <el-form-item label="售卖百分比（%）">
        <el-input-number v-model="cardSettings.sell_percent" :min="0" :max="1000" :precision="0" style="width:100%" />
        <div class="el-form-item__tip">导入产品时，售卖价格 = 卡牌价格 × 售卖百分比</div>
      </el-form-item>
      <el-form-item label="预设库存">
        <el-input-number v-model="cardSettings.import_stock" :min="0" :precision="0" style="width:100%" />
        <div class="el-form-item__tip">导入产品时，库存设为此值</div>
      </el-form-item>
    </el-form>
    <span slot="footer">
      <el-button type="primary" @click="saveCardSettings">储存</el-button>
      <el-button @click="showCardSettings = false">取消</el-button>
    </span>
  </el-dialog>
  <!-- 新增卡牌对话框 -->
  <el-dialog title="新增卡牌" :visible.sync="showAddCard" width="960px" :close-on-click-modal="false" :destroy-on-close="true" append-to-body @closed="resetAddForm">
    <el-form ref="addForm" :model="addForm" label-width="100px">
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="卡牌名称" prop="card_name">
            <el-input v-model="addForm.card_name" placeholder="请输入卡牌名称" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="图片">
            <BaseFile v-model="addForm.card_img" :config="{ attr: { limit: 1 } }" />
            <div class="el-form-item__tip">建议尺寸856*625</div>
          </el-form-item>
        </el-col>
      </el-row>
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="品牌">
            <el-input v-model="addForm.card_brand" placeholder="请输入品牌" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="品类">
            <el-input v-model="addForm.card_category" placeholder="请输入品类" />
          </el-form-item>
        </el-col>
      </el-row>
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="类型">
            <el-select v-model="addForm.card_type" placeholder="请选择类型" style="width:100%">
              <el-option label="单卡" value="single" />
              <el-option label="套装" value="set" />
              <el-option label="其他" value="other" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="成色">
            <el-input v-model="addForm.deal_condition" placeholder="例如：PSA 10" />
          </el-form-item>
        </el-col>
      </el-row>
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="价格">
            <el-input-number v-model="addForm.deal_price" :min="0" :precision="2" style="width:100%" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="成本">
            <el-input-number v-model="addForm.card_cost" :min="0" :precision="2" style="width:100%" />
          </el-form-item>
        </el-col>
      </el-row>
      <el-form-item label="描述">
        <el-input v-model="addForm.card_describe" type="textarea" :rows="3" placeholder="请输入描述" />
      </el-form-item>
    </el-form>
    <span slot="footer">
      <el-button type="primary" :loading="isAddLoading" @click="submitAddCard">确定新增</el-button>
      <el-button @click="showAddCard = false">取消</el-button>
    </span>
  </el-dialog>
  <!-- 编辑卡牌对话框 -->
  <el-dialog title="编辑卡牌" :visible.sync="showEditCard" width="500px" :close-on-click-modal="false" :destroy-on-close="true" append-to-body @closed="resetEditForm">
    <el-form ref="editForm" :model="editForm" label-width="100px">
      <el-form-item label="卡牌名称">
        <el-input v-model="editForm.card_name" disabled />
      </el-form-item>
      <el-form-item label="图片">
        <BaseFile v-model="editForm.card_img" :config="{ attr: { limit: 1 } }" />
        <div class="el-form-item__tip">建议尺寸856*625</div>
      </el-form-item>
      <el-form-item label="价格">
        <el-input-number v-model="editForm.deal_price" :min="0" :precision="2" style="width:100%" />
      </el-form-item>
      <el-form-item label="是否更新">
        <el-switch v-model="editForm.is_update" :active-value="1" :inactive-value="0" active-text="是" inactive-text="否" />
      </el-form-item>
    </el-form>
    <span slot="footer">
      <el-button type="primary" :loading="isEditLoading" @click="submitEditCard">确定</el-button>
      <el-button @click="showEditCard = false">取消</el-button>
    </span>
  </el-dialog>
  </div>
</template>

<script>
import { cardList } from '@/form'
import paging from '@/mixins/paging'
import { getStore, editStore, addSpiderCard, deleteSpiderCard, editSpiderCard } from '@/api'
import BaseFile from '@/components/ProForm/baseFile.vue'

export default {
  name: 'CardList',
  mixins: [paging],
  components: { BaseFile },
  data() {
    return {
      SFC: cardList.SFC,
      show: false,
      column: {
        card_img: 'goods.image',
        card_name: 'goods.zh_name',
        card_describe: 'goods.zh_desc',
        card_brand: 'cardList.card_brand',
        card_type: 'cardList.card_type',
        conditions: 'cardList.conditions',
        deal_price: 'goods.price',
        is_update: 'cardList.is_update',
        created_at: 'common.createdAt'
      },
      columnAttr: {
        card_img: { width: '140px' },
        card_name: { minWidth: '140px', showOverflowTooltip: true },
        card_describe: { minWidth: '200px', showOverflowTooltip: true },
        card_brand: { minWidth: '100px', showOverflowTooltip: true },
        conditions: { minWidth: '180px', showOverflowTooltip: true },
        deal_price: { width: '100px', sortable: 'custom' },
        is_update: { width: '100px' },
        created_at: { width: '160px' }
      },
      // 卡牌设置
      showCardSettings: false,
      cardSettings: {
        recycle_percent: 0,
        sell_percent: 100,
        import_stock: 1
      },
      // 新增卡牌
      showAddCard: false,
      isAddLoading: false,
      // 编辑卡牌
      showEditCard: false,
      isEditLoading: false,
      editForm: {
        id: null,
        card_name: '',
        card_img: '',
        deal_price: 0,
        is_update: 0
      },
      conditionKeyword: '',
      sortField: '',
      sortOrder: '',
      selectedIds: [],
      addForm: {
        card_name: '',
        card_brand: '',
        card_category: '',
        card_type: 'single',
        deal_price: 0,
        card_cost: 0,
        deal_condition: '',
        card_img: '',
        card_describe: ''
      }
    }
  },
  created() {
    this.loadCardSettings()
    this.getList('SpiderCard')
  },
  methods: {
    // 提取分页额外参数
    getExtraParams() {
      const params = {}
      if (this.conditionKeyword) {
        params.deal_condition = this.conditionKeyword
      }
      if (this.sortField) {
        params.sort_by = this.sortField
        params.sort_order = this.sortOrder
      }
      return params
    },
    // 排序变更
    onSortChange({ prop, order }) {
      if (order === 'ascending' || order === 'descending') {
        this.sortField = prop
        this.sortOrder = order === 'ascending' ? 'asc' : 'desc'
      } else {
        this.sortField = ''
        this.sortOrder = ''
      }
      this.getList('SpiderCard', this.getExtraParams())
    },
    // 从后端载入卡牌设置
    async loadCardSettings() {
      try {
        const { results } = await getStore()
        if (results) {
          this.cardSettings.recycle_percent = results.card_recycle_percent ?? 0
          this.cardSettings.sell_percent = results.card_sell_percent ?? 100
          this.cardSettings.import_stock = results.card_import_stock ?? 1
        }
      } catch (e) {
        // 后端没有栏位时，尝试从 localStorage 载入
        const saved = localStorage.getItem('cardCardSettings')
        if (saved) {
          try {
            Object.assign(this.cardSettings, JSON.parse(saved))
          } catch (e2) {}
        }
      }
    },
    // 储存卡牌设置到后端
    async saveCardSettings() {
      try {
        await editStore({
          shop_id: 1,
          card_recycle_percent: this.cardSettings.recycle_percent,
          card_sell_percent: this.cardSettings.sell_percent,
          card_import_stock: this.cardSettings.import_stock
        })
        this.showCardSettings = false
        this.$message({ message: '设置已储存', type: 'success' })
      } catch (e) {
        this.$message({ message: '储存失败', type: 'error' })
      }
    },
    // 成色筛选
    applyConditionFilter() {
      this.getList('SpiderCard', { ...this.getExtraParams(), ...this.searchForm })
    },
    // 多选变更
    onSelectionChange(rows) {
      this.selectedIds = (rows || []).map(r => r.id)
    },
    // 单行删除
    onDelete(row) {
      this.$confirm('确定删除此卡牌？', '提示', { type: 'warning' }).then(async () => {
        try {
          await deleteSpiderCard({ ids: [row.id] })
          this.$message({ message: '删除成功', type: 'success' })
          this.getList('SpiderCard', { page: this.page.current, ...this.getExtraParams(), ...this.searchForm })
        } catch (e) {
          // 错误由拦截器处理
        }
      }).catch(() => {})
    },
    // 批量删除
    onBatchDelete() {
      if (this.selectedIds.length === 0) return
      this.$confirm(`确定删除所选的 ${this.selectedIds.length} 张卡牌？`, '提示', { type: 'warning' }).then(async () => {
        try {
          await deleteSpiderCard({ ids: this.selectedIds })
          this.$message({ message: '删除成功', type: 'success' })
          this.selectedIds = []
          this.getList('SpiderCard', { page: this.page.current, ...this.getExtraParams(), ...this.searchForm })
        } catch (e) {
          // 错误由拦截器处理
        }
      }).catch(() => {})
    },
    // 重置新增表单
    resetAddForm() {
      this.addForm = {
        card_name: '',
        card_brand: '',
        card_category: '',
        card_type: 'single',
        deal_price: 0,
        card_cost: 0,
        deal_condition: '',
        card_img: '',
        card_describe: ''
      }
      this.$refs.addForm && this.$refs.addForm.clearValidate()
    },
    // 新增卡牌
    async submitAddCard() {
      if (!this.addForm.card_name) {
        return this.$message({ message: '请输入卡牌名称', type: 'warning' })
      }
      this.isAddLoading = true
      try {
        await addSpiderCard(this.addForm)
        this.$message({ message: '新增成功', type: 'success' })
        this.showAddCard = false
        this.getList('SpiderCard', { page: this.page.current, ...this.getExtraParams(), ...this.searchForm })
      } catch (e) {
        // 错误由拦截器处理
      } finally {
        this.isAddLoading = false
      }
    },
    // 编辑卡牌
    onEdit(row) {
      this.editForm = {
        id: row.id,
        card_name: row.card_name || '',
        card_img: row.card_img || '',
        deal_price: row.deal_price ?? 0,
        is_update: row.is_update ?? 0
      }
      this.showEditCard = true
    },
    async submitEditCard() {
      if (this.editForm.deal_price < 0) {
        return this.$message({ message: '价格不能为负数', type: 'warning' })
      }
      this.isEditLoading = true
      try {
        await editSpiderCard({
          id: this.editForm.id,
          card_img: this.editForm.card_img,
          deal_price: this.editForm.deal_price,
          is_update: this.editForm.is_update
        })
        this.$message({ message: '编辑成功', type: 'success' })
        this.showEditCard = false
        this.getList('SpiderCard', { page: this.page.current, ...this.getExtraParams(), ...this.searchForm })
      } catch (e) {
        // 错误由拦截器处理
      } finally {
        this.isEditLoading = false
      }
    },
    resetEditForm() {
      this.editForm = { id: null, card_name: '', card_img: '', deal_price: 0, is_update: 0 }
    }
  }
}
</script>
<style scoped>
.card-condition-badge {
  position: absolute;
  top: -5px;
  left: 20%;
  right: 10%;
  background: rgba(220, 20, 60, 0.65);
  color: #ffd700;
  font-size: 10px;
  font-weight: 700;
  padding: 1px 0;
  border-radius: 0 0 4px 4px;
  line-height: 2;
  pointer-events: none;
  z-index: 2;
  text-align: center;
  letter-spacing: 0.5px;
}
</style>
