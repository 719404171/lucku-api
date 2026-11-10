<template>
  <div class="app-container">
    <el-card v-loading="isLoading" class="box-card">
      <div slot="header" class="clearfix">
        <span>{{ $t('store.title') }}</span>
        <el-button v-if="nodePower.includes('edit')" style="float: right;" type="primary" :loading="isLoadSub" @click="submit">{{ $t('common.save') }}</el-button>
      </div>
      <h4>{{ $t('goods.base') }}</h4>
      <ProForm ref="baseForm" :echo="baseEcho" :config="EFC" :rules="baseRules" :msg="$t('goods.noBase')" />
      <h4>{{ $t('goods.else') }}</h4>
      <el-form label-position="top">
        <el-row :gutter="40">
          <el-col :span="device==='desktop' ? 12 : 24">
            <el-form-item>
              <el-checkbox-group v-model="checked">
                <el-checkbox v-for="(item, index) in options" :key="index" :label="item">{{ $t('store.'+item) }}</el-checkbox>
              </el-checkbox-group>
            </el-form-item>
            <el-form-item :label="$t('store.minStock')" prop="minStock">
              <div class="df"><el-input-number v-model="elseForm.minStock" class="f1" :min="0" :disabled="!checked.includes('is_tips')" /></div>
            </el-form-item>
            <el-form-item :label="$t('store.is_freight')" prop="freight_price">
              <div class="df"><el-input-number v-model="elseForm.freight_price" class="f1" :min="0" :disabled="!checked.includes('is_freight')" /></div>
            </el-form-item>
            <el-form-item :label="$t('store.diamondCoinRate')">
              <div class="df"><el-input-number v-model="elseForm.diamond_coin_rate" class="f1" :min="0.01" :precision="2" :step="1" /></div>
            </el-form-item>
            <el-form-item :label="$t('store.diamondExchangeEnable')">
              <el-switch v-model="elseForm.diamond_exchange_enable" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="註冊需郵箱驗證">
              <el-switch v-model="elseForm.email_verify_required" :active-value="1" :inactive-value="0" />
              <div class="gray9 mt4">開啟後：用戶必須通過郵箱驗證才能註冊並登錄；關閉後：手機號/郵箱均可直接註冊登錄。</div>
            </el-form-item>
            <el-form-item :label="$t('store.releasedate')">
              <div>
                <el-date-picker
                  v-model="elseForm.release_date"
                  type="date"
                  format="yyyy-MM-dd"
                  value-format="yyyy-MM-dd"
                />
              </div>
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
    </el-card>
  </div>
</template>

<script>
import { validEmail } from '@/utils/validate'
import { getStore, editStore } from '@/api'
import { store } from '@/form'
export default {
  name: 'MemberList',
  data() {
    return {
      isLoading: false,
      EFC: store,
      baseEcho: {},
      baseRules: {
        shop_name: [{ required: true, message: this.$t('common.required'), trigger: 'blur' }],
        shop_address: [{ required: true, message: this.$t('common.required'), trigger: 'blur' }],
        en_shop_address: [{ required: true, messages: this.$t('common.required'), trigger: 'blur' }],
        shop_phone: [{ required: true, message: this.$t('common.required'), trigger: 'blur' }],
        shop_mail: [
          { required: true, message: this.$t('common.required'), trigger: 'blur' },
          { trigger: 'blur', validator: (rule, value, callback) => validEmail(value) ? callback() : callback(new Error(this.$t('common.emailErr'))) }
        ],
        shop_web: [{ required: true, message: this.$t('common.required'), trigger: 'blur' }]
      },
      checked: [],
      options: ['is_tips', 'is_freight'],
      elseForm: {
        shop_id: 1,
        minStock: 0,
        freight_price: 0,
        diamond_coin_rate: 1,
        diamond_exchange_enable: 1,
        email_verify_required: 0,
        release_date: ''
      },
      isLoadSub: false
    }
  },
  created() {
    this.getStore()
  },
  methods: {
    // 获取店铺信息
    async getStore() {
      try {
        this.isLoading = true
        const { results: {
          shop_logo, shop_name, shop_address, en_shop_address, shop_phone, shop_mail, shop_web,
          is_tips, is_freight,
          shop_id, minStock, freight_price, diamond_coin_rate, diamond_exchange_enable, email_verify_required, release_date,
          app_background_img
        }} = await getStore()
        this.baseEcho = { shop_id, shop_logo, shop_name, shop_address, en_shop_address, shop_phone, shop_mail, shop_web, app_background_img }
        const iss = { is_tips, is_freight }
        for (const i in iss) {
          if (iss[i] === 1) {
            this.checked.push(i)
          }
        }
        Object.assign(this.elseForm, { shop_id, minStock, freight_price, diamond_coin_rate, diamond_exchange_enable, email_verify_required, release_date })
      } catch (error) {

      } finally {
        this.isLoading = false
      }
    },
    // 提交
    async submit() {
      this.$refs.baseForm.submit(async data => {
        const { minStock, freight_price } = this.elseForm
        if (this.checked.includes('is_tips') && !minStock) return this.$message({ message: this.$t('store.stockErr'), type: 'warning' })
        if (this.checked.includes('is_freight') && !freight_price) return this.$message({ message: this.$t('store.freightErr'), type: 'warning' })

        const iss = {}
        this.options.forEach(item => {
          iss[item] = this.checked.includes(item) ? 1 : 0
        })
        try {
          this.isLoadSub = true
          await editStore(Object.assign({}, data, iss, this.elseForm))
          this.$message({ message: this.$t('common.success'), type: 'success' })
        } catch (error) {

        } finally {
          this.isLoadSub = false
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
</style>
