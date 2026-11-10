<template>
  <div class="app-container">
    <div class="mb20">
      <el-button type="warning" icon="el-icon-refresh" :loading="isInitLoad" @click="onAutoInit">{{ $t('drawPreset.autoInit') }}</el-button>
    </div>
    <ProTable
      index
      border
      :is-loading="isLoading"
      :data="list"
      :page="page"
      :column="column"
      :row-control="['edit', 'del']"
      @row-control-click="onRcc"
      @page-change="onPageChange('DrawPreset', $event)"
    >
      <template #card_sort_name="{ row }">
        <span>{{ row.card_sort_name || $t('common.null') }}</span>
      </template>
      <template #card_count="{ row }">
        <span>{{ row.total_card_count != null ? row.total_card_count : (row.preset_data ? row.preset_data.length : 0) }}</span>
      </template>
      <template #is_lock="{ row }">
        <el-tag v-if="row.is_lock" type="warning" size="mini">
          <i class="el-icon-lock"></i>
        </el-tag>
        <el-tag v-else type="success" size="mini">
          <i class="el-icon-unlock"></i>
        </el-tag>
      </template>
      <template #created_at="{ row }">
        <span>{{ row.created_at }}</span>
      </template>
    </ProTable>

    <el-dialog
      :title="$t('route.DrawPreset')"
      :visible.sync="showEdit"
      :fullscreen="true"
      :close-on-click-modal="false"
      @close="resetEdit"
    >
      <el-form label-position="top">
        <el-form-item :label="$t('goods.cate')" required>
          <el-select v-model="editSortId" :placeholder="$t('common.selectPh')" style="width:100%" @change="onSortChange">
            <el-option
              v-for="item in sortList"
              :key="item.card_sort_id"
              :label="item.card_sort_name"
              :value="item.card_sort_id"
            />
          </el-select>
        </el-form-item>
      </el-form>
      <template v-if="editSortId && isEditLoading">
      <div style="text-align:center;padding:40px 0;">
        <i class="el-icon-loading" style="font-size:32px;color:#409EFF;"></i>
        <p style="margin-top:10px;color:#999;">{{ $t('common.loading') }}</p>
      </div>
    </template>
    <template v-else-if="editSortId && editCards.length > 0">
        <div class="toolbar">
          <div class="toolbar-stats">
            <span class="stats-count">{{ $t('db.total') }} <strong>{{ editCards.length }}</strong></span>
            <span class="stats-divider"></span>
            <span class="stats-undrawn">{{ $t('cardList.undrawn') }} <strong>{{ undrawnCount }}</strong></span>
            <span class="stats-drawn">{{ $t('cardList.drawn') }} <strong>{{ drawnCount }}</strong></span>
          </div>
          <el-tag v-if="editIsLock" type="warning" effect="dark">
            <i class="el-icon-lock"></i> {{ $t('drawPreset.lockedSort') }}
          </el-tag>
          <el-button v-else type="primary" icon="el-icon-refresh" :loading="isShuffleLoad" @click="onShuffle">{{ $t('drawPreset.shuffle') }}</el-button>
        </div>

        <div v-if="editIsLock" class="draw-preset-locked-overlay">
          <div class="draw-preset-locked-msg">
            <i class="el-icon-warning"></i>
            <span>{{ $t('drawPreset.lockedSortTip') }}</span>
          </div>
        </div>

        <div class="draw-preset-card-list">
          <draggable
            v-model="editCards"
            class="draw-preset-card-horizontal"
            ghost-class="draw-preset-card-ghost"
            handle=".draw-preset-card-img-wrap"
            :disabled="editIsLock === 1"
            animation="200"
            tag="div"
          >
            <div
              v-for="(card, idx) in editCards"
              :key="card.card_id || card.product_id"
              class="draw-preset-card-item"
            >
              <div class="draw-preset-card-img-wrap">
                <el-image
                  class="card-img"
                  :src="card.card_img || card.product_img"
                  fit="cover"
                >
                  <div slot="error" class="draw-preset-card-img-error">
                    <i class="el-icon-picture-outline"></i>
                  </div>
                </el-image>
                <span v-if="card.card_tag || card.product_tag" class="draw-preset-card-tag" :style="{ background: getTagColor(card.card_tag || card.product_tag) }">{{ card.card_tag || card.product_tag }}</span>
              </div>
              <span class="draw-preset-card-index">{{ idx + 1 }}</span>
              <div class="draw-preset-card-info">
                <div class="draw-preset-card-name" :title="card.card_name || card.product_name">{{ card.card_name || card.product_name }}</div>
                <div class="draw-preset-card-en-name" :title="card.en_card_name || card.en_product_name">{{ card.en_card_name || card.en_product_name }}</div>
                <div class="draw-preset-card-meta">
                  <span class="draw-preset-card-price">HK$<span>{{ card.card_price || card.product_price }}</span></span>
                </div>
                <div class="draw-preset-card-status">
                  <el-tag v-if="(card.card_stock != null ? card.card_stock : card.product_stock) > 0" size="mini" type="success">{{ $t('cardList.undrawn') }}</el-tag>
                  <el-tag v-else size="mini" type="danger">{{ $t('cardList.drawn') }}</el-tag>
                </div>
                <div class="draw-preset-card-order">
                  <span class="order-tip">{{ $t('drawPreset.order') }}</span>
                  <el-input-number
                    :value="idx + 1"
                    :min="1"
                    :max="editCards.length"
                    :controls="false"
                    size="mini"
                    :disabled="editIsLock === 1"
                    @change="val => onMoveCard(card, idx, val)"
                  />
                </div>
              </div>
            </div>
          </draggable>
        </div>
      </template>

      <template v-else-if="editSortId">
        <el-empty :description="$t('common.null')" />
      </template>

      <span slot="footer" class="dialog-footer">
        <el-button @click="showEdit = false">{{ $t('common.cancel') }}</el-button>
        <el-button type="primary" :loading="isSaveLoad" :disabled="!editSortId || editIsLock === 1" @click="onSave">{{ $t('common.confirm') }}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { getPresetCards, saveDrawPreset, shuffleDrawPreset, autoCreatePresets, delDrawPreset, unlockDrawPreset, getAllCardSort } from '@/api'
