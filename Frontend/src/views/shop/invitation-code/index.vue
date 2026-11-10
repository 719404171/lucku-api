<template>
  <div class="app-container">
    <div v-if="device==='desktop'" class="mb20">
      <el-button v-if="nodePower.includes('add')" type="success" icon="el-icon-plus" @click="showAdd = true">{{ $t('invitationCode.add') }}</el-button>
      <el-button v-if="nodePower.includes('add')" type="warning" icon="el-icon-document-copy" @click="showBatch = true">{{ $t('invitationCode.batchGenerate') }}</el-button>
      <el-button v-if="nodePower.includes('del')" type="danger" icon="el-icon-delete" :disabled="!!!delIds.length" @click="del">{{ $t('common.deletes') }}</el-button>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('InvitationCode')" />
    </div>
    <div v-else class="mb20">
      <el-button v-if="nodePower.includes('add')" type="success" icon="el-icon-plus" @click="showAdd = true" />
      <el-button v-if="nodePower.includes('add')" type="warning" icon="el-icon-document-copy" @click="showBatch = true" />
      <el-button v-if="nodePower.includes('del')" type="danger" icon="el-icon-delete" :disabled="!!!delIds.length" @click="del" />
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('InvitationCode')" />
    </div>

    <ProTable
      index
      selection
      border
      :is-loading="isLoading"
      :data="list"
      :page="page"
      :column="column"
      :row-control="['edit', 'del']"
      @row-control-click="onRcc"
      @page-change="onPageChange('InvitationCode', $event)"
      @selection-change="delIds=$event.map(({invitation_id})=>invitation_id)"
    >
      <template #code="{row}">
        <span style="font-family:monospace;font-weight:700;font-size:14px;letter-spacing:1px;">{{ row.code }}</span>
      </template>
      <template #reward_coin="{row}">
        <span style="color:#e6a23c;font-weight:600;">+{{ row.reward_coin || 0 }}</span>
      </template>
      <template #reward_diamond="{row}">
        <span style="color:#ff69b4;font-weight:600;">+{{ row.reward_diamond || 0 }}</span>
      </template>
      <template #use_count="{row}">
        <span>{{ row.use_count || 0 }} / {{ row.max_uses == 0 ? '∞' : row.max_uses }}</span>
      </template>
      <template #status="{row}">
        <el-tag :type="row.status==1 ? 'success' : 'danger'">{{ row.status==1 ? $t('common.normal') : $t('common.disabled') }}</el-tag>
      </template>
    </ProTable>

    <!-- 新增邀请码 -->
    <el-dialog :title="$t('invitationCode.add')" :visible.sync="showAdd" width="500px" :close-on-click-modal="false" @closed="resetAddForm">
      <el-form ref="addForm" :model="addForm" label-width="110px">
        <el-form-item :label="$t('invitationCode.code')" prop="code">
          <el-input v-model="addForm.code" :placeholder="$t('invitationCode.codePlaceholder')" clearable>
            <el-button slot="append" @click="autoGenerateCode">{{ $t('invitationCode.randomGenerate') }}</el-button>
          </el-input>
        </el-form-item>
        <el-form-item :label="$t('invitationCode.maxUses')" prop="max_uses">
          <el-input-number v-model="addForm.max_uses" :min="0" style="width:100%" />
          <div style="color:#909399;font-size:12px;margin-top:4px;">0 = 不限次数</div>
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item :label="$t('invitationCode.rewardCoin')" prop="reward_coin">
              <el-input-number v-model="addForm.reward_coin" :min="0" style="width:100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="$t('invitationCode.rewardDiamond')" prop="reward_diamond">
              <el-input-number v-model="addForm.reward_diamond" :min="0" style="width:100%" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <span slot="footer">
        <el-button type="primary" :loading="isAddLoading" @click="submitAdd">{{ $t('common.confirm') }}</el-button>
        <el-button @click="showAdd = false">{{ $t('common.cancel') }}</el-button>
      </span>
    </el-dialog>

    <!-- 批量生成 -->
    <el-dialog :title="$t('invitationCode.batchGenerate')" :visible.sync="showBatch" width="500px" :close-on-click-modal="false" @closed="resetBatchForm">
      <el-form ref="batchForm" :model="batchForm" label-width="110px">
        <el-form-item :label="$t('invitationCode.generateCount')" prop="count">
          <el-input-number v-model="batchForm.count" :min="1" :max="500" style="width:100%" />
          <div style="color:#909399;font-size:12px;margin-top:4px;">最多一次性生成 500 个</div>
        </el-form-item>
        <el-form-item :label="$t('invitationCode.maxUses')" prop="max_uses">
          <el-input-number v-model="batchForm.max_uses" :min="0" style="width:100%" />
          <div style="color:#909399;font-size:12px;margin-top:4px;">0 = 不限次数</div>
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item :label="$t('invitationCode.rewardCoin')" prop="reward_coin">
              <el-input-number v-model="batchForm.reward_coin" :min="0" style="width:100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="$t('invitationCode.rewardDiamond')" prop="reward_diamond">
              <el-input-number v-model="batchForm.reward_diamond" :min="0" style="width:100%" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
      <span slot="footer">
        <el-button type="primary" :loading="isBatchLoading" @click="submitBatch">{{ $t('common.confirm') }}</el-button>
        <el-button @click="showBatch = false">{{ $t('common.cancel') }}</el-button>
      </span>
    </el-dialog>

    <!-- 编辑邀请码 -->
    <el-dialog :title="$t('invitationCode.edit')" :visible.sync="showEdit" width="500px" :close-on-click-modal="false" @closed="resetEditForm">
      <el-form ref="editForm" :model="editForm" label-width="110px">
        <el-form-item :label="$t('invitationCode.code')">
          <el-tag type="info" style="font-family:monospace;font-size:14px;letter-spacing:1px;">{{ editForm.code }}</el-tag>
        </el-form-item>
        <el-form-item :label="$t('invitationCode.maxUses')" prop="max_uses">
          <el-input-number v-model="editForm.max_uses" :min="0" style="width:100%" />
          <div style="color:#909399;font-size:12px;margin-top:4px;">0 = 不限次数｜已使用 {{ editForm.use_count }} 次</div>
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item :label="$t('invitationCode.rewardCoin')" prop="reward_coin">
              <el-input-number v-model="editForm.reward_coin" :min="0" style="width:100%" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item :label="$t('invitationCode.rewardDiamond')" prop="reward_diamond">
              <el-input-number v-model="editForm.reward_diamond" :min="0" style="width:100%" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item :label="$t('common.status')" prop="status">
          <el-radio-group v-model="editForm.status">
            <el-radio :label="1">{{ $t('common.normal') }}</el-radio>
            <el-radio :label="0">{{ $t('common.disabled') }}</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <span slot="footer">
        <el-button type="primary" :loading="isEditLoading" @click="submitEdit">{{ $t('common.confirm') }}</el-button>
        <el-button @click="showEdit = false">{{ $t('common.cancel') }}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { getInvitationCodeList, addInvitationCode, editInvitationCode, batchGenerateInvitationCode, deleteInvitationCode } from '@/api'
