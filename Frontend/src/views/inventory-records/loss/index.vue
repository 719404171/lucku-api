<template>
  <div class="app-container">
    <div class="mb20" v-if="device==='desktop'">
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true">{{$t('common.add')}}</el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('Loss')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Loss')"></el-button>
    </div>
    <div class="mb20" v-else>
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true"></el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click" width="320">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('Loss')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Loss')"></el-button>
    </div>

    <!--  -->
    <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('Loss', $event)" @selection-change="delIds=$event.map(({wastage_id})=>wastage_id)"> </ProTable>

    <!--  -->
    <el-dialog :title="$t(`loss.${this.echo.wastage_id ? 'editLoss' : 'addLoss'}`)" :visible.sync="show" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules"></ProForm>
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { getSku, addLoss, editLoss, delLoss } from '@/api'
import { loss } from '@/form'
import paging from '@/mixins/paging'
export default {
  name: 'LossList',
  mixins: [paging],
  data() {
    return {
      SFC: loss.SFC,
      EFC: loss.EFC,
      show: false,
      delIds: [],
      column: {
        product_name: 'goods.name',
        product_specs: 'goods.specs',
        wastage_number: 'loss.number',
        wastage_remarks: 'loss.remark',
        username: 'coupon.admin',
        // created_at: 'common.created_at',
        // updated_at: 'common.updated_at',
      },
      echo: {},
      skuData: [],
      isLoadSub: false,
      rules: {
        product: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
      }
    }
  },
  created() {
    this.init()
    this.getList('Loss')
  },
  methods: {
    async init() {
      const {results} = await getSku()
      const skuOption = results.map(({product_name, product_id, product_relation}) => ({
        label: product_name, value: product_id, children: product_relation.map(({product_relation_id, product_specs_value}) => {
          const product_specs = product_specs_value.map(({product_specs_value}) => product_specs_value).join('-')
          return {label: product_specs, value: `${product_relation_id},${product_specs}`}
        })
      }))
      this.$set(this.EFC.formItems[0].item.attr, 'options', skuOption)
    },
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
      this.$set(this.EFC.formItems[0].item.attr, 'disabled', false)
    },
    // 编辑
    edit({wastage_id, product_id, product_relation_id, product_specs, wastage_number, wastage_remarks}) {
      this.$set(this.EFC.formItems[0].item.attr, 'disabled', true)
      const product = product_relation_id && product_specs ? [product_id, `${product_relation_id},${product_specs}`] : [product_id]
      this.echo = {product, wastage_id, wastage_number, wastage_remarks}
      this.show = true
    },
    // 删除
    async del({wastage_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delLoss({wastage_id: wastage_id ? [wastage_id] : this.delIds})
          if (wastage_id) {
            this.list = this.list.filter(item => item.wastage_id !== wastage_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.wastage_id))
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
        const {product: [product_id, spec=''], wastage_id, ...elsePar} = data
        const [product_relation_id='', product_specs=''] = spec.split(',')
        const admin_id = this.$store.state.user.detail.admin_id
        try {
          this.isLoadSub = true
          if (this.echo.wastage_id) {
            await editLoss({wastage_id, product_id, admin_id, ...elsePar})
          } else {
            await addLoss({admin_id, product_id, product_relation_id, product_specs, ...elsePar})
          }
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.show = false
          this.getList('Loss')
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

</style>