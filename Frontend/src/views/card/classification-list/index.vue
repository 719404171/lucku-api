<template>
  <div class="app-container">
    <div v-if="device === 'desktop'" class="mb20">
      <el-button v-if="nodePower.includes('add')" type="success" icon="el-icon-plus" @click="show = true">新增</el-button>
      <el-button v-if="nodePower.includes('del')" class="mr10" type="danger" icon="el-icon-delete" :disabled="!delIds.length" @click="del">批量删除</el-button>
      <el-dropdown class="mr10" @command="batchDrawEnable">
        <el-button type="warning" icon="el-icon-switch-button">{{ $t('classificationList.batchDraw') }}<i class="el-icon-arrow-down el-icon--right" /></el-button>
        <el-dropdown-menu slot="dropdown">
          <el-dropdown-item command="1">{{ $t('classificationList.drawOn') }}</el-dropdown-item>
          <el-dropdown-item command="0">{{ $t('classificationList.drawOff') }}</el-dropdown-item>
        </el-dropdown-menu>
      </el-dropdown>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC" />
        <el-button type="primary" @click="onSearch('Cate')">确定</el-button>
        <el-button slot="reference" type="primary" icon="el-icon-search">搜索</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Cate')" />
    </div>
    <div v-else class="mb20">
      <el-button v-if="nodePower.includes('add')" type="success" icon="el-icon-plus" @click="show = true" />
      <el-button v-if="nodePower.includes('del')" class="mr10" type="danger" icon="el-icon-delete" :disabled="!delIds.length" @click="del" />
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC" />
        <el-button type="primary" @click="onSearch('Cate')">确定</el-button>
        <el-button slot="reference" type="primary" icon="el-icon-search" />
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'GoodsCate')" />
    </div>

    <ProTable
      index
      selection
      border
      control-width="240px"
      :is-loading="isLoading"
      :data="list"
      :page="page"
      :column="column"
      :column-attr="{
        card_sort_url_single: { minWidth: '140px', showOverflowTooltip: true },
        card_sort_url_ten: { minWidth: '140px', showOverflowTooltip: true },
        card_sort_url_hundred: { minWidth: '140px', showOverflowTooltip: true },
        card_brands: { minWidth: '180px' }
      }"
      :row-control="['edit', 'del']"
      @row-control-click="onRcc"
      @page-change="onPageChange('Cate', $event)"
      @selection-change="delIds = $event.map(({ card_sort_id }) => card_sort_id)"
    >
      <template #control="{ scope }">
        <el-link class="row-control-item" :class="$store.state.app.size" type="primary" :underline="false" @click="view(scope.row)">关联</el-link>
        <el-link class="row-control-item" :class="$store.state.app.size" type="primary" :underline="false" icon="el-icon-gift" @click="openBonus(scope.row)">額外獎勵</el-link>
        <el-link
          class="row-control-item"
          :class="$store.state.app.size"
          type="primary"
          :underline="false"
          icon="el-icon-refresh"
          @click="initPool(scope.row)"
        >初始化</el-link>
      </template>
      <template #card_sort_img="{ row }">
        <ProImage :src="row.card_sort_img || ''" />
      </template>
      <template #undrawn_count="{ row }">
        <el-tag type="success">{{ row.undrawn_count != null ? row.undrawn_count : 0 }}</el-tag>
      </template>
      <template #drawn_count="{ row }">
        <el-tag type="danger">{{ row.drawn_count != null ? row.drawn_count : 0 }}</el-tag>
      </template>
      <template #status="{ row }">
        <el-tag :type="row.status ? '' : 'danger'">{{ row.status ? '正常' : '禁用' }}</el-tag>
      </template>
      <template #card_brands="{ row }">
        <el-tag v-for="brand in (row.card_brands || [])" :key="brand" size="mini" style="margin:2px 4px 2px 0">{{ brand }}</el-tag>
        <span v-if="!row.card_brands || !row.card_brands.length" style="color:#909399">—</span>
      </template>
      <template #draw_limit_per_user="{ row }">
        <span v-if="Number(row.draw_limit_per_user) <= 0" style="color:#67c23a;font-weight:500">∞ 無限</span>
        <span v-else>{{ row.draw_limit_per_user }} 次</span>
      </template>
      <template #daily_draw_limit="{ row }">
        <span v-if="Number(row.daily_draw_limit) <= 0" style="color:#67c23a;font-weight:500">∞ 無限</span>
        <span v-else>{{ row.daily_draw_limit }} 次/日</span>
      </template>
      <template #member_level_limit="{ row }">
        <span v-if="!levelLimitList(row).length" style="color:#67c23a;font-weight:500">∞ 不限制</span>
        <span v-else>
          <span v-for="(lv, idx) in levelLimitList(row)" :key="lv.member_id">
            {{ lv.name }}（{{ Number(lv.draw_limit) > 0 ? lv.draw_limit + ' 张' : '不限' }}）<span v-if="idx < levelLimitList(row).length - 1">、</span>
          </span>
        </span>
      </template>
      <template #draw_enable="{ row }">
        <el-tooltip :disabled="isDrawOn(row) || !!row.has_locked_preset" :content="$t('classificationList.drawNeedLockedPreset')" placement="top">
          <span>
            <el-switch
              :value="isDrawOn(row)"
              :disabled="!isDrawOn(row) && !row.has_locked_preset"
              @change="toggleDrawEnable(row, $event)"
            />
          </span>
        </el-tooltip>
      </template>
      <template #is_once_only="{ row }">
        <el-switch :value="isOnceOnlyOn(row)" @change="toggleOnceOnly(row, $event)" />
      </template>
    </ProTable>

    <el-dialog
      :title="editDialogTitle"
      :visible.sync="show"
      :width="device === 'desktop' ? '800px' : '100%'"
      :close-on-click-modal="false"
      @closed="onFormClose"
    >
      <ProForm ref="editForm" :echo="echo" :config="EFCConfig" :rules="rules" />
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">取消</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">确定</el-button>
      </span>
    </el-dialog>

    <el-dialog
      :title="'卡片关联 - ' + detailSortName"
      :visible.sync="showDetail"
      :fullscreen="true"
      :close-on-click-modal="false"
      @close="handleDetailClose"
    >
      <div v-loading="detailLoading" element-loading-text="Loading...">
        <!-- 抽卡價格公式：卡牌總價 ÷ 卡牌數量 × 百分比 = 抽卡價格（取整） -->
        <div class="price-formula-bar">
          <div class="pf-group">
            <span class="pf-label">卡牌數量</span>
            <b>{{ priceStats.count }}</b> 張
          </div>
          <div class="pf-group">
            <span class="pf-label">卡牌總價（正價）</span>
            <b>${{ money(priceStats.total_price) }}</b>
          </div>
          <div class="pf-group">
            <span class="pf-label">手動百分比</span>
            <el-input-number
              v-model="pricePercent"
              :min="0"
              :max="999"
              :precision="2"
              :step="10"
              size="small"
              controls-position="right"
              class="pf-percent-input"
            />
            <span class="pf-pct">%</span>
          </div>
          <div class="pf-formula">
            ${{ money(priceStats.total_price) }} ÷ {{ priceStats.count }} × {{ pricePercent }}% =
            <b class="pf-result">${{ previewDrawPrice }}</b>
            <span class="pf-hint">（取整）</span>
          </div>
          <div class="pf-group">
            <span class="pf-label">目前抽卡價格</span>
            <b class="pf-current">${{ money(priceStats.card_draw_price) }}</b>
          </div>
          <div class="pf-group pf-switch-group">
            <span class="pf-label">每日自動更新</span>
            <el-switch
              :value="autoUpdatePrice === 1"
              active-color="#13ce66"
              :loading="savingAutoUpdate"
              @change="toggleAutoUpdatePrice"
            />
            <span class="pf-hint">按系統設置時間（{{ priceStats.auto_update_time1 || '05:00' }} / {{ priceStats.auto_update_time2 || '18:00' }}，延遲5分鐘）每日自動重算</span>
          </div>
          <el-button type="primary" size="small" :loading="savingPrice" @click="saveDrawPriceSetting">保存並更新抽卡價格</el-button>
        </div>
        <el-tabs v-model="detailTab" type="border-card" style="min-height:600px">
          <!-- 已关联 Tab - 标签分页 + 拖拽 -->
          <el-tab-pane :label="associatedTabLabel" name="associated">
            <!-- 标签栏 -->
            <div class="tag-tabs">
              <div
                v-for="tag in tags"
                :key="tag"
                :class="['tag-tab-item', { 'tag-tab-active': activeTag === tag, 'tag-tab-dragover': dragOverTag === tag }]"
                @click="onTagClick(tag)"
                @dragover.prevent="dragOverTag = tag"
                @dragleave="dragOverTag = null"
                @drop.prevent="onDropToTag(tag)"
              >
                {{ tag }}
                <i
                  v-if="tag !== '预设'"
                  :class="['el-icon-video-camera', 'tag-tab-anim', { 'tag-anim-set': tagAnimations[tag] }]"
                  :title="tagAnimations[tag] ? '中奖动画: ' + tagAnimations[tag] + '（点击从文件管理更换）' : '设置该标签专属中奖动画（从文件管理选择，未设置抽中时用默认动画）'"
                  @click.stop="setTagAnimation(tag)"
                ></i>
                <i
                  v-if="tag !== '预设' && tagAnimations[tag]"
                  class="el-icon-circle-check tag-anim-check"
                  :title="'已设置中奖动画: ' + tagAnimations[tag]"
                ></i>
                <i
                  v-if="tag !== '预设' && tagAnimations[tag]"
                  class="el-icon-refresh-left tag-anim-clear"
                  title="清除该标签专属动画，恢复使用默认动画"
                  @click.stop="clearTagAnimation(tag)"
                ></i>
                <i
                  v-if="tag !== '预设'"
                  class="el-icon-rank tag-tab-drag"
                  :title="'拖动排序（越靠前奖励越大，抽中时优先显示靠前标签的动画）'"
                  draggable="true"
                  @dragstart.stop="onTagDragStart(tag, $event)"
                  @dragend.stop="onTagDragEnd()"
                ></i>
                <i
                  v-if="tags.length > 1 && tag !== '预设'"
                  class="el-icon-close tag-tab-close"
                  @click.stop="removeTag(tag)"
                ></i>
              </div>
              <el-button type="text" size="small" icon="el-icon-plus" @click="addTag" class="tag-add-btn">新增</el-button>
            </div>
            <!-- 当前标签卡片 -->
            <div class="tab-toolbar">
              <el-checkbox
                v-model="assocSelectAll"
                :indeterminate="assocIndeterminate"
                size="small"
                style="margin-right:auto"
              >全选</el-checkbox>
              <span class="tag-card-count">{{ activeTag }} ({{ currentTagCards.length }})</span>
              <span class="tag-stats" v-if="tagStats[activeTag]">
                总共 {{ tagStats[activeTag].total }} 张 · 未抽取 {{ tagStats[activeTag].undrawn }} 张
              </span>
              <div style="display:flex;gap:6px;">
                <el-button
                  type="success"
                  size="small"
                  :disabled="!selectedAssoc.length"
                  @click="batchSetPriority(1)"
                >开启展示</el-button>
                <el-button
                  type="warning"
                  size="small"
                  :disabled="!selectedAssoc.length"
                  @click="batchSetPriority(0)"
                >关闭展示</el-button>
                <el-button
                  type="danger"
                  size="small"
                  :disabled="!selectedAssoc.length"
                  :loading="subLoading"
                  @click="batchUnassociate"
                >取消关联 ({{ selectedAssoc.length }})</el-button>
              </div>
            </div>
            <!-- 可拖拽卡片区域 -->
            <div v-if="currentTagCards.length" class="card-grid">
              <draggable
                v-model="currentTagCards"
                group="card-tags"
                :sort="false"
                @change="onCardDragChange"
                :style="{ display: 'flex', flexWrap: 'wrap', gap: '14px', padding: '8px 0' }"
              >
                <div
                  v-for="card in currentTagCards"
                  :key="'a-'+card.card_id"
                  :class="['card-item', { 'card-item-selected': selectedAssoc.includes(card.card_id), 'card-item-drawn': !card.card_stock || card.card_stock <= 0 }]"
                  @click="toggleAssocSelect(card.card_id)"
                  @mousedown="onCardMouseDown($event, card)"
                >
                  <div class="card-img-wrap">
                    <el-image
                      style="width:110px;height:110px;border-radius:8px"
                      :src="card.card_img"
                      fit="cover"
                      lazy
                      :class="{ 'img-drawn': !card.card_stock || card.card_stock <= 0 }"
                    >
                      <div slot="error" class="card-img-error">
                        <i class="el-icon-picture-outline"></i>
                      </div>
                    </el-image>
                    <div v-if="card.conditions" class="card-condition-badge">{{ card.conditions }}</div>
                    <el-tag
                      v-if="!card.card_stock || card.card_stock <= 0"
                      size="mini"
                      type="danger"
                      class="card-status-tag"
                    >已抽取</el-tag>
                    <el-tag
                      v-else
                      size="mini"
                      type="success"
                      class="card-status-tag"
                    >未抽取</el-tag>
                  </div>
                  <div class="card-name" :title="displayName(card)">{{ displayName(card) }}</div>
                  <div class="card-price">${{ card.regular_price != null && card.regular_price !== '' ? card.regular_price : (card.card_cost || card.deal_price || card.card_price || 0) }}</div>
                  <div class="card-priority-row" @click.stop>
                    <el-switch
                      v-model="card.is_priority"
                      :active-value="1"
                      :inactive-value="0"
                      size="mini"
                      @change="val => onPriorityChange(card, val)"
                    />
                    <span class="priority-label">{{ card.is_priority == 1 ? '展示' : '隐藏' }}</span>
                  </div>
                  <el-checkbox
                    :value="selectedAssoc.indexOf(Number(card.card_id)) > -1"
                    @change="toggleAssocSelect(card.card_id)"
                    class="card-checkbox-el"
                  />
                </div>
              </draggable>
            </div>
            <el-empty v-if="!currentTagCards.length && !detailLoading" :description="emptyAssocDesc" />
          </el-tab-pane>
          <!-- 未关联 Tab -->
          <el-tab-pane :label="unassociatedTabLabel" name="unassociated">
            <div class="tab-toolbar">
              <el-input
                v-model="unassocSearch"
                :placeholder="'搜索卡牌名称'"
                size="small"
                clearable
                style="width:240px"
                @keyup.enter.native="loadCards"
                @clear="loadCards"
              />
              <el-checkbox
                v-model="unassocSelectAll"
                :indeterminate="unassocIndeterminate"
                size="small"
                style="margin-right:auto"
              >全选</el-checkbox>
              <el-button
                type="primary"
                size="small"
                :disabled="!selectedUnassoc.length"
                :loading="subLoading"
                @click="batchAssociate"
              >添加关联 ({{ selectedUnassoc.length }})</el-button>
            </div>
            <div v-if="unassociatedCards.length" class="card-grid">
              <div
                v-for="card in unassociatedCards"
                :key="'u-'+card.card_id"
                :class="['card-item', 
                  { 
                    'card-item-selected': selectedUnassoc.indexOf(Number(card.card_id)) > -1, 
                    'card-item-associated': card.is_associated == 1
                  }]"
              >
                <div class="card-img-wrap" @click="toggleUnassocSelect(card.card_id)">
                  <el-image
                    style="width:110px;height:110px;border-radius:8px"
                    :src="card.card_img"
                    fit="cover"
                  >
                    <div slot="error" class="card-img-error">
                      <i class="el-icon-picture-outline"></i>
                    </div>
                  </el-image>
                  <div v-if="card.conditions" class="card-condition-badge">{{ card.conditions }}</div>
                  <div class="card-status-corner">
                    <el-tag
                      v-if="card.is_associated == 1"
                      size="mini"
                      type="warning"
                    >已关联</el-tag>
                    <el-tag
                      v-else
                      size="mini"
                      type="info"
                    >未关联</el-tag>
                  </div>
                </div>
                <div class="card-name" :title="displayName(card)" @click="toggleUnassocSelect(card.card_id)">{{ displayName(card) }}</div>
                <div class="card-price" @click="toggleUnassocSelect(card.card_id)">${{ card.deal_price || card.card_price || card.card_cost || 0 }}</div>
                <div style="display:flex;align-items:center;justify-content:center;padding:2px 0;">
                  <el-input-number
                    v-model="unassocCounts[card.card_id]"
                    :min="1"
                    :max="9999"
                    size="mini"
                    style="width:100%;"
                    controls-position="right"
                    @click.native.stop
                    @change="val => onUnassocCountChange(card.card_id, val)"
                  />
                </div>
                <el-checkbox
                  :value="selectedUnassoc.indexOf(Number(card.card_id)) > -1"
                  @change="toggleUnassocSelect(card.card_id)"
                  class="card-checkbox-el"
                />
              </div>
            </div>
            <el-empty v-else :description="'暂无卡库卡片'" />
          </el-tab-pane>
        </el-tabs>
      </div>
      <span slot="footer">
        <el-button @click="onCancelDetail">取消</el-button>
      </span>
    </el-dialog>
    <!-- 标签中奖动画文件选择 -->
    <ProFile
      :show.sync="showAnimFile"
      :limit="1"
      :have="0"
      v-model="animSelectedFile"
    />
    <!-- 额外奖励规则弹窗 -->
    <el-dialog
      :close-on-click-modal="false"
      :visible.sync="bonusDialog"
      width="760px"
      :title="'額外獎勵 — ' + (bonusSort ? bonusSort.card_sort_name : '')"
      top="6vh"
    >
      <div v-if="!bonusHasTable" style="padding:20px;text-align:center;color:#f56c6c;">
        功能尚未啟用：請先執行數據庫遷移 <code>2026_11_13_00000*</code>
      </div>
      <div v-else>
        <div style="margin-bottom:12px;color:#909399;font-size:13px;">
          本池全局已抽：<b style="color:#409eff;">{{ bonusDrawnTotal }}</b> 張
          &nbsp;|&nbsp; 每配置一條「第 N 張」，即從卡池全局計數器中，
          每達到該 N 的倍數時額外發放一次獎勵（免費、正常進背包）。
          <i :style="{ color: '#e6a23c' }">獎勵卡必須從本池已關聯的卡中選擇。</i>
        </div>

        <el-button type="primary" size="small" icon="el-icon-plus" :disabled="bonusLoading" @click="addBonusRule">新增一條規則</el-button>

        <el-table
          v-loading="bonusLoading"
          :data="bonusRules"
          empty-text="暫無規則"
          style="margin-top:10px;width:100%;"
          size="mini"
          border
        >
          <el-table-column label="啟用" width="70" align="center">
            <template slot-scope="{ row }">
              <el-switch v-model="row.is_active" :active-value="1" :inactive-value="0" />
            </template>
          </el-table-column>
          <el-table-column label="每 N 張發一次" width="160">
            <template slot-scope="{ row }">
              <el-input-number v-model="row.trigger_at" :min="1" :step="1" size="small" controls-position="right" style="width:120px;" />
            </template>
          </el-table-column>
          <el-table-column label="獎勵卡牌" min-width="260">
            <template slot-scope="{ row }">
              <el-select v-model="row.card_id" filterable size="small" placeholder="請選擇獎勵卡" style="width:100%;">
                <el-option
                  v-for="c in bonusCards"
                  :key="c.card_id"
                  :label="(c.card_img ? '[圖] ' : '') + c.card_name + '（庫存 ' + c.card_stock + '）'"
                  :value="Number(c.card_id)"
                  :disabled="c.card_stock <= 0"
                />
              </el-select>
            </template>
          </el-table-column>
          <el-table-column label="已發放" width="80" align="center">
            <template slot-scope="{ row }">{{ row.issued_count || 0 }}</template>
          </el-table-column>
          <el-table-column label="操作" width="70" align="center">
            <template slot-scope="{ $index }">
              <el-button type="text" size="small" style="color:#f56c6c;" @click="removeBonusRule($index)">刪除</el-button>
            </template>
          </el-table-column>
        </el-table>

        <div style="margin-top:12px;color:#909399;font-size:12px;line-height:1.6;">
          提示：
          <br/>• 一次抽 100 張跨越多個 N（如 N=10、抽 100 張），會最多發放 10 次獎勵。
          <br/>• 獎勵卡庫存不足 / 已下架時，該次贈送自動跳過，不影響本次付費抽卡結果。
          <br/>• 獎勵卡免費發放，正常走訂單流程、正常進用戶背包。
        </div>
      </div>
      <span slot="footer">
        <el-button @click="bonusDialog = false">取消</el-button>
        <el-button type="primary" :loading="bonusSaving" :disabled="!bonusHasTable" @click="saveBonus">保存</el-button>
      </span>
    </el-dialog>