import paging from '@/mixins/paging'
export default {
  name: 'InvitationCode',
  mixins: [paging],
  data() {
    return {
      SFC: {},
      EFC: {},
      showAdd: false,
      showBatch: false,
      showEdit: false,
      isAddLoading: false,
      isBatchLoading: false,
      isEditLoading: false,
      addForm: {
        code: '',
        max_uses: 1,
        reward_coin: 0,
        reward_diamond: 0
      },
      editForm: {
        invitation_id: 0,
        code: '',
        use_count: 0,
        max_uses: 1,
        reward_coin: 0,
        reward_diamond: 0,
        status: 1
      },
      batchForm: {
        count: 10,
        max_uses: 1,
        reward_coin: 0,
        reward_diamond: 0
      },
      column: {
        code: 'invitationCode.code',
        reward_coin: 'invitationCode.rewardCoin',
        reward_diamond: 'invitationCode.rewardDiamond',
        use_count: 'invitationCode.usedCount',
        status: 'common.status',
        created_at: 'common.createdAt'
      },
      delIds: []
    }
  },
  created() {
    this.getList('InvitationCode')
  },
  methods: {
    // 生成随机邀请码
    autoGenerateCode() {
      const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
      let code = ''
      for (let i = 0; i < 8; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length))
      }
      this.addForm.code = code
    },
    // 重置新增表单
    resetAddForm() {
      this.addForm = { code: '', max_uses: 1, reward_coin: 0, reward_diamond: 0 }
      this.$refs.addForm && this.$refs.addForm.clearValidate()
    },
    // 重置编辑表单
    resetEditForm() {
      this.editForm = { invitation_id: 0, code: '', use_count: 0, max_uses: 1, reward_coin: 0, reward_diamond: 0, status: 1 }
      this.$refs.editForm && this.$refs.editForm.clearValidate()
    },
    // 重置批量表单
    resetBatchForm() {
      this.batchForm = { count: 10, max_uses: 1, reward_coin: 0, reward_diamond: 0 }
      this.$refs.batchForm && this.$refs.batchForm.clearValidate()
    },
    // 新增邀请码
    async submitAdd() {
      this.isAddLoading = true
      try {
        await addInvitationCode(this.addForm)
        this.$message({ message: this.$t('common.success'), type: 'success' })
        this.showAdd = false
        this.getList('InvitationCode')
      } catch (e) {
        // 错误由拦截器处理
      } finally {
        this.isAddLoading = false
      }
    },
    // 批量生成
    async submitBatch() {
      this.isBatchLoading = true
      try {
        await batchGenerateInvitationCode(this.batchForm)
        this.$message({ message: this.$t('common.success'), type: 'success' })
        this.showBatch = false
        this.getList('InvitationCode')
      } catch (e) {
        // 错误由拦截器处理
      } finally {
        this.isBatchLoading = false
      }
    },
    // 删除
    async del() {
      if (!this.delIds.length) return
      try {
        await this.$confirm(this.$t('common.delConfirm'), this.$t('common.warning'), { confirmButtonText: this.$t('common.confirm'), cancelButtonText: this.$t('common.cancel'), type: 'warning' })
        for (const id of this.delIds) {
          await deleteInvitationCode({ invitation_id: id })
        }
        this.$message({ message: this.$t('common.success'), type: 'success' })
        this.delIds = []
        this.getList('InvitationCode')
      } catch (e) {
        // 取消或错误
      }
    },
    // 编辑
    edit(row) {
      this.editForm = {
        invitation_id: row.invitation_id,
        code: row.code,
        use_count: row.use_count,
        max_uses: row.max_uses,
        reward_coin: row.reward_coin,
        reward_diamond: row.reward_diamond,
        status: row.status
      }
      this.showEdit = true
    },
    // 提交编辑
    async submitEdit() {
      this.isEditLoading = true
      try {
        await editInvitationCode(this.editForm)
        this.$message({ message: this.$t('common.success'), type: 'success' })
        this.showEdit = false
        this.getList('InvitationCode')
      } catch (e) {
        // 错误由拦截器处理
      } finally {
        this.isEditLoading = false
      }
    },
    // 行控制
    onRcc({scope: {row}, type}) {
      if (type === 'edit') {
        this.edit(row)
      } else if (type === 'del') {
        this.delIds = [row.invitation_id]
        this.del()
      }
    }
  }
}
</script>
