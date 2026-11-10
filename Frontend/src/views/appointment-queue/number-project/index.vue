<template>
  <div class="app-container">
    <div class="df mb20" v-if="device==='desktop'">
      <div class="control-search">
        <el-input :placeholder="$t('appoNum.name')" clearable v-model="reservation_project_name" class="search">
          <el-button slot="append" icon="el-icon-search" @click="getList('AppoNum', {reservation_project_name})"></el-button>
        </el-input>
      </div>
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true">{{$t('common.add')}}</el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('AppoNum')"></el-button>
    </div>
    <el-row v-else>
      <el-col :span="24">
        <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true"></el-button>
        <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
        <el-button type="primary" icon="el-icon-refresh" plain @click="getList('AppoNum')"></el-button>
      </el-col>
      <el-col :span="24">
        <div class="control-search mtb20">
          <el-input :placeholder="$t('appoNum.name')" clearable v-model="reservation_project_name" class="search">
            <el-button slot="append" icon="el-icon-search" @click="getList('AppoNum', {reservation_project_name})"></el-button>
          </el-input>
        </div>
      </el-col>
    </el-row>

    <!--  -->
    <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('AppoNum', $event)" @selection-change="delIds=$event.map(({reservation_project_id})=>reservation_project_id)">
      <template #reservation_project_url="{row}"><ProImage :src="row.reservation_project_url"></ProImage></template>
      <template #reservation_project_status="{row}"><el-tag :type="row.reservation_project_status ? '' : 'danger'">{{$t(statusTxt[row.reservation_project_status])}}</el-tag></template>
    </ProTable>

    <!--  -->
    <el-dialog :title="$t(`appoNum.${this.echo.reservation_project_id ? 'editAppoNum' : 'addAppoNum'}`)" :visible.sync="show" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules"></ProForm>
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { editAppoNum, delAppoNum } from '@/api';
import { appoNum } from '@/form';
import paging from '@/mixins/paging'
export default {
  name: 'NumberProject',
  mixins: [paging],
  data() {
    return {
      EFC: appoNum,
      reservation_project_name: '',
      show: false,
      delIds: [],
      column: {
        reservation_project_url: 'appoNum.img',
        reservation_project_name: 'appoNum.zh_name',
        en_reservation_project_name: 'appoNum.en_name',
        reservation_project_describe: 'appoNum.zh_desc',
        en_reservation_project_describe: 'appoNum.en_desc',
        reservation_project_badge: 'appoNum.badge',
        reservation_project_stock: 'appoNum.stock',
        reservation_project_time: 'appoNum.time',
        reservation_project_status: 'common.status'
      },
      echo: {},
      isLoadSub: false,
      rules: {
        reservation_project_name: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        en_reservation_project_name: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        reservation_project_badge: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
      }
    }
  },
  created() {
    this.getList('AppoNum')
  },
  watch: {
    reservation_project_name(val) {
      if (!val) {
        this.getList('AppoNum')
      }
    }

  },
  methods: {
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
      this.$set(this.EFC.formItems[4][1].item.attr, 'disabled', true)
    },
    // 编辑
    edit({reservation_project_id, reservation_project_url, reservation_project_name, en_reservation_project_name, reservation_project_describe, en_reservation_project_describe, reservation_project_badge, reservation_project_stock, reservation_project_time, reservation_project_status}) {
      this.$set(this.EFC.formItems[4][1].item.attr, 'disabled', false)
      this.echo = {reservation_project_id, reservation_project_url, reservation_project_name, en_reservation_project_name, reservation_project_describe, en_reservation_project_describe, reservation_project_badge, reservation_project_stock, reservation_project_time, reservation_project_status}
      this.show = true
    },
    // 删除
    async del({reservation_project_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delAppoNum({reservation_project_id: reservation_project_id ? [reservation_project_id] : this.delIds})
          if (reservation_project_id) {
            this.list = this.list.filter(item => item.reservation_project_id !== reservation_project_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.reservation_project_id))
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
        const params = this.echo.reservation_project_id ? data : {reservation_project_id: '', ...data}
        try {
          this.isLoadSub = true
          await editAppoNum(params)
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.show = false
          this.getList('AppoNum')
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