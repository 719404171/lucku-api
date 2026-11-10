<template>
  <div class="app-container">
    <div v-if="device==='desktop'" class="mb20">
      <el-button v-if="nodePower.includes('add')" type="success" icon="el-icon-plus" @click="show=true">{{ $t('common.add') }}</el-button>
      <el-button v-if="nodePower.includes('del')" type="danger" icon="el-icon-delete" :disabled="!!!delIds.length" @click="del">{{ $t('common.deletes') }}</el-button>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('Exchange')" />
    </div>
    <div v-else class="mb20">
      <el-button v-if="nodePower.includes('add')" type="success" icon="el-icon-plus" @click="show=true" />
      <el-button v-if="nodePower.includes('del')" type="danger" icon="el-icon-delete" :disabled="!!!delIds.length" @click="del" />
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('Exchange')" />
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
      @page-change="onPageChange('Exchange', $event)"
      @selection-change="delIds=$event.map(({exchange_id})=>exchange_id)"
    >
      <template #amount="{row}"><span class="exchange-amount">HK${{ row.amount != null ? row.amount : 0 }}</span></template>
      <template #icon="{row}">
        <ProImage v-if="row.icon" :src="row.icon" style="width:40px;height:40px;border-radius:6px;display:block;" />
        <span v-else style="color:#c0c4cc;">--</span>
      </template>
      <template #exchange_coin="{row}"><span style="font-weight:600;">{{ row.exchange_coin != null ? row.exchange_coin : 0 }}</span></template>
      <template #bonus_coin="{row}"><span style="color:#e6a23c;font-weight:600;">+{{ row.bonus_coin != null ? row.bonus_coin : 0 }}</span></template>
      <template #total_coin="{row}"><span style="color:#409eff;font-weight:700;">{{ ((row.exchange_coin != null ? row.exchange_coin : 0) + (row.bonus_coin != null ? row.bonus_coin : 0)) }}</span></template>
      <template #status="{row}"><el-tag :type="row.status==1 ? 'success' : 'danger'">{{ row.status==1 ? $t('common.normal') : $t('common.disabled') }}</el-tag></template>
    </ProTable>

    <el-dialog :title="$t(`exchangeSetting.${echo.exchange_id ? 'edit' : 'add'}`)" :visible.sync="show" :width="device==='desktop' ? '600px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <el-form ref="editForm" :model="formData" label-width="130px" :rules="rules">
        <el-form-item :label="$t('exchangeSetting.amount')" prop="amount">
          <el-input-number v-model="formData.amount" :min="0.01" :precision="2" style="width:100%" />
        </el-form-item>
        <el-form-item :label="$t('exchangeSetting.icon')" prop="icon">
          <Upload ref="uploadRef" v-model="formData.icon" :config="{}" @upload-start="uploading = true" @upload-end="uploading = false" />
          <div style="color:#909399;font-size:12px;margin-top:4px;">建议尺寸 120x120px</div>
        </el-form-item>
        <el-form-item :label="$t('exchangeSetting.exchangeCoin')" prop="exchange_coin">
          <el-input-number v-model="formData.exchange_coin" :min="1" :step="10" style="width:100%" />
          <div style="color:#909399;font-size:12px;margin-top:4px;">充值后基础可获得的金币数量</div>
        </el-form-item>
        <el-form-item :label="$t('exchangeSetting.bonusCoin')" prop="bonus_coin">
          <el-input-number v-model="formData.bonus_coin" :min="0" :step="10" style="width:100%" />
          <div style="color:#909399;font-size:12px;margin-top:4px;">额外赠送的优惠金币</div>
        </el-form-item>
        <el-form-item v-if="formData.exchange_coin > 0" :label="$t('exchangeSetting.totalCoin')" prop="total_coin">
          <div style="background:#f0f9eb;padding:12px 16px;border-radius:6px;display:flex;align-items:center;justify-content:space-between;">
            <div style="text-align:center;">
              <div style="font-size:12px;color:#909399;">{{ $t('exchangeSetting.exchangeCoin') }}</div>
              <div style="font-size:20px;font-weight:700;color:#67c23a;margin-top:4px;">{{ formData.exchange_coin }}</div>
            </div>
            <div style="font-size:20px;color:#c0c4cc;">+</div>
            <div style="text-align:center;">
              <div style="font-size:12px;color:#909399;">{{ $t('exchangeSetting.bonusCoin') }}</div>
              <div style="font-size:20px;font-weight:700;color:#e6a23c;margin-top:4px;">{{ formData.bonus_coin }}</div>
            </div>
            <div style="font-size:20px;color:#c0c4cc;">=</div>
            <div style="text-align:center;">
              <div style="font-size:12px;color:#909399;">{{ $t('exchangeSetting.totalCoin') }}</div>
              <div style="font-size:24px;font-weight:700;color:#409eff;margin-top:4px;">{{ totalCoin }}</div>
            </div>
          </div>
        </el-form-item>
        <el-form-item :label="$t('exchangeSetting.description')" prop="description">
          <el-input v-model="formData.description" type="textarea" :rows="2" :placeholder="$t('exchangeSetting.descPlaceholder')" />
        </el-form-item>
        <el-form-item :label="$t('common.status')" prop="status">
          <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{ $t('common.cancel') }}</el-button>
        <el-button type="primary" :loading="isLoadSub" :disabled="uploading" @click="submit">{{ $t('common.confirm') }}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { getExchangeList, handleExchange, delExchange } from '@/api'
