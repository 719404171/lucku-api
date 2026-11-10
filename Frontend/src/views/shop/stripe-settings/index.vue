<template>
  <div class="app-container">
    <div class="mb20">
      <el-button v-if="device==='desktop' && nodePower.includes('add')" type="success" icon="el-icon-plus" @click="show=true">{{ $t('common.add') }}</el-button>
      <el-button v-if="device==='mobile' && nodePower.includes('add')" type="success" icon="el-icon-plus" @click="show=true" />
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('Stripe')" />
    </div>

    <!--  -->
    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column" :row-control="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('Stripe', $event)" @selection-change="delIds=$event.map(({stripe_id})=>stripe_id)">
      <template #stripe_enable="{row}">{{ $t('common.'+(row.stripe_enable===1 ? 'yes' : 'no')) }}</template>
      <template #stripe_env="{row}">{{ row.stripe_env===1 ? 'live' : 'sandbox' }}</template>
      <template #stripe_status="{row}"><el-tag :type="row.stripe_status ? '' : 'danger'">{{ $t(statusTxt[row.stripe_status]) }}</el-tag></template>
    </ProTable>

    <!--  -->
    <el-dialog :title="$t(`stripe.${this.echo.stripe_id ? 'editStripe' : 'addStripe'}`)" :visible.sync="show" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules" />
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{ $t('common.cancel') }}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{ $t('common.confirm') }}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { editStripe, delStripe } from '@/api'
import { stripe } from '@/form'
import paging from '@/mixins/paging'
export default {
  name: 'StripeSetting',
  mixins: [paging],
  data() {
    return {
      EFC: stripe,
      show: false,
      delIds: [],
      column: {
        stripe_key: 'stripe.stripe_key',
        stripe_secret: 'stripe.stripe_secret',
        stripe_enable: 'stripe.stripe_enable',
        stripe_env: 'stripe.stripe_env',
        stripe_status: 'common.status'
      },
      echo: {},
      isLoadSub: false,
      rules: {
        stripe_key: [{ required: true, message: this.$t('common.required'), trigger: 'blur' }],
        stripe_secret: [{ required: true, message: this.$t('common.required'), trigger: 'blur' }]
      }
    }
  },
  created() {
    this.getList('Stripe')
  },
  methods: {
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
    },
    // 编辑
    edit({ stripe_id, stripe_key, stripe_secret, stripe_enable, stripe_env, stripe_status }) {
      this.echo = { stripe_id, stripe_key, stripe_secret, stripe_enable, stripe_env, stripe_status }
      this.show = true
    },
    // 删除
    async del({ stripe_id } = {}) {
      this.asyncMsg(async(instance, done) => {
        instance.confirmButtonLoading = true
        try {
          await delStripe({ stripe_id: stripe_id ? [stripe_id] : this.delIds })
          if (stripe_id) {
            this.list = this.list.filter(item => item.stripe_id !== stripe_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.stripe_id))
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
    submit() {
      this.$refs.editForm.submit(async(data) => {
        const params = this.echo.stripe_id ? data : { stripe_id: '', ...data }
        try {
          this.isLoadSub = true
          await editStripe(params)
          this.$message({ message: this.$t('common.success'), type: 'success' })
          this.show = false
          this.getList('Stripe')
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
