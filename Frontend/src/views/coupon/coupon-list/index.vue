<template>
  <div class="app-container">
    <div class="mb20" v-if="device==='desktop'">
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true">{{$t('common.add')}}</el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('Coupon')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Coupon')"></el-button>
    </div>
    <div class="mb20" v-else>
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true"></el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('Coupon')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Coupon')"></el-button>
    </div>

    <!--  -->
    <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('Coupon', $event)" @selection-change="delIds=$event.map(({coupon_id})=>coupon_id)">
      <template #coupon_type="{row}">{{$t('coupon.type'+row.coupon_type)}}</template>
      <template #coupon_status="{row}"><el-tag :type="row.coupon_status ? '' : 'danger'">{{$t(statusTxt[row.coupon_status])}}</el-tag></template>
    </ProTable>

    <!--  -->
    <el-dialog :title="$t(`coupon.${this.echo.coupon_id ? 'editCoupon' : 'addCoupon'}`)" :visible.sync="show" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules"></ProForm>
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { editCoupon, delCoupon } from '@/api'
import api from '@/api'
import { coupon } from '@/form'
import paging from '@/mixins/paging'
import { mapGetters } from 'vuex'
export default {
  name: 'CouponList',
  mixins: [paging],
  computed: {
    ...mapGetters(['language'])
  },
  data() {
    return {
      SFC: coupon.SFC,
      EFC: coupon.EFC,
      show: false,
      delIds: [],
      column: {
        coupon_name: 'coupon.zh_name',
        en_coupon_name: 'coupon.en_name',
        coupon_type: 'coupon.type',
        discount: 'coupon.discount',
        lowest: 'coupon.lowest',
        member_name: 'coupon.member_level',
        conversion_price: 'coupon.conversion_price',
        coupon_stock: 'goods.stock',
        instructions: 'coupon.zh_inst',
        en_instructions: 'coupon.en_inst',
        expiration_time: 'coupon.expiration_time',
        username: 'coupon.admin',
        coupon_status: 'common.status'
      },
      echo: {},
      isLoadSub: false,
      rules: {
        coupon_name: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        en_coupon_name: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        instructions: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        en_instructions: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        coupon_type: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
      }
    }
  },
  created() {
    this.getList('Coupon')
    this.getMemberLevels()
  },
  methods: {
    async getMemberLevels() {
      const { results } = await api.reqLevel()
      const options = results.data.map(item => ({
        label: this.language === 'en' ? item.en_member_name : item.member_name,
        value: item.member_id,
        trans: '1'
      }))
      this.$set(this.EFC.formItems[3][0].item, 'options', options)
    },
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
      // this.$set(this.EFC.formItems[3].item.attr, 'disabled', true)
    },
    // 编辑
    edit({username, is_end, updated_at, created_at, member_name, en_member_name, ...data}) {
      // this.$set(this.EFC.formItems[3].item.attr, 'disabled', false)
      this.echo = data
      this.show = true
    },
    // 删除
    async del({coupon_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delCoupon({coupon_id: coupon_id ? [coupon_id] : this.delIds})
          if (coupon_id) {
            this.list = this.list.filter(item => item.coupon_id !== coupon_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.coupon_id))
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
    submit() {
      this.$refs.editForm.submit(async (data) => {
        try {
          this.isLoadSub = true
          const params = this.echo.coupon_id ? data : {coupon_id: '', admin_id: this.$store.state.user.detail.admin_id, ...data}
          await editCoupon(params)
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.show = false
          this.getList('Coupon')
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