import Upload from '@/components/Upload'
import paging from '@/mixins/paging'
export default {
  name: 'ExchangeSetting',
  mixins: [paging],
  components: { Upload },
  data() {
    return {
      SFC: {},
      EFC: {},
      show: false,
      delIds: [],
      column: {
        amount: 'exchangeSetting.amount',
        icon: 'exchangeSetting.icon',
        exchange_coin: 'exchangeSetting.exchangeCoin',
        bonus_coin: 'exchangeSetting.bonusCoin',
        total_coin: 'exchangeSetting.totalCoin',
        description: 'exchangeSetting.description',
        status: 'common.status'
      },
      echo: {},
      formData: {
        amount: 0,
        icon: '',
        exchange_coin: 0,
        bonus_coin: 0,
        description: '',
        status: 1
      },
      isLoadSub: false,
      uploading: false,
      rules: {
        amount: [{ required: true, message: this.$t('common.required'), trigger: 'blur' }],
        exchange_coin: [{ required: true, message: this.$t('common.required'), trigger: 'blur' }]
      }
    }
  },
  computed: {
    totalCoin() {
      return (Number(this.formData.exchange_coin) || 0) + (Number(this.formData.bonus_coin) || 0)
    }
  },
  created() {
    this.getList('Exchange')
  },
  methods: {
    onFormClose() {
      this.$refs.editForm.resetFields()
      this.$nextTick(() => {
        this.formData = { amount: 0, icon: '', exchange_coin: 0, bonus_coin: 0, description: '', status: 1 }
        this.echo = {}
      })
    },
    async submit() {
      if (this.uploading) {
        this.$message.warning(this.$t('exchangeSetting.uploadingTip'))
        return
      }
      this.isLoadSub = true
      try {
        await this.$refs.editForm.validate()
        const data = { ...this.formData }
        if (this.echo.exchange_id) data.exchange_id = this.echo.exchange_id
        await handleExchange(data)
        this.show = false
        this.getList('Exchange')
      } catch (error) {
        console.log(error)
      } finally {
        this.isLoadSub = false
      }
    },
    async del() {
      this.$confirm(this.$t('common.delConfirm'), this.$t('common.tips'), { confirmButtonText: this.$t('common.confirm'), cancelButtonText: this.$t('common.cancel'), type: 'warning' }).then(async() => {
        for (const id of this.delIds) {
          await delExchange({ exchange_id: id })
        }
        this.getList('Exchange')
        this.delIds = []
      }).catch(() => {})
    },
    onRcc({ scope: { row }, type }) {
      if (type === 'edit') {
        this.echo = { ...row }
        this.formData = {
          amount: Number(row.amount) || 0,
          icon: row.icon || '',
          exchange_coin: Number(row.exchange_coin) || 0,
          bonus_coin: Number(row.bonus_coin) || 0,
          description: row.description || '',
          status: row.status
        }
        this.show = true
      }
    }
  }
}
</script>