import paging from '@/mixins/paging'
import draggable from 'vuedraggable'

export default {
  name: 'DrawPreset',
  components: { draggable },
  mixins: [paging],
  data() {
    return {
      column: {
        product_sort_name: 'drawPreset.productSort',
        card_count: 'drawPreset.cardCount',
        is_lock: 'drawPreset.isLock',
        created_at: 'common.created_at'
      },
      showEdit: false,
      editSortId: null,
      editCards: [],
      editPresetId: null,
      sortList: [],
      isShuffleLoad: false,
      isSaveLoad: false,
      isInitLoad: false,
      isEditLoading: false,
      editIsLock: 0
    }
  },
  created() {
    this.init()
    this.onAutoInit()
  },
  computed: {
    undrawnCount() {
      return this.editCards.filter(c => ((c.card_stock ?? c.product_stock) || 0) > 0).length
    },
    drawnCount() {
      return this.editCards.filter(c => {
        const s = c.card_stock ?? c.product_stock
        return s === undefined || s === null || s <= 0
      }).length
    }
  },
  methods: {
    async init() {
      try {
        const res = await getAllCardSort()
        this.sortList = (res.results || []).map(s => ({
          card_sort_id: s.card_sort_id,
          card_sort_name: s.card_sort_name
        }))
      } catch (error) {
        console.log(error)
      }
    },
    onAdd() {
      this.editPresetId = null
      this.editSortId = null
      this.editCards = []
      this.showEdit = true
    },
    onAutoInit() {
      this.isInitLoad = true
      autoCreatePresets().then(res => {
        const msg = res.results
        if (msg.created > 0) {
          this.$message({
            message: msg.created + ' ' + this.$t('drawPreset.autoInitDone'),
            type: 'success'
          })
        }
      }).catch(() => {}).finally(() => {
        this.isInitLoad = false
        this.getList('DrawPreset')
      })
    },
    async onUnlock(row) {
      try {
        await unlockDrawPreset({ preset_id: row.preset_id })
        this.$message({ message: this.$t('common.success'), type: 'success' })
        this.getList('DrawPreset')
      } catch (error) {
        console.log(error)
      }
    },
    onRcc({ scope: { row }, type }) {
      if (type === 'edit') {
        this.editPresetId = row.preset_id
        this.editIsLock = row.is_lock || 0
        // 先清除资料避免显示旧资料
        this.editCards = []
        // 使用标记避免 @change 事件干扰
        this._loadingSortId = row.card_sort_id
        this.editSortId = row.card_sort_id
        this.showEdit = true
        this.loadEditCards()
      } else if (type === 'del') {
        this.$confirm(this.$t('common.sure'), this.$t('common.tips'), {
          confirmButtonText: this.$t('common.confirm'),
          cancelButtonText: this.$t('common.cancel'),
          type: 'warning'
        }).then(async () => {
          await delDrawPreset({ preset_id: row.preset_id })
          this.$message({ message: this.$t('common.success'), type: 'success' })
          this.getList('DrawPreset')
        }).catch(() => {})
      }
    },
    async loadEditCards() {
      if (!this.editSortId) return
      this.isEditLoading = true
      this.editCards = []
      try {
        const res = await getPresetCards({ product_sort_id: this.editSortId })
        this._applyPresetData(res.results)
      } catch (error) {
        console.log(error)
        this.editCards = []
      }
      this.isEditLoading = false
    },
    /**
     * 将 getPresetCards API 的回应资料映射为 editCards（相容 product_* 旧格式）
     */
    _applyPresetData(data) {
      const allCards = data.cards || []
      const stockMap = {}
      const tagMap = {}
      allCards.forEach(c => {
        stockMap[c.card_id] = c.card_stock
        tagMap[c.card_id] = c.card_tag || ''
      })
      if (data.preset_data) {
        this.editCards = data.preset_data.map(c => {
          const pid = c.card_id || c.product_id
          return {
            ...c,
            card_id: pid,
            card_name: c.card_name || c.product_name,
            en_card_name: c.en_card_name || c.en_product_name || '',
            card_img: c.card_img || c.product_img || '',
            card_price: c.card_price || c.product_price || 0,
            card_cost: c.card_cost || c.product_cost || 0,
            card_sort_id: c.card_sort_id || c.product_sort_id,
            card_stock: stockMap[pid] != null ? stockMap[pid] : (c.card_stock ?? c.product_stock ?? 1),
            card_tag: tagMap[pid] || c.card_tag || c.product_tag || ''
          }
        })
        this.editPresetId = data.preset_id
      } else {
        this.editCards = allCards.map(c => ({
          card_id: c.card_id,
          card_name: c.card_name,
          en_card_name: c.en_card_name || '',
          card_img: c.card_img || '',
          card_price: c.card_price || 0,
          card_cost: c.card_cost || 0,
          card_sort_id: c.card_sort_id,
          card_stock: c.card_stock != null ? c.card_stock : 1,
          card_tag: c.card_tag || ''
        }))
        this.editPresetId = null
      }
      this.editIsLock = data.is_lock || 0
    },
    async onSortChange(val) {
      if (!val) return
      // 如果是 onRcc 正在载入中，跳过此事件避免竞态
      if (this._loadingSortId === val) {
        this._loadingSortId = null
        return
      }
      this.editCards = []
      this.editPresetId = null
      this.isEditLoading = true
      try {
        const res = await getPresetCards({ product_sort_id: val })
        this._applyPresetData(res.results)
      } catch (error) {
        console.log(error)
        this.editCards = []
      }
      this.isEditLoading = false
    },
    async onShuffle() {
      this.isShuffleLoad = true
      try {
        const res = await shuffleDrawPreset({ product_sort_id: this.editSortId })
        this.editCards = (res.results.shuffled_cards || []).map(c => ({
          card_id: c.card_id,
          card_name: c.card_name,
          en_card_name: c.en_card_name || '',
          card_img: c.card_img || '',
          card_price: c.card_price || 0,
          card_cost: c.card_cost || 0,
          card_sort_id: c.card_sort_id,
          card_stock: c.card_stock != null ? c.card_stock : 1,
          card_tag: c.card_tag || ''
        }))
      } catch (error) {
        console.log(error)
      } finally {
        this.isShuffleLoad = false
      }
    },
    // 输入序号后把该卡移动到对应位置（1 开始）
    onMoveCard(card, fromIdx, target) {
      const n = parseInt(target, 10)
      const len = this.editCards.length
      if (Number.isNaN(n) || n < 1) return
      const to = Math.min(len, n) - 1
      if (to === fromIdx) return
      const arr = this.editCards.slice()
      arr.splice(fromIdx, 1)
      arr.splice(to, 0, card)
      this.editCards = arr
    },
    // 根据标签名称生成不同颜色
    getTagColor(tag) {
      if (!tag) return 'rgba(220,20,60,0.65)'
      const colors = [
        'rgba(220,20,60,0.75)',
        'rgba(30,144,255,0.75)',
        'rgba(50,205,50,0.75)',
        'rgba(255,165,0,0.75)',
        'rgba(147,112,219,0.75)',
        'rgba(255,99,71,0.75)',
        'rgba(0,206,209,0.75)',
        'rgba(255,20,147,0.75)',
        'rgba(60,179,113,0.75)',
        'rgba(106,90,205,0.75)',
        'rgba(218,165,32,0.75)',
        'rgba(240,128,128,0.75)',
      ]
      let hash = 0
      for (let i = 0; i < tag.length; i++) {
        hash = tag.charCodeAt(i) + ((hash << 5) - hash)
      }
      return colors[Math.abs(hash) % colors.length]
    },
    async onSave() {
      this.isSaveLoad = true
      try {
        const cards = this.editCards.map(c => ({
          card_id: c.card_id,
          card_name: c.card_name,
          en_card_name: c.en_card_name || '',
          card_img: c.card_img || '',
          card_price: c.card_price || 0,
          card_cost: c.card_cost || 0,
          card_sort_id: c.card_sort_id,
          card_stock: c.card_stock != null ? c.card_stock : 1,
          card_tag: c.card_tag || ''
        }))
        await saveDrawPreset({
          product_sort_id: this.editSortId,
          preset_data: cards
        })
        this.$message({ message: this.$t('common.success'), type: 'success' })
        this.showEdit = false
        this.getList('DrawPreset')
      } catch (error) {
        console.log(error)
      } finally {
        this.isSaveLoad = false
      }
    },
    resetEdit() {
      this.editSortId = null
      this.editCards = []
      this.editPresetId = null
      this.editIsLock = 0
    }
  }
}
</script>