</div>
</template>

<script>
import { addCardCate, delCardCate, getCardsBySort, associateCards, updateCardTagBatch, saveCardTagOrder, saveCardTagAnimation, updateCardPriority, editCardSortDrawEnable, batchCardSortDrawEnable, editCardSortOnceOnly, getAllCardBrands, updateCardSortDrawPrice, initCardSort, reqLevel, getCardSortBonusRules, saveCardSortBonusRules } from '@/api'
import { classificationList } from '@/form'
import paging from '@/mixins/paging'
import draggable from 'vuedraggable'

// 新建卡池默認品牌
const DEFAULT_BRAND = 'Lucku'

export default {
  name: 'ClassificationList',
  components: { draggable, ProFile: () => import('@/components/ProFile/index.vue') },
  mixins: [paging],
  data() {
    return {
      SFC: classificationList.SFC,
      EFC: classificationList.EFC,
      show: false,
      delIds: [],
      column: {
        card_sort_img: 'common.image',
        card_sort_name: 'goodsCate.zh_name',
        sort: 'classificationList.sort',
        card_draw_price: 'classificationList.drawPrice',
        draw_limit_per_user: 'classificationList.drawLimit',
        daily_draw_limit: 'classificationList.dailyDrawLimit',
        member_level_limit: 'classificationList.memberLevelLimit',
        card_sort_url_single: 'classificationList.urlSingle',
        card_sort_url_ten: 'classificationList.urlTen',
        card_sort_url_hundred: 'classificationList.urlHundred',
        card_brands: 'classificationList.brand',
        undrawn_count: 'classificationList.undrawnCount',
        drawn_count: 'classificationList.drawnCount',
        draw_enable: 'classificationList.drawEnable',
        is_once_only: 'classificationList.onceOnly',
        status: 'common.status'
      },
      echo: {},
      isLoadSub: false,
      // 品牌下拉可選項（所有已維護過的品牌 + 默認品牌）
      brandOptions: [DEFAULT_BRAND],
      // 會員等級下拉可選項（會員等級限制用）
      memberOptions: [],
      rules: {
        card_sort_name: [{ required: true, message: '此项为必填', trigger: 'blur' }]
      },
      showDetail: false,
      detailSortName: '',
      detailSortId: null,
      detailTab: 'unassociated',
      associatedCards: [],
      unassociatedCards: [],
      selectedAssoc: [],
      selectedUnassoc: [],
      assocSearch: '',
      unassocSearch: '',
      subLoading: false,
      detailLoading: false,
      unassocCounts: {},
      // 标签相关
      tags: ['预设'],
      activeTag: '预设',
      tagStats: {},
      tagAnimations: {},
      showAnimFile: false,
      animEditingTag: '',
      animSelectedFile: [],
      dragOverTag: null,
      draggedCard: null,
      draggingTag: null,
      // 抽卡价格公式（卡牌总价 ÷ 卡牌数量 × 百分比 = 抽卡价格，取整）
      priceStats: {
        count: 0,
        total_price: 0,
        percent: 100,
        auto_update_price: 1,
        card_draw_price: 0,
        computed_draw_price: 0,
        auto_update_time1: '05:00',
        auto_update_time2: '18:00'
      },
      pricePercent: 100,
      autoUpdatePrice: 1,
      savingPrice: false,
      savingAutoUpdate: false,
      // 額外獎勵（每 N 張發一張獎勵卡）
      bonusDialog: false,
      bonusSort: null,   // { card_sort_id, card_sort_name }
      bonusDrawnTotal: 0,
      bonusHasTable: true,
      bonusCards: [],    // 本池卡片下拉 { card_id, card_name, card_img, card_stock }
      bonusRules: [],    // 現有規則 [{ rule_id, trigger_at, card_id, is_active }]
      bonusLoading: false,
      bonusSaving: false
    }
  },
  computed: {
    // 把「所有已維護過的品牌」注入表單配置，作為品牌欄的下拉可選項
    // 把「會員等級」注入表單配置，作為會員等級限制（可多選）的可選項
    EFCConfig() {
      const options = this.brandOptions.map(brand => ({ label: brand, value: brand, trans: true }))
      const memberOpts = this.memberOptions.map(m => ({
        label: (this.$i18n.locale === 'en' ? (m.en_member_name || m.member_name) : (m.member_name || m.en_member_name)) || ('ID:' + m.member_id),
        value: Number(m.member_id)
      }))
      return {
        ...this.EFC,
        formItems: this.EFC.formItems.map(item => {
          if (item.attr.prop === 'card_brands') {
            return { ...item, item: { ...(item.item || {}), options } }
          }
          if (item.attr.prop === 'member_level_limits') {
            return { ...item, item: { ...(item.item || {}), options: memberOpts } }
          }
          return item
        })
      }
    },
    displayName() {
      return (card) => {
        const lang = this.$i18n.locale
        if (lang === 'en') return card.en_card_name || card.card_name
        return card.card_name || card.en_card_name
      }
    },
    // 根据当前 activeTag 过滤关联卡片
    currentTagCards: {
      get() {
        return this.associatedCards.filter(c => (c.card_tag || '预设') === this.activeTag)
      },
      set(val) {
        // 拖拽排序同步回 associatedCards（目前不做排序同步，仅保留 getter）
      }
    },
    associatedTabLabel() {
      return '已关联 (' + this.associatedCards.length + ')'
    },
    unassociatedTabLabel() {
      return '卡库 (' + this.unassociatedCards.length + ')'
    },
    emptyAssocDesc() {
      return '暂无已关联卡片（' + this.activeTag + '）'
    },
    editDialogTitle() {
      return this.echo.card_sort_id ? '编辑分类' : '新增分类'
    },
    // 公式即時預覽：卡牌總價 ÷ 卡牌數量 × 百分比（取整）
    previewDrawPrice() {
      const total = Number(this.priceStats.total_price) || 0
      const count = Number(this.priceStats.count) || 0
      const percent = Number(this.pricePercent) || 0
      return count > 0 ? Math.round((total / count) * percent / 100) : 0
    },
    // 已关联（当前标签）全选
    assocSelectAll: {
      get() {
        return this.currentTagCards.length > 0 && this.selectedAssoc.length === this.currentTagCards.length
      },
      set(val) {
        if (val) {
          this.selectedAssoc = this.currentTagCards.map(c => Number(c.card_id))
        } else {
          this.selectedAssoc = []
        }
      }
    },
    assocIndeterminate() {
      return this.selectedAssoc.length > 0 && this.selectedAssoc.length < this.currentTagCards.length
    },
    // 卡库全选
    unassocSelectAll: {
      get() {
        return this.unassociatedCards.length > 0 && this.selectedUnassoc.length === this.unassociatedCards.length
      },
      set(val) {
        if (val) {
          this.selectedUnassoc = this.unassociatedCards.map(c => Number(c.card_id))
        } else {
          this.selectedUnassoc = []
        }
      }
    },
    unassocIndeterminate() {
      return this.selectedUnassoc.length > 0 && this.selectedUnassoc.length < this.unassociatedCards.length
    }
  },
  created() {
    this.getList('Cate')
    this.loadBrandOptions()
    this.loadMemberOptions()
  },
  watch: {
    // 从文件管理选中标签中奖动画后保存
    animSelectedFile(val) {
      if (val && val.length) {
        this.saveTagAnimation(this.animEditingTag, val[0])
      }
    }
  },
  methods: {
    // 拉取所有已維護過的卡池品牌，作為品牌欄下拉選項
    async loadBrandOptions() {
      try {
        const { results } = await getAllCardBrands()
        const brands = (results && results.brands) || []
        this.brandOptions = Array.from(new Set([DEFAULT_BRAND, ...brands]))
      } catch (error) {
        console.log(error)
      }
    },
    // 拉取會員等級列表，作為「會員等級限制」下拉選項（按檔位區間升序）
    async loadMemberOptions() {
      try {
        const { results } = await reqLevel({ page: 1, limit: 100 })
        this.memberOptions = (results && results.data) || []
      } catch (error) {
        console.log(error)
      }
    },
    // 會員等級名稱（按當前語言），未設限制回傳空
    memberLevelName(memberId) {
      if (!Number(memberId)) return ''
      const lang = this.$i18n.locale
      const level = this.memberOptions.find(m => Number(m.member_id) === Number(memberId))
      if (!level) return `ID:${memberId}`
      return (lang === 'en' ? (level.en_member_name || level.member_name) : (level.member_name || level.en_member_name)) || `ID:${memberId}`
    },
    // 卡池的會員等級限制列表（多等級 + 每級張數），兼容舊單值字段
    levelLimitList(row) {
      let list = Array.isArray(row.member_level_limits) ? row.member_level_limits : []
      if (!list.length && Number(row.member_level_limit) > 0) {
        list = [{
          member_id: Number(row.member_level_limit),
          name: this.memberLevelName(row.member_level_limit),
          draw_limit: 0
        }]
      }
      return list.map(item => ({
        member_id: Number(item.member_id),
        name: item.name || this.memberLevelName(item.member_id),
        draw_limit: Number(item.draw_limit) || 0
      }))
    },
    // 卡池抽卡入口：为空视为开启（兼容未跑迁移时的旧数据）
    isDrawOn(row) {
      return row.draw_enable === null || row.draw_enable === undefined ? true : Number(row.draw_enable) === 1
    },
    // 金額展示：去掉多餘小數
    money(val) {
      const n = Number(val) || 0
      return n.toFixed(2).replace(/\.?0+$/, '')
    },
    // 回填抽卡價格公式統計
    applyPriceStats(stats) {
      const next = Object.assign({
        count: 0,
        total_price: 0,
        percent: 100,
        auto_update_price: 1,
        card_draw_price: 0,
        computed_draw_price: 0,
        auto_update_time1: '05:00',
        auto_update_time2: '18:00'
      }, stats || {})
      this.priceStats = next
      this.pricePercent = Number(next.percent)
      this.autoUpdatePrice = Number(next.auto_update_price) === 0 ? 0 : 1
    },
    // 保存手動百分比 / 自動更新開關，後端立即按公式重算並寫回實際抽卡價格
    async saveDrawPriceSetting() {
      if (!this.detailSortId) return
      this.savingPrice = true
      try {
        const res = await updateCardSortDrawPrice({
          card_sort_id: this.detailSortId,
          draw_price_percent: this.pricePercent,
          auto_update_price: this.autoUpdatePrice
        })
        this.applyPriceStats(res && res.results)
        this.$message({ message: '抽卡價格已更新', type: 'success' })
        // 同步刷新列表中的抽卡價格
        this.getList('Cate')
      } catch (error) {
        console.log(error)
      } finally {
        this.savingPrice = false
      }
    },
    // 切換「每日自動更新」開關時立即保存，無需點擊保存按鈕
    async toggleAutoUpdatePrice(val) {
      if (!this.detailSortId) return
      const target = val ? 1 : 0
      // 即時更新本地顯示
      this.autoUpdatePrice = target
      this.savingAutoUpdate = true
      try {
        const res = await updateCardSortDrawPrice({
          card_sort_id: this.detailSortId,
          auto_update_price: target
        })
        this.applyPriceStats(res && res.results)
        this.$message({ message: target === 1 ? '已開啟每日自動更新' : '已關閉每日自動更新', type: 'success' })
        this.getList('Cate')
      } catch (error) {
        // 失敗時回滾開關狀態
        this.autoUpdatePrice = target === 1 ? 0 : 1
        console.log(error)
      } finally {
        this.savingAutoUpdate = false
      }
    },
    // 单卡池开/关抽卡
    async toggleDrawEnable(row, val) {
      const target = val ? 1 : 0
      try {
        await editCardSortDrawEnable({ card_sort_id: row.card_sort_id, draw_enable: target })
        row.draw_enable = target
        this.$message.success(this.$t('common.success'))
      } catch (error) {
        this.getList('Cate')
      }
    },
    // 优惠卡包：开关（每账号仅能抽一次、仅单抽）
    isOnceOnlyOn(row) {
      return Number(row.is_once_only) === 1
    },
    async toggleOnceOnly(row, val) {
      const target = val ? 1 : 0
      try {
        await editCardSortOnceOnly({ card_sort_id: row.card_sort_id, is_once_only: target })
        row.is_once_only = target
        this.$message.success(this.$t('common.success'))
      } catch (error) {
        this.getList('Cate')
      }
    },
    // 一键开/关全部卡池
    batchDrawEnable(command) {
      const target = Number(command)
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true
        try {
          const res = await batchCardSortDrawEnable({ draw_enable: target })
          done()
          this.getList('Cate')
          // 開啟時僅處理已鎖定抽卡預設的卡池，其餘跳過並提示
          const skipped = res && res.results ? Number(res.results.skipped || 0) : 0
          if (target === 1 && skipped > 0) {
            this.$message.warning(this.$t('classificationList.drawBatchSkipped').replace('{n}', skipped))
          }
        } catch (error) {
        } finally {
          instance.confirmButtonLoading = false
        }
      })
    },
    onFormClose() {
      this.reset('editForm')
    },
    handleDetailClose() {
      this.detailLoading = false
      this.showDetail = false
    },
    onCancelDetail() {
      this.showDetail = false
    },
    async view(row) {
      this.detailSortName = row.card_sort_name || ''
      this.detailSortId = row.card_sort_id
      this.associatedCards = []
      this.unassociatedCards = []
      this.selectedAssoc = []
      this.selectedUnassoc = []
      this.unassocCounts = {}
      this.assocSearch = ''
      this.unassocSearch = ''
      this.detailTab = 'unassociated'
      this.tags = ['预设']
      this.activeTag = '预设'
      this.tagAnimations = {}
      this.draggingTag = null
      this.priceStats = {
        count: 0,
        total_price: 0,
        percent: 100,
        auto_update_price: 1,
        card_draw_price: row.card_draw_price || 0,
        computed_draw_price: 0,
        auto_update_time1: '05:00',
        auto_update_time2: '18:00'
      }
      this.pricePercent = 100
      this.autoUpdatePrice = 1
      this.detailLoading = true
      this.showDetail = true
      try {
        await this.loadCards()
      } catch (error) {
        console.log(error)
      } finally {
        this.detailLoading = false
      }
    },
    async loadCards() {
      try {
        const keyword = this.detailTab === 'associated' ? this.assocSearch : this.unassocSearch
        const res = await getCardsBySort({
          card_sort_id: this.detailSortId,
          keyword
        })
        this.associatedCards = res.results.associated || []
        this.unassociatedCards = res.results.unassociated || []
        this.tagStats = res.results.tag_stats || {}
        this.tagAnimations = res.results.tag_animations || {}
        // 抽卡價格公式統計（卡牌數量 / 正價總價 / 百分比 / 自動更新開關）
        this.applyPriceStats(res.results.sort_price)
        // 更新标签列表，并确保「预设」永远在第一个
        if (res.results.tags && res.results.tags.length) {
          this.tags = res.results.tags
        } else {
          this.tags = ['预设']
        }
        const defaultIdx = this.tags.indexOf('预设')
        if (defaultIdx > 0) {
          this.tags.splice(defaultIdx, 1)
          this.tags.unshift('预设')
        }
        if (!this.tags.includes(this.activeTag)) {
          this.activeTag = this.tags[0]
        }
        this.selectedAssoc = []
        this.selectedUnassoc = []
        // 同步保存标签顺序到后端
        this.saveTagsOrder()
      } catch (error) {
        console.log(error)
      }
    },
    // 同步保存标签顺序到后端
    async saveTagsOrder() {
      try {
        await saveCardTagOrder({
          card_sort_id: this.detailSortId,
          tags: this.tags
        })
      } catch (e) {
        console.log(e)
      }
    },
    // 新增标签
    async addTag() {
      const tagName = prompt('请输入新标签名称：')
      if (!tagName || !tagName.trim()) return
      const name = tagName.trim()
      if (this.tags.includes(name)) {
        this.$message({ message: '标签已存在', type: 'warning' })
        return
      }
      this.tags.push(name)
      this.activeTag = name
      this.saveTagsOrder()
      this.$message({ message: `标签「${name}」已建立`, type: 'success' })
    },
    // 删除标签
    async removeTag(tag) {
      if (tag === '预设') return
      const cardsInTag = this.associatedCards.filter(c => (c.card_tag || '预设') === tag)
      if (cardsInTag.length > 0) {
        const confirmMsg = `标签「${tag}」中有 ${cardsInTag.length} 张卡片，将全部移回「预设」标签，是否继续？`
        if (!confirm(confirmMsg)) return
        // 将该标签下的卡片全部移回预设（一次批量请求）
        this.detailLoading = true
        try {
          await updateCardTagBatch({
            card_ids: cardsInTag.map(c => c.card_id),
            card_tag: '预设'
          })
          cardsInTag.forEach(c => { c.card_tag = '预设' })
        } catch (e) {
          console.log(e)
        } finally {
          this.detailLoading = false
        }
      }
      this.tags = this.tags.filter(t => t !== tag)
      if (this.activeTag === tag) {
        this.activeTag = this.tags[0]
      }
      // 清理该标签的中奖动画配置（后端一并删除）
      if (this.tagAnimations[tag] !== undefined) {
        try {
          await saveCardTagAnimation({ card_sort_id: this.detailSortId, tag, animation_url: '' })
        } catch (e) {
          console.log(e)
        }
        delete this.tagAnimations[tag]
      }
      this.saveTagsOrder()
    },
    // 拖拽事件处理
    async onCardDragChange(evt) {
      const { added } = evt
      if (added && added.element) {
        const card = added.element
        const newTag = this.activeTag
        card.card_tag = newTag
        try {
          await updateCardTagBatch({ card_ids: [card.card_id], card_tag: newTag })
        } catch (e) {
          console.log(e)
        }
      }
    },
    // 点击标签：切换到该标签
    onTagClick(tag) {
      this.activeTag = tag
    },
    // 打开文件管理，为标签选择专属中奖动画
    setTagAnimation(tag) {
      this.animEditingTag = tag
      this.animSelectedFile = []
      this.showAnimFile = true
    },
    // 清除标签专属中奖动画（恢复使用默认动画）
    clearTagAnimation(tag) {
      this.$confirm(
        `确定清除标签「${tag}」的专属中奖动画吗？清除后抽中该标签将使用卡池默认动画。`,
        '清除中奖动画',
        { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' }
      ).then(async () => {
        this.detailLoading = true
        try {
          const res = await saveCardTagAnimation({
            card_sort_id: this.detailSortId,
            tag,
            animation_url: ''
          })
          if (res && res.results && res.results.tag_animations) {
            this.tagAnimations = res.results.tag_animations
          }
          this.$message({ message: '已恢复默认动画', type: 'success' })
        } catch (e) {
          console.log(e)
          this.$message({ message: '清除失败', type: 'error' })
        } finally {
          this.detailLoading = false
        }
      }).catch(() => {})
    },
    // 保存标签专属中奖动画
    async saveTagAnimation(tag, animationUrl) {
      this.detailLoading = true
      try {
        const res = await saveCardTagAnimation({
          card_sort_id: this.detailSortId,
          tag,
          animation_url: animationUrl
        })
        if (res && res.results && res.results.tag_animations) {
          this.tagAnimations = res.results.tag_animations
        }
        this.$message({ message: `标签「${tag}」中奖动画已设置`, type: 'success' })
      } catch (e) {
        console.log(e)
        this.$message({ message: '设置失败', type: 'error' })
      } finally {
        this.detailLoading = false
      }
    },
    // 标签拖动开始（记录正在拖动的标签，用于区分卡片拖拽）
    onTagDragStart(tag, event) {
      this.draggingTag = tag
      if (event && event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move'
        try {
          event.dataTransfer.setData('text/plain', tag)
        } catch (e) {
          console.log(e)
        }
      }
    },
    // 标签拖动结束
    onTagDragEnd() {
      this.draggingTag = null
      this.dragOverTag = null
    },
    // 标签重新排序：把 from 移动到 to 所在的位置（越靠前奖励越大）
    reorderTag(from, to) {
      const fromIdx = this.tags.indexOf(from)
      const toIdx = this.tags.indexOf(to)
      if (fromIdx === -1 || toIdx === -1 || fromIdx === toIdx) return
      this.tags.splice(fromIdx, 1)
      const insertIdx = this.tags.indexOf(to)
      this.tags.splice(insertIdx, 0, from)
      // 确保「预设」永远在第一个
      const defaultIdx = this.tags.indexOf('预设')
      if (defaultIdx > 0) {
        this.tags.splice(defaultIdx, 1)
        this.tags.unshift('预设')
      }
      this.saveTagsOrder()
      this.$message({ message: '标签顺序已更新（越靠前奖励越大）', type: 'success' })
    },
    // 滑鼠按下记录被拖拽的卡片（配合 vuedraggable 使用）
    onCardMouseDown(event, card) {
      this.draggedCard = card
    },
    // 拖拽到标签：移动卡片（含选中卡片）并切换页面
    async onDropToTag(tag) {
      this.dragOverTag = null
      // 标签拖动排序（奖励大到小）
      if (this.draggingTag) {
        const fromTag = this.draggingTag
        this.draggingTag = null
        if (fromTag === tag || fromTag === '预设' || tag === '预设') return
        this.reorderTag(fromTag, tag)
        return
      }
      const draggedCard = this.draggedCard
      this.draggedCard = null
      if (!draggedCard) return

      // 决定要移动的卡片：有选中则包含选中的 + 被拖拽的，否则只有被拖拽的
      let cardsToMove = [draggedCard]
      if (this.selectedAssoc.length > 0) {
        const selectedCards = this.associatedCards.filter(
          c => this.selectedAssoc.includes(c.card_id) && c.card_id !== draggedCard.card_id
        )
        cardsToMove = [...selectedCards, draggedCard]
      }

      // 过滤掉已在目标标签的卡片
      cardsToMove = cardsToMove.filter(c => (c.card_tag || '预设') !== tag)
      if (!cardsToMove.length) return

      this.detailLoading = true
      try {
        // 一次批量请求更新所有卡片标签（成功后才更新本地状态，失败保持原标签）
        const ids = cardsToMove.map(c => c.card_id)
        try {
          await updateCardTagBatch({ card_ids: ids, card_tag: tag })
          cardsToMove.forEach(c => { c.card_tag = tag })
        } catch (e) {
          console.log(e)
        }
        this.selectedAssoc = []
        this.activeTag = tag
      } finally {
        this.detailLoading = false
      }
    },
    toggleAssocSelect(id) {
      const numId = Number(id)
      const idx = this.selectedAssoc.indexOf(numId)
      if (idx > -1) {
        this.selectedAssoc.splice(idx, 1)
      } else {
        this.selectedAssoc.push(numId)
      }
      this.selectedAssoc = this.selectedAssoc.slice()
    },
    toggleUnassocSelect(id) {
      const numId = Number(id)
      const idx = this.selectedUnassoc.indexOf(numId)
      if (idx > -1) {
        this.selectedUnassoc.splice(idx, 1)
      } else {
        this.selectedUnassoc.push(numId)
        if (!this.unassocCounts[numId]) {
          this.$set(this.unassocCounts, numId, 1)
        }
      }
      this.selectedUnassoc = this.selectedUnassoc.slice()
    },
    onUnassocCountChange(cardId, val) {
      if (!val || val < 1) {
        this.$set(this.unassocCounts, cardId, 1)
      }
    },
    async batchAssociate() {
      if (!this.selectedUnassoc.length) return
      this.subLoading = true
      try {
        const items = this.selectedUnassoc.map(id => ({
          card_id: id,
          count: this.unassocCounts[id] || 1
        }))
        await associateCards({
          card_sort_id: this.detailSortId,
          items,
          action: 'associate'
        })
        this.$message({ message: '操作成功', type: 'success' })
        await this.loadCards()
      } catch (error) {
        console.log(error)
      } finally {
        this.subLoading = false
      }
    },
    // 批量设定展示/隐藏
    async batchSetPriority(val) {
      if (!this.selectedAssoc.length) return
      const label = val === 1 ? '开启展示' : '关闭展示'
      this.subLoading = true
      try {
        await associateCards({
          card_ids: this.selectedAssoc,
          is_priority: val,
          action: 'batch_priority'
        })
        this.$message({ message: `已${label} ${this.selectedAssoc.length} 张卡牌`, type: 'success' })
        this.selectedAssoc = []
        await this.loadCards()
      } catch (e) {
        console.error(e)
        this.$message({ message: '操作失败：' + (e.message || e), type: 'error' })
      } finally {
        this.subLoading = false
      }
    },
    async batchUnassociate() {
      if (!this.selectedAssoc.length) return
      this.subLoading = true
      try {
        await associateCards({
          card_ids: this.selectedAssoc,
          action: 'unassociate'
        })
        this.$message({ message: '操作成功', type: 'success' })
        await this.loadCards()
      } catch (error) {
        console.log(error)
      } finally {
        this.subLoading = false
      }
    },
    edit({ status, created_at, updated_at, ...data }) {
      // 多等級限制：兼容舊數據（member_level_limit 單值）
      let levelLimits = Array.isArray(data.member_level_limits) ? data.member_level_limits : []
      if (!levelLimits.length && Number(data.member_level_limit) > 0) {
        levelLimits = [{ member_id: Number(data.member_level_limit), draw_limit: 0 }]
      }
      this.echo = {
        ...data,
        status: Number(status),
        member_level_limit: Number(data.member_level_limit) || 0,
        member_level_limits: levelLimits.map(item => ({
          member_id: Number(item.member_id),
          draw_limit: Number(item.draw_limit) || 0
        }))
      }
      this.show = true
    },
    async onPriorityChange(card, val) {
      try {
        await updateCardPriority({ card_id: card.card_id, is_priority: val })
        this.$message({ message: '更新成功', type: 'success' })
      } catch (error) {
        card.is_priority = val === 1 ? 0 : 1
      }
    },
    async del(row) {
      // 支援行级删除（传入 row）和批量删除（使用 delIds）
      let ids = []
      if (row && row.card_sort_id) {
        ids = [row.card_sort_id]
      } else {
        ids = this.delIds
      }
      if (!ids.length) return
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true
        try {
          for (const id of ids) {
            await delCardCate({ card_sort_id: id })
          }
          this.delIds = []
          this.getList('Cate')
          done()
          this.$message({ message: '操作成功', type: 'success' })
        } catch (error) {
        } finally {
          instance.confirmButtonLoading = false
        }
      })
    },
    onRcc({ scope: { row }, type }) {
      this[type](row)
    },
    // ============ 額外獎勵 methods ============
    async openBonus(row) {
      this.bonusSort = row
      this.bonusDialog = true
      this.bonusLoading = true
      this.bonusRules = []
      this.bonusCards = []
      try {
        const [ruleRes, cardRes] = await Promise.all([
          getCardSortBonusRules({ card_sort_id: row.card_sort_id }),
          getCardsBySort({ card_sort_id: row.card_sort_id, page: 1, limit: 2000 }),
        ])
        this.bonusDrawnTotal = Number(ruleRes.results.drawn_total || 0)
        this.bonusHasTable = !!ruleRes.results.has_table
        this.bonusRules = (ruleRes.results.rules || []).map(r => ({ ...r }))
        // 本池可用卡（可選為獎勵卡）
        this.bonusCards = ((cardRes.results && cardRes.results.data) || []).map(c => ({
          card_id: c.card_id,
          card_name: c.card_name || '',
          card_img: c.card_img || '',
          card_stock: Number(c.card_stock || 0),
          card_status: Number(c.card_status || 1),
        })).filter(c => c.card_status === 1)
      } catch (e) {
        this.$message.error('讀取獎勵規則失敗')
      } finally {
        this.bonusLoading = false
      }
    },
    addBonusRule() {
      this.bonusRules.push({
        rule_id: 0,
        trigger_at: 10,
        card_id: null,
        is_active: 1,
        sort: this.bonusRules.length + 1,
        issued_count: 0,
      })
    },
    removeBonusRule(idx) {
      this.bonusRules.splice(idx, 1)
    },
    async saveBonus() {
      // 前端基礎校驗
      const triggers = []
      for (let i = 0; i < this.bonusRules.length; i++) {
        const r = this.bonusRules[i]
        if (!r.trigger_at || r.trigger_at < 1) { this.$message.error(`第 ${i + 1} 條規則的「每 N 張」必須為正整數`); return }
        if (!r.card_id) { this.$message.error(`第 ${i + 1} 條規則未選擇獎勵卡牌`); return }
        if (triggers.indexOf(r.trigger_at) > -1) { this.$message.error(`「每 N 張」不能重複：N=${r.trigger_at}`); return }
        triggers.push(r.trigger_at)
      }
      this.bonusSaving = true
      try {
        const res = await saveCardSortBonusRules({
          card_sort_id: this.bonusSort.card_sort_id,
          rules: this.bonusRules.map(r => ({
            trigger_at: Number(r.trigger_at),
            card_id: Number(r.card_id),
            is_active: r.is_active,
            sort: Number(r.sort),
          })),
        })
        this.$message.success(`已保存 ${res.results.saved} 條規則`)
        // 保存成功後刷新一次規則（拿回 issued_count 等最新數據）
        this.openBonus(this.bonusSort)
      } catch (e) {
      } finally {
        this.bonusSaving = false
      }
    },
    // 卡池初始化：複製設置/預設相同的新池，原池軟刪除
    initPool(row) {
      this.$confirm(
        `確定初始化卡池「${row.card_sort_name}」嗎？將複製一個設置與抽卡預設完全相同的新卡池（未被抽取過），原卡池將隱藏且不影響歷史數據。`,
        '初始化卡池',
        { confirmButtonText: '確定', cancelButtonText: '取消', type: 'warning' }
      ).then(async () => {
        try {
          this.isLoading = true
          const res = await initCardSort({ card_sort_id: row.card_sort_id })
          this.$message.success(`初始化成功，新卡池 ID：${res.results.card_sort_id}，複製卡牌 ${res.results.copied_cards} 張`)
          this.getList('Cate')
        } catch (error) {
        } finally {
          this.isLoading = false
        }
      }).catch(() => {})
    },
    submit() {
      this.$refs.editForm.submit(async (data) => {
        try {
          this.isLoadSub = true
          const params = this.echo.card_sort_id
            ? { ...data, card_sort_id: this.echo.card_sort_id }
            : data
          await addCardCate(params)
          this.$message({ message: '操作成功', type: 'success' })
          this.show = false
          this.getList('Cate')
          // 品牌可能有新增，刷新下拉選項
          this.loadBrandOptions()
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

<style scoped>
.price-formula-bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px 18px;
  padding: 12px 16px;
  margin-bottom: 12px;
  background: #f4f8ff;
  border: 1px solid #d9e8ff;
  border-radius: 6px;
  font-size: 13px;
  color: #303133;
}
.pf-group {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}
.pf-label {
  color: #606266;
}
.pf-percent-input {
  width: 120px;
}
.pf-pct {
  font-weight: 600;
}
.pf-formula {
  font-size: 14px;
  color: #303133;
  white-space: nowrap;
}
.pf-result {
  color: #f56c6c;
  font-size: 17px;
  margin: 0 2px;
}
.pf-current {
  color: #409eff;
  font-size: 15px;
}
.pf-hint {
  color: #909399;
  font-size: 12px;
}
.pf-switch-group {
  gap: 8px;
}
.tab-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid #ebeef5;
  margin-bottom: 12px;
}
.tag-stats {
  font-size: 12px;
  color: #909399;
  flex: 1;
  text-align: center;
}
.card-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  padding: 8px 0;
  max-height: 650px;
  overflow-y: auto;
}
.card-item {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 148px;
  padding: 14px 12px 10px;
  border: 2px solid #ebeef5;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
}
.card-item:hover {
  box-shadow: 0 2px 12px rgba(0,0,0,0.1);
}
.card-item-drawn {
  opacity: 0.5;
  background: #f5f5f5;
  border-color: #e4e7ed;
}
.card-item-drawn .card-name {
  text-decoration: line-through;
  color: #c0c4cc;
}
.card-item-selected {
  border-color: #409eff;
  background: #ecf5ff;
}
.card-item-drawn.card-item-selected {
  border-color: #409eff;
  background: #ecf5ff;
}
.card-item-associated {
  border-color: #e6a23c;
  background: #fdf6ec;
}
.card-item-selected.card-item-associated {
  border-color: #409eff;
  background: #ecf5ff;
}
.card-associated-tag {
  top: 0;
  left: 0;
  bottom: auto;
  right: auto;
  font-size: 11px;
  padding: 0 6px;
  height: 22px;
  line-height: 22px;
  pointer-events: none;
  z-index: 1;
}
.card-img-wrap {
  position: relative;
  width: 96px;
  height: 96px;
  border-radius: 8px;
  overflow: hidden;
}
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
.card-status-corner {
  position: absolute;
  bottom: 4px;
  right: 4px;
  z-index: 2;
  pointer-events: none;
}
.card-status-corner .el-tag {
  margin: 0;
  font-size: 10px;
  height: 18px;
  line-height: 18px;
  padding: 0 5px;
}
.card-status-tag {
  position: absolute;
  bottom: 0;
  right: 0;
  font-size: 12px;
  padding: 2px 6px;
  height: 22px;
  line-height: 18px;
  pointer-events: none;
  z-index: 1;
}
.img-drawn {
  filter: grayscale(1);
}
.card-name {
  margin-top: 6px;
  font-size: 13px;
  color: #303133;
  text-align: center;
  width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.card-checkbox {
  margin-top: 4px;
  width: 16px;
  height: 16px;
  cursor: pointer;
}
.card-checkbox-el {
  margin-top: 4px;
}
.card-item .el-input-number {
  width: 100% !important;
}
.card-item .el-input-number .el-input {
  width: 100%;
}
.card-item .el-input-number .el-input__inner {
  padding-right: 30px;
}
.card-priority-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  margin-top: 2px;
}
.priority-label {
  font-size: 11px;
  color: #909399;
  white-space: nowrap;
}
.card-price {
  margin-top: 4px;
  font-size: 13px;
  color: #e6a23c;
  font-weight: 600;
  text-align: center;
  width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.card-img-error {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 110px;
  height: 110px;
  background: #f5f5f5;
  color: #c0c4cc;
  font-size: 28px;
  border-radius: 8px;
}
.tag-tabs {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
  padding-bottom: 12px;
  margin-bottom: 12px;
  border-bottom: 1px solid #ebeef5;
}
.tag-tab-item {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 12px;
  font-size: 13px;
  border: 1px solid #dcdfe6;
  border-radius: 16px;
  cursor: pointer;
  transition: all 0.2s;
  color: #606266;
  background: #fff;
  white-space: nowrap;
}
.tag-tab-item:hover {
  background: #ecf5ff;
  border-color: #c6e2ff;
  color: #409eff;
}
.tag-tab-active {
  background: #409eff;
  border-color: #409eff;
  color: #fff;
}
.tag-tab-active:hover {
  background: #66b1ff;
  border-color: #66b1ff;
  color: #fff;
}
.tag-tab-dragover {
  background: #e6a23c !important;
  border-color: #e6a23c !important;
  color: #fff !important;
  transform: scale(1.05);
  box-shadow: 0 2px 8px rgba(230, 162, 60, 0.4);
}
.tag-tab-close {
  font-size: 12px;
  cursor: pointer;
  opacity: 0.6;
}
.tag-tab-close:hover {
  opacity: 1;
}
.tag-tab-anim {
  font-size: 13px;
  cursor: pointer;
  opacity: 0.55;
  color: #909399;
}
.tag-tab-anim:hover {
  opacity: 1;
  color: #409eff;
}
.tag-anim-set {
  color: #67c23a;
  opacity: 1;
}
.tag-anim-check {
  font-size: 13px;
  color: #67c23a;
  opacity: 1;
}
.tag-anim-clear {
  font-size: 13px;
  cursor: pointer;
  opacity: 0.55;
  color: #f56c6c;
}
.tag-anim-clear:hover {
  opacity: 1;
}
.tag-tab-drag {
  font-size: 13px;
  cursor: move;
  opacity: 0.55;
  color: #909399;
}
.tag-tab-drag:hover {
  opacity: 1;
  color: #409eff;
}
.tag-add-btn {
  margin-left: 2px;
  font-size: 13px;
}
.tag-card-count {
  font-size: 13px;
  color: #909399;
}
</style>
