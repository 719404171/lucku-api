<template>
  <div class="app-container">
    <div class="mb20" v-if="device==='desktop'">
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true">{{$t('common.add')}}</el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('Faq')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Faq')"></el-button>
    </div>
    <div class="mb20" v-else>
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true"></el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('Faq')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Faq')"></el-button>
    </div>

    <!--  -->
    <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('Faq', $event)" @selection-change="delIds=$event.map(({faq_id})=>faq_id)">
      <template #faq_status="{row}"><el-tag :type="row.faq_status ? '' : 'danger'">{{$t(statusTxt[row.faq_status])}}</el-tag></template>
    </ProTable>

    <!--  -->
    <el-dialog :title="$t(`faq.${this.echo.faq_id ? 'editQus' : 'addQus'}`)" :visible.sync="show" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules"></ProForm>
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { editFaq, delFaq } from '@/api';
import { faq } from '@/form';
import paging from '@/mixins/paging'
export default {
  name: 'FaqList',
  mixins: [paging],
  data() {
    return {
      SFC: faq.SFC,
      EFC: faq.EFC,
      show: false,
      delIds: [],
      column: {
        faq_title: 'faq.zh_qus',
        faq_result: 'faq.zh_anw',
        faq_status: 'common.status'
      },
      echo: {},
      isLoadSub: false,
      rules: {
        faq_title: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        faq_result: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
      }
    }
  },
  created() {
    this.getList('Faq')
  },
  methods: {
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
      // this.$set(this.EFC.formItems[3].item.attr, 'disabled', false)
    },
    // 编辑
    edit({faq_id, faq_title, faq_result, faq_status}) {
      // this.$set(this.EFC.formItems[3].item.attr, 'disabled', false)
      this.echo = {faq_id, faq_title, faq_result, faq_status}
      this.show = true
    },
    // 删除
    async del({faq_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delFaq({faq_id: faq_id ? [faq_id] : this.delIds})
          if (faq_id) {
            this.list = this.list.filter(item => item.faq_id !== faq_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.faq_id))
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
        const params = this.echo.faq_id ? data : {faq_id: '', ...data}
        try {
          this.isLoadSub = true
          await editFaq(params)
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.show = false
          this.getList('Faq')
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