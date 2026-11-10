<template>
  <div class="app-container">
    <div class="mb20" v-if="device==='desktop'">
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true">{{$t('common.add')}}</el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('Purchase')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Purchase')"></el-button>
    </div>
    <div class="mb20" v-else>
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true"></el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click" width="320">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('Purchase')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Purchase')"></el-button>
    </div>

    <!--  -->
    <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('Purchase', $event)" @selection-change="delIds=$event.map(({purchase_id})=>purchase_id)">
      <template #control="{scope:{row}}">
        <el-link :class="$store.state.app.size" :underline="false" type="primary" :title="$t('purchase.email')" :disabled="!!row.purchase_send" v-if="nodePower.includes('send')" @click="send(row)">{{ $t('purchase.'+ (row.purchase_send ? 'sent' : 'send')) }}</el-link>
        <el-link :class="$store.state.app.size" :underline="false" type="success" :title="$t('purchase.'+(row.purchase_state ? 'ow' : 'ew'))" v-if="nodePower.includes('oew') && row.purchase_send" :disabled="row.purchase_state===2" @click="warehouse(row)">{{ $t('purchase.'+(row.purchase_state ? 'out' : 'enter')) }}</el-link>
      </template>
    </ProTable>

    <!--  -->
    <el-dialog :title="$t(`purchase.${this.echo.purchase_id ? 'editPurchase' : 'addPurchase'}`)" :visible.sync="show" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules"></ProForm>
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { validEmail } from '@/utils/validate'
import { getSku, editPurchase, delPurchase, emailPurchase, warehousePurchase } from '@/api'
import { purchase } from '@/form'
import paging from '@/mixins/paging'
export default {
  name: 'PurchaseList',
  mixins: [paging],
  data() {
    return {
      SFC: purchase.SFC,
      EFC: purchase.EFC,
      show: false,
      delIds: [],
      column: {
        product_name: 'goods.name',
        product_specs: 'goods.specs',
        purchase_number: 'purchase.number',
        purchase_cost: 'purchase.cost',
        purchase_remarks: 'purchase.remark',
        purchase_supplier: 'purchase.supplier',
        purchase_contact: 'purchase.contact',
        username: 'coupon.admin',
        // created_at: 'common.created_at',
        // updated_at: 'common.updated_at',
      },
      echo: {},
      skuData: [],
      skuList:[],
      isLoadSub: false,
      rules: {
        product: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        purchase_supplier: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        purchase_contact: [
          {required: true,message: this.$t('common.required'),trigger: "blur"},
          {trigger: "blur",validator: (rule, value, callback) => validEmail(value) ? callback() : callback(new Error(this.$t('common.emailErr')))}
        ]
      }
    }
  },
  created() {
    this.init()
    this.getList('Purchase')
    this.$set(this.EFC.formItems[0].item.listeners, 'change', data=>this.handleSpecs(data))

  },
  methods: {
    handleSpecs(data){
      const { purchase_contact,purchase_supplier } = this.skuList.find(v=>v.product_id === data[0])
      this.$set(this.echo,'purchase_contact',purchase_contact)
      this.$set(this.echo,'purchase_supplier',purchase_supplier)
    },
    async init() {
      const {results} = await getSku()
      this.skuList = results
      const skuOption = results.map(({product_name, product_id, product_relation}) => ({
        label: product_name, value: product_id, children: product_relation.map(({product_relation_id, product_specs_value}) => {
          const product_specs = product_specs_value.map(({product_specs_value}) => product_specs_value).join('-')
          return {label: product_specs, value: `${product_relation_id},${product_specs}`}
        })
      }))
      this.$set(this.EFC.formItems[0].item.attr, 'options', skuOption)
    },
    // 发送邮件
    async send({purchase_id}) {
      try {
        this.isLoading = true
        await emailPurchase({purchase_id})
        this.getList('Purchase')
        this.$message({message: this.$t('common.success'),type: 'success'})
      } catch (error) {

      } finally {
        this.isLoading = false
      }
    },
    // 进退仓
    async warehouse({purchase_id, purchase_state,admin_id}) {
      const purchase_type = purchase_state ? 2 : 1
      try {
        this.isLoading = true
        await warehousePurchase({purchase_id, purchase_type,admin_id})
        this.getList('Purchase')
        this.$message({message: this.$t('common.success'),type: 'success'})
      } catch (error) {

      } finally {
        this.isLoading = false
      }
    },
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
    },
    // 编辑
    edit({product_id, product_relation_id, product_specs, purchase_id, purchase_number, purchase_remarks, purchase_supplier, purchase_contact, purchase_cost}) {
      const product = product_relation_id && product_specs ? [product_id, `${product_relation_id},${product_specs}`] : [product_id]
      this.echo = {product, purchase_id, purchase_number, purchase_remarks, purchase_supplier, purchase_contact, purchase_cost}
      this.show = true
    },
    // 删除
    async del({purchase_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delPurchase({purchase_id: purchase_id ? [purchase_id] : this.delIds})
          if (purchase_id) {
            this.list = this.list.filter(item => item.purchase_id !== purchase_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.purchase_id))
            this.page.total -= this.delIds.length
          }
          this.delIds = []
          done()
        } catch (error) {

        } finally {
          instance.confirmButtonLoading = false;
        }
      })
    },
    // 操作
    onRcc({scope: {row},type}) {
      this[type](row)
    },
    // 提交表单
    submit() {
      this.$refs.editForm.submit(async (data) => {
        const {product: [product_id, spec=''], ...elsePar} = data
        const [product_relation_id='', product_specs=''] = spec.split(',')
        const admin_id = this.$store.state.user.detail.admin_id
        const params = this.echo.purchase_id ? {product_id, product_relation_id, product_specs, ...elsePar} : {purchase_id: '', product_id, product_relation_id, product_specs, ...elsePar}
        try {
          this.isLoadSub = true
          await editPurchase(Object.assign({admin_id}, params))
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.show = false
          this.getList('Purchase')
        } catch (error) {
          console.log(error);
        } finally {
          this.isLoadSub = false
        }
      })
    }
  }
}
</script>

<style>
.default {font-size: 14px;margin: 0 5px;}
.medium {font-size: 14px;margin: 0 5px;}
.small {font-size: 13px;margin: 0 5px;}
.mini {font-size: 12px;margin: 0 5px;}
</style>