<style>
.dialog-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-bottom: 16px;
  border-bottom: 1px solid #ebeef5;
  margin-bottom: 16px;
}
.dialog-header-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 16px;
}
.dialog-header-text {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
}
.dialog-header-sub {
  font-size: 12px;
  color: #909399;
  margin-left: auto;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #f8f9fc;
  border-radius: 8px 8px 0 0;
  border: 1px solid #ebeef5;
  border-bottom: none;
}
.toolbar-stats {
  display: flex;
  align-items: center;
  gap: 12px;
}
.stats-count {
  font-size: 14px;
  color: #606266;
}
.stats-count strong {
  color: #409eff;
  font-size: 18px;
  margin: 0 2px;
}
.stats-divider {
  width: 1px;
  height: 18px;
  background: #dcdfe6;
}
.stats-undrawn {
  font-size: 13px;
  color: #67c23a;
}
.stats-undrawn strong {
  font-size: 16px;
  margin: 0 2px;
}
.stats-drawn {
  font-size: 13px;
  color: #f56c6c;
}
.stats-drawn strong {
  font-size: 16px;
  margin: 0 2px;
}

/* 卡片横向拖动排序 - 不使用 scoped 以相容 vuedraggable */
.draw-preset-card-list {
  max-height: 560px;
  overflow-y: auto;
  border: 1px solid #ebeef5;
  border-radius: 0 0 8px 8px;
  padding: 8px;
}
.draw-preset-card-list::-webkit-scrollbar {
  width: 5px;
}
.draw-preset-card-list::-webkit-scrollbar-thumb {
  background: #dcdfe6;
  border-radius: 3px;
}
.draw-preset-card-horizontal {
  display: flex !important;
  flex-wrap: wrap;
  gap: 12px;
  min-height: 240px;
  align-content: flex-start;
}
.draw-preset-card-horizontal .draw-preset-card-item {
  flex: 0 0 auto;
  width: 158px;
  background: #fff;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  padding: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: box-shadow 0.2s, border-color 0.2s;
  position: relative;
}
.draw-preset-card-horizontal .draw-preset-card-item:hover {
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
  border-color: #c0c4cc;
}
.draw-preset-card-ghost {
  opacity: 0.4;
  border: 2px dashed #409eff !important;
}
.draw-preset-card-index {
  position: absolute;
  top: 4px;
  right: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #f0f2f5;
  color: #909399;
  font-size: 11px;
  font-weight: 600;
  z-index: 1;
}
.draw-preset-card-horizontal .draw-preset-card-item:first-child .draw-preset-card-index {
  background: #e6f7ff;
  color: #1890ff;
}
.draw-preset-card-horizontal .draw-preset-card-item:nth-child(2) .draw-preset-card-index {
  background: #fff7e6;
  color: #fa8c16;
}
.draw-preset-card-horizontal .draw-preset-card-item:nth-child(3) .draw-preset-card-index {
  background: #f6ffed;
  color: #52c41a;
}

