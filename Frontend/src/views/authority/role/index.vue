<template>
<div class="app-container">
  <div class="mb20 df" v-if="device==='desktop'">
    <div class="control-search">
      <el-input :placeholder="$t('role.namePh')" clearable v-model="role_name" class="search">
        <el-button slot="append" icon="el-icon-search" @click="getList('Role', {role_name})"></el-button>
      </el-input>
    </div>
    <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="showForm=true">{{$t('common.add')}}</el-button>
    <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
    <el-button type="primary" icon="el-icon-refresh" plain @click="getList('Role')"></el-button>
  </div>
  <el-row v-else>
    <el-col :span="24">
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="showForm=true"></el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('Role')"></el-button>
    </el-col>
    <el-col :span="24">
      <div class="control-search mtb20">
        <el-input :placeholder="$t('role.namePh')" clearable v-model="role_name" class="search">
          <el-button slot="append" icon="el-icon-search" @click="getList('Role', {role_name})"></el-button>
        </el-input>
      </div>
    </el-col>
  </el-row>

  <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('Role', $event)" @selection-change="delIds=$event.map(({role_id})=>role_id)"></ProTable>

  <el-dialog :title="$t(`role.${curId ? 'editRole' : 'addRole'}`)" :visible.sync="showForm" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="clearPower">
    <el-form :model="roleForm" :rules="rules" ref="roleForm" label-position="top">
      <el-form-item :label="$t('role.zh_name')" prop="role_name">
        <el-input :placeholder="$t('role.namePh')" v-model="roleForm.role_name"></el-input>
      </el-form-item>
      <el-form-item :label="$t('role.en_name')" prop="en_role_name">
        <el-input :placeholder="$t('role.namePh')" v-model="roleForm.en_role_name"></el-input>
      </el-form-item>
      <el-form-item :label="$t('role.power')">
        <el-checkbox v-model="isCheckAll" v-if="menus.length">{{$t('role.selectAll')}}</el-checkbox>
        <div class="df jcc" v-if="isReload"><el-button type="primary" icon="el-icon-refresh" plain @click="getAllMenus">{{ $t('common.reload') }}</el-button></div>
        <el-tree v-else ref="power" v-loading="isLoadTree" :empty-text="treeStatus" :data="menus" node-key="id" :props="{children: 'children', label: 'label'}" show-checkbox default-expand-all @check="checkPower"></el-tree>
      </el-form-item>
    </el-form>
    <span slot="footer" class="dialog-footer">
      <el-button @click="showForm = false">{{$t('common.cancel')}}</el-button>
      <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
    </span>
  </el-dialog>
</div>
</template>

<script>
import { addRole, editRole, delRole, getAllMenus, getPower } from '@/api'
import { recursion, kebabToCamel } from '@/utils'
import paging from '@/mixins/paging'
export default {
  name: 'Role',
  mixins: [paging],
  data() {
    return {
      role_name: '',
      column: {
        role_name: 'role.zh_name',
        en_role_name: 'role.en_name'
      },
      showForm: false,
      treeStatus: '',
      isLoadTree: false,
      menus: [],
      allMenu: [],
      allPower: [],
      allCheck: [],
      isCheckAll: false,
      isReload: false,
      curId: '',
      delIds: [],
      roleForm: {
        role_name: '',
        en_role_name: '',
        menu: [],
        power: []
      },
      isLoadSub: false,
      rules: {
        role_name: [
          { required: true, message: this.$t('common.required'), trigger: "blur" }
        ],
        en_role_name: [
          { required: true, message: this.$t('common.required'), trigger: "blur" }
        ]
      }
    }
  },
  created() {
    this.getAllMenus()
    this.getList('Role')
  },
  watch: {
    role_name(val) {
      if (!val) {
        this.getList('Role')
      }
    },
    // 是否全选权限
    isCheckAll(val) {
      this.$refs.power.setCheckedKeys(val ? this.menus.map(({id}) => id) : [])
      this.allCheck = this.$refs.power.getCheckedKeys()
    },
    // 处理权限选择
    allCheck(val) {
      this.roleForm.menu = this.allMenu.map(item => ({menu_id: item, status: val.includes(item) ? 1 : 0}))
      this.roleForm.power = this.allPower.map(item => {
        const [menu_id, power_id] = item.split('-')
        return {menu_id, power_id, status: val.includes(item) ? 1 : 0}
      })
    }
  },
  methods: {
    // 获取所有路由权限
    async getAllMenus() {
      try {
        this.isReload = false
        this.treeStatus = this.$t('common.loading')
        const ids = []
        const {results} = await getAllMenus()
        const {result} = recursion({data: results, match:[['id', 'label'], ['menu_id', 'menu_name']]}, item => {
          item.menu_name = item.type ? this.$t('common.'+item.type) : this.$t('route.'+kebabToCamel(item.path.substr(1)))
          ids.push(item.menu_id)
        })
        this.menus = result
        this.allMenu = ids.filter(item => typeof item === 'number')
        this.allPower = ids.filter(item => typeof item === 'string')
      } catch {
        this.treeStatus = this.$t('common.reload')
        this.isReload = true
      } finally {
        this.treeStatus = this.$t('common.nodata')
      }
    },
    // 勾选权限
    checkPower() {
      this.allCheck = [...this.$refs.power.getCheckedKeys(), ...this.$refs.power.getHalfCheckedKeys()]
    },
    // 重置表单
    clearPower() {
      this.curId = ''
      this.isCheckAll = false
      this.$refs.roleForm.resetFields();
      this.roleForm = {role_name: '',en_role_name: '',menu: [],power: []}
      if (this.$refs.power) this.$refs.power.setCheckedKeys([])
    },
    // 批量删除
    async del({role_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delRole({roleID: role_id ? [role_id] : this.delIds})
          if (role_id) {
            this.list = this.list.filter(item => item.role_id !== role_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.role_id))
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
    // 编辑
    async edit({role_id, role_name, en_role_name}) {
      try {
        this.curId = role_id
        Object.assign(this.roleForm, {role_name, en_role_name})
        this.showForm = true
        this.isLoadTree = true
        const {results} = await getPower({role_id})
        const ids = []
        recursion({data:results}, item => {
          if (item.type && item.status) {
            ids.push(item.menu_id)
          }
        })
        this.$refs.power.setCheckedKeys(ids)
        this.allCheck = [...this.$refs.power.getCheckedKeys(), ...this.$refs.power.getHalfCheckedKeys()]
      } catch (error) {
        
      } finally {
        this.isLoadTree = false
      }
    },
    // 操作
    onRcc({scope: {row}, type}) {
      this[type](row)
    },
    // 提交
    submit() {
      this.$refs.roleForm.validate(async valid => {
        if (!valid) return
        try {
          this.isLoadSub = true
          if (this.curId) {
            const {role_name, en_role_name, ...power} = this.roleForm
            await editRole({role:{role_id: this.curId, role_name, en_role_name}, ...power})
          } else {
            await addRole(this.roleForm)
          }
          this.$message({message: this.$t('common.success'), type: 'success'})
          this.showForm = false
          this.getList('Role')
        } catch (error) {
          
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
