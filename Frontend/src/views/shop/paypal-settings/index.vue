<template>
  <div class="app-container">
    <div class="mb20">
      <el-button v-if="device==='desktop' && nodePower.includes('add')" type="success" icon="el-icon-plus" @click="show=true">{{ $t('common.add') }}</el-button>
      <el-button v-if="device==='mobile' && nodePower.includes('add')" type="success" icon="el-icon-plus" @click="show=true" />
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('Paypal')" />
    </div>

    <!--  -->
    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column" :row-control="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('Paypal', $event)" @selection-change="delIds=$event.map(({paypal_id})=>paypal_id)">
      <template #paypal_enable="{row}">{{ $t('common.'+(row.paypal_enable===1 ? 'yes' : 'no')) }}</template>
      <template #paypal_env="{row}">{{ row.paypal_env===1 ? 'live' : 'sandbox' }}</template>
      <template #paypal_status="{row}"><el-tag :type="row.paypal_status ? '' : 'danger'">{{ $t(statusTxt[row.paypal_status]) }}</el-tag></template>
    </ProTable>

    <!--  -->
    <el-dialog :title="$t(`paypal.${this.echo.paypal_id ? 'editPaypal' : 'addPaypal'}`)" :visible.sync="show" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules" />
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{ $t('common.cancel') }}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{ $t('common.confirm') }}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { editPaypal, delPaypal } from '@/api'
import { paypal } from '@/form'
import paging from '@/mixins/paging'
export default {
  name: 'PaypalSetting',
  mixins: [paging],
  data() {
    return {
      EFC: paypal,
      show: false,
      delIds: [],
      column: {
        client_id: 'paypal.client_id',
        paypal_secret: 'paypal.paypal_secret',
        paypal_enable: 'paypal.paypal_enable',
        paypal_env: 'paypal.paypal_env',
        paypal_status: 'common.status'
      },
      echo: {},
      isLoadSub: false,
      rules: {
        client_id: [{ required: true, message: this.$t('common.required'), trigger: 'blur' }],
        paypal_secret: [{ required: true, message: this.$t('common.required'), trigger: 'blur' }]
      }
    }
  },
  created() {
    this.getList('Paypal')
  },
  methods: {
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
    },
    // 编辑
    edit({ paypal_id, client_id, paypal_secret, paypal_enable, paypal_env, paypal_status }) {
      this.echo = { paypal_id, client_id, paypal_secret, paypal_enable, paypal_env, paypal_status }
      this.show = true
    },
    // 删除
    async del({ paypal_id } = {}) {
      this.asyncMsg(async(instance, done) => {
        instance.confirmButtonLoading = true
        try {
          await delPaypal({ paypal_id: paypal_id ? [paypal_id] : this.delIds })
          if (paypal_id) {
            this.list = this.list.filter(item => item.paypal_id !== paypal_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.paypal_id))
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
        const params = this.echo.paypal_id ? data : { paypal_id: '', ...data }
        try {
          this.isLoadSub = true
          await editPaypal(params)
          this.$message({ message: this.$t('common.success'), type: 'success' })
          this.show = false
          this.getList('Paypal')
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
