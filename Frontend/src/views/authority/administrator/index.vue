<template>
<div class="app-container">
  <div class="mb20" v-if="device==='desktop'">
    <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true">{{$t('common.add')}}</el-button>
    <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
    <el-popover class="mlr10" placement="bottom" trigger="click">
      <ProForm ref="searchForm" :config="SFC"></ProForm>
      <el-button type="primary" @click="onSearch('Admin')">{{$t('common.confirm')}}</el-button>
      <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
    </el-popover>
    <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Admin')"></el-button>
  </div>
  <div class="mb20" v-else>
    <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true"></el-button>
    <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
    <el-popover class="mlr10" placement="bottom" trigger="click">
      <ProForm ref="searchForm" :config="SFC"></ProForm>
      <el-button type="primary" @click="onSearch('Admin')">{{$t('common.confirm')}}</el-button>
      <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
    </el-popover>
    <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Admin')"></el-button>
  </div>

  <!--  -->
  <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('Admin', $event)" @selection-change="delIds=$event.map(({admin_id})=>admin_id)">
    <template #avatar="{row}">
      <el-avatar shape="square" v-bind="row.avatar ? {src: row.avatar} : {icon: 'el-icon-user-solid'}"></el-avatar>
    </template>
    <template #status="{row}">
      <el-tag :type="row.status ? '' : 'danger'">{{$t(statusTxt[row.status])}}</el-tag>
    </template>
  </ProTable>

  <!--  -->
  <el-dialog :title="$t(`admin.${this.echo.admin_id ? 'editAdmin' : 'addAdmin'}`)" :visible.sync="show" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
    <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules"></ProForm>
    <span slot="footer" class="dialog-footer">
      <el-button @click="show = false">{{$t('common.cancel')}}</el-button>
      <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
    </span>
  </el-dialog>
</div>
</template>

<script>
import {validEmail} from '@/utils/validate'
import {getAllRole,addAdmin,editAdmin,delAdmin} from '@/api'
import {admin} from '@/form'
import paging from '@/mixins/paging'
export default {
  name: 'Administrator',
  mixins: [paging],
  data() {
    return {
      SFC: admin.SFC,
      EFC: admin.EFC,
      show: false,
      delIds: [],
      column: {
        avatar: 'common.avatar',
        username: 'admin.name',
        role_name: 'role.name',
        email: 'common.email',
        phone: 'common.tel',
        status: 'common.status'
      },
      echo: {},
      isLoadSub: false,
      rules: {
        username: [
          {required: true,message: this.$t('common.required'),trigger: "blur"},
          {min: 3,max: 16,message: this.$t('login.unErr'),trigger: 'blur'}
        ],
        password: [
          {required: true,message: this.$t('common.required'),trigger: "blur"},
          {min: 5,max: 18,message: this.$t('login.psdErr'),trigger: 'blur'}
        ],
        role_id: [
          {required: true,message: this.$t('common.required'),trigger: "blur"}
      ],
        phone: [
          {required: true,message: this.$t('common.required'),trigger: "blur"}
      ],
        email: [
          {required: true,message: this.$t('common.required'),trigger: "blur"},
          {trigger: "blur",validator: (rule, value, callback) => validEmail(value) ? callback() : callback(new Error(this.$t('common.emailErr')))}
        ]
      }
    }
  },
  created() {
    this.init()
    this.getList('Admin')
  },
  methods: {
    async init() {
      const {results} = await getAllRole()
      const roles = results.map(({role_id,role_name,en_role_name}) => ({label: role_name || en_role_name,value: role_id, trans: '1'}))
      this.$set(this.SFC.formItems[1].item, 'options', roles)
      this.$set(this.EFC.formItems[3].item, 'options', roles)
    },
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
      this.$set(this.EFC.formItems[2].item.attr, 'disabled', false)
      this.$set(this.EFC.formItems[4].item.attr, 'disabled', true)
      const index = this.rules.password.findIndex(item => item.required)
      index < 0 ? this.rules.password.push({required: true,trigger: "blur"}) : null
    },
    // 编辑
    edit(scope) {
      this.$set(this.EFC.formItems[2].item.attr, 'disabled', true)
      this.$set(this.EFC.formItems[4].item.attr, 'disabled', false)
      const index = this.rules.password.findIndex(item => item.required)
      index > -1 ? this.rules.password.splice(index, 1) : null
      this.echo = scope
      this.show = true
    },
    // 批量删除
    async del({admin_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delAdmin({adminID: admin_id ? [admin_id] : this.delIds})
          if (admin_id) {
            this.list = this.list.filter(item => item.admin_id !== admin_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.admin_id))
            this.page.total -= this.delIds.length
          }
          this.delIds = []
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
      this.$refs.editForm.submit(async ({role_name,...data}) => {
        try {
          this.isLoadSub = true
          if (this.echo.admin_id) {
            await editAdmin(data)
          } else {
            await addAdmin(data)
          }
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.show = false
          this.getList('Admin')
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
