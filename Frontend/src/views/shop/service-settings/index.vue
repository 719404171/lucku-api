<template>
  <div class="app-container">
    <div class="mb20">
      <el-button type="success" icon="el-icon-plus" v-if="device==='desktop' && nodePower.includes('add')" @click="show=true">{{$t('common.add')}}</el-button>
      <el-button type="success" icon="el-icon-plus" v-if="device==='mobile' && nodePower.includes('add')" @click="show=true"></el-button>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('Service')"></el-button>
    </div>

    <!--  -->
    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('Service', $event)" @selection-change="delIds=$event.map(({service_id})=>service_id)">
      <template #service_status="{row}"><el-tag :type="row.service_status ? '' : 'danger'">{{$t(statusTxt[row.service_status])}}</el-tag></template>
    </ProTable>

    <!--  -->
    <el-dialog :title="$t(`service.${this.echo.service_id ? 'editService' : 'addService'}`)" :visible.sync="show" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules"></ProForm>
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { editService, delService } from '@/api';
import { service } from '@/form';
import paging from '@/mixins/paging'
export default {
  name: 'ServiceSetting',
  mixins: [paging],
  data() {
    return {
      EFC: service,
      show: false,
      delIds: [],
      column: {
        service_url: 'service.url',
        service_account: 'service.account',
        service_status: 'common.status'
      },
      echo: {},
      isLoadSub: false,
      rules: {
        service_url: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        service_account: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
      }
    }
  },
  created() {
    this.getList('Service')
  },
  methods: {
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
    },
    // 编辑
    edit({service_id, service_url, service_account, service_status}) {
      this.echo = {service_id, service_url, service_account, service_status}
      this.show = true
    },
    // 删除
    async del({service_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delService({service_id: service_id ? [service_id] : this.delIds})
          if (service_id) {
            this.list = this.list.filter(item => item.service_id !== service_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.service_id))
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
        const params = this.echo.service_id ? data : {service_id: '', ...data}
        try {
          this.isLoadSub = true
          await editService(params)
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.show = false
          this.getList('Service')
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