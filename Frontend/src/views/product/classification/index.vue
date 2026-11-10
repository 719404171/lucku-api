<template>
  <div class="app-container">
    <div class="mb20" v-if="device==='desktop'">
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true">{{$t('common.add')}}</el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('GoodsCate')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'GoodsCate')"></el-button>
    </div>
    <div class="mb20" v-else>
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true"></el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('GoodsCate')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'GoodsCate')"></el-button>
    </div>

    <!--  -->
    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('GoodsCate', $event)">
      <template #product_sort_img="{row}"><ProImage :src="row.product_sort_img || ''"></ProImage></template>
      <template #status="{row}"><el-tag :type="row.status ? '' : 'danger'">{{$t(statusTxt[row.status])}}</el-tag></template>
    </ProTable>

    <!--  -->
    <el-dialog :title="$t(`goodsCate.${this.echo.product_sort_id ? 'editCate' : 'addCate'}`)" :visible.sync="show" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules"></ProForm>
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { addGoodsCate, delGoodsCate } from '@/api'
import { goodsCate } from '@/form'
import paging from '@/mixins/paging'
export default {
  name: 'ProductClassification',
  mixins: [paging],
  data() {
    return {
      SFC: goodsCate.SFC,
      EFC: goodsCate.EFC,
      show: false,
      delIds: [],
      column: {
        product_sort_img: 'common.image',
        product_sort_name: 'goodsCate.zh_name',
        status: 'common.status'
      },
      echo: {},
      isLoadSub: false,
      rules: {
        product_sort_name: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
      }
    }
  },
  created() {
    this.getList('GoodsCate')
  },
  methods: {
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
    },
    // 编辑
    edit({created_at, updated_at, ...data}) {
      this.echo = {
        ...data,
        product_sort_img: data.product_sort_img || ''
      }
      this.show = true
    },
    // 删除
    async del({product_sort_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delGoodsCate({product_sort_id})
          this.list = this.list.filter(item => item.product_sort_id !== product_sort_id)
          this.page.total--
          done()
          this.$message({message: this.$t('common.success'),type: 'success'})
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
          const params = this.echo.product_sort_id ? data : {product_sort_id: '', ...data}
          await addGoodsCate(params)
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.show = false
          this.getList('GoodsCate')
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