.draw-preset-card-img-wrap {
  position: relative;
  width: 100%;
  height: 120px;
  border-radius: 6px;
  overflow: hidden;
  margin-bottom: 6px;
  background: #f5f5f5;
  border: 1px solid #f0f0f0;
  cursor: grab;
}
.draw-preset-card-tag {
  position: absolute;
  top: 0;
  left: 26%;
  right: 25%;
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  padding: 2px 0;
  border-radius: 0 0 4px 4px;
  line-height: 1.6;
  pointer-events: none;
  z-index: 2;
  text-align: center;
  letter-spacing: 0.5px;
}
.draw-preset-card-img-wrap:active {
  cursor: grabbing;
}
.draw-preset-card-img-wrap img,
.draw-preset-card-img-wrap .el-image img,
.draw-preset-card-img-wrap .card-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.draw-preset-card-img-error {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #c0c4cc;
  font-size: 24px;
}
.draw-preset-locked-overlay {
  background: #fdf6ec;
  border: 1px solid #faecd8;
  border-radius: 6px;
  padding: 10px 16px;
  margin-bottom: 10px;
}
.draw-preset-locked-msg {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #e6a23c;
  font-size: 14px;
}
.draw-preset-locked-msg i {
  font-size: 18px;
}
.draw-preset-card-info {
  width: 100%;
  text-align: center;
}
.draw-preset-card-name {
  font-size: 12px;
  font-weight: 600;
  color: #303133;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.draw-preset-card-en-name {
  font-size: 11px;
  color: #c0c4cc;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 4px;
}
.draw-preset-card-meta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-bottom: 4px;
}
.draw-preset-card-price {
  font-size: 11px;
  color: #909399;
}
.draw-preset-card-price span {
  color: #e6a23c;
  font-weight: 600;
}
.draw-preset-card-status {
  display: flex;
  justify-content: center;
}
.draw-preset-card-status .el-tag--mini {
  height: 18px;
  line-height: 16px;
  padding: 0 6px;
  font-size: 11px;
}
.draw-preset-card-order {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  margin-top: 4px;
}
.draw-preset-card-order .order-tip {
  font-size: 11px;
  color: #909399;
}
.draw-preset-card-order .el-input-number {
  width: 72px;
}
.draw-preset-card-order .el-input-number .el-input__inner {
  padding: 0 4px;
  text-align: center;
  font-size: 12px;
}

.card-empty {
  text-align: center;
  padding: 60px 0;
  color: #c0c4cc;
}
.card-empty-icon {
  font-size: 48px;
  margin-bottom: 12px;
}
.card-empty-text {
  font-size: 14px;
}

@media (max-width: 768px) {
  .card-img-wrap {
    width: 44px;
    height: 44px;
  }
  .card-meta {
    flex-wrap: wrap;
  }
}
</style>
