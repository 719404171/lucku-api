<template>
  <div class="app-container">
    <div class="mb20 df" v-if="device==='desktop'">
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true">{{$t('common.add')}}</el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('User')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-upload class="mr10" action="#" :show-file-list="false" :http-request="uploadQr">
        <el-button type="primary" icon="el-icon-camera">{{$t('common.uploadQr')}}</el-button>
      </el-upload>
      <el-button type="primary" icon="el-icon-check" plain @click="verifyAll">一键通过验证</el-button>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'User')"></el-button>
    </div>
    <div class="mb20 df" v-else>
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true"></el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('User')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-upload class="mr10" action="#" :show-file-list="false" :http-request="uploadQr">
        <el-button type="primary" icon="el-icon-camera"></el-button>
      </el-upload>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'User')"></el-button>
    </div>
    <canvas ref="canvas" style="display:none"></canvas>

    <!--  -->
    <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :columnAttr="columnAttr" :rowControl="['edit', 'password', 'del']" @row-control-click="onRcc" @page-change="onPageChange('User', $event)" @selection-change="delIds=$event.map(({user_id})=>user_id)" @sort-change="onSortChange">
      <template #control="{scope}">
        <!-- 自定义按钮：未验证且有邮箱才显示 -->
        <el-button
          v-if="scope.row.user_status === 0 && scope.row.user_email"
          type="text"
          size="small"
          @click="verifyEmail(scope.row)"
        >通过验证</el-button>
      </template>
      <template #user_avatar="{row}"><el-avatar shape="square" v-bind="row.user_avatar ? {src: row.user_avatar} : {icon: 'el-icon-user-solid'}"></el-avatar></template>
      <template #user_sex="{row}">{{$t('user.sex'+row.user_sex)}}</template>
      <template #user_phone="{row}">{{row.user_phone || '—'}}</template>
      <template #user_email="{row}">
        <span v-if="row.user_email">{{row.user_email}}</span>
        <span v-else style="color:#ccc">—</span>
      </template>
      <template #user_status="{row}">
        <el-tag v-if="row.user_status === 1" type="success">已通过</el-tag>
        <el-tag v-else-if="row.user_status === 0" type="warning">未验证</el-tag>
        <el-tag v-else-if="row.user_status === -1" type="danger">已删除</el-tag>
        <el-tag v-else type="danger">封禁</el-tag>
      </template>
      <template #login_type="{row}">
        <el-tag v-if="row.login_type === 1" size="small" type="info">📱 手机号</el-tag>
        <el-tag v-else-if="row.login_type === 2" size="small">🍎 Apple</el-tag>
        <el-tag v-else-if="row.login_type === 3" size="small">🔍 Google</el-tag>
        <el-tag v-else-if="row.login_type === 4" size="small" type="success">📧 邮箱注册</el-tag>
        <el-tag v-else size="small" type="info">—</el-tag>
      </template>
      <template #created_at="{row}">{{row.created_at || '—'}}</template>
      <template #login_time="{row}">{{row.login_time || 0}}</template>
      <template #finally_at="{row}">{{row.finally_at || '—'}}</template>
      <template #last_login_ip="{row}">{{row.last_login_ip || '—'}}</template>
      <template #is_online="{row}"><el-tag :type="row.is_online==1 ? 'success' : 'info'">{{$t(row.is_online==1 ? 'user.online' : 'user.offline')}}</el-tag></template>
    </ProTable>

    <!--  -->
    <el-dialog :title="$t(`user.${this.echo.user_id ? 'editUser' : 'addUser'}`)" :visible.sync="show" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules"></ProForm>
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>

    <!--  -->
    <el-dialog :title="$t('user.updatePsd')+`（${psd.user_name}）`" :visible.sync="showPsd" :width="device==='desktop' ? '500px' : '100%'" :close-on-click-modal="false" @closed="resetPsd">
      <el-input type="password" :placeholder="$t('common.enter')" v-model="psd.user_password" show-password></el-input>
      <span slot="footer" class="dialog-footer">
        <el-button @click="showPsd = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadPsd" @click="subPsd">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import jsQR from 'jsqr';
import { validEmail } from '@/utils/validate'
import { addUser, editUser, delUser, editUserPsd, verifyUserEmail, verifyAllUserEmail } from '@/api';
import { user } from '@/form';
import paging from '@/mixins/paging'
export default {
  name: 'UserList',
  mixins: [paging],
  data() {
    return {
      SFC: user.SFC,
      EFC: user.EFC,
      show: false,
      delIds: [],
      column: {
        user_avatar: 'common.avatar',
        user_name: 'user.name',
        user_sex: 'user.sex',
        user_phone: 'user.phone',
        user_email: 'common.email',
        login_type: 'user.register_type',
        user_status: 'user.verify_status',
        created_at: 'user.register_time',
        login_time: 'user.login_time',
        finally_at: 'user.finally_at',
        last_login_ip: 'log.ip',
        is_online: 'user.online_status'
      },
      columnAttr: {
        login_time: { sortable: 'custom' },
        finally_at: { sortable: 'custom' },
      },
      echo: {},
      isLoadSub: false,
      showPsd: false,
      psd: {
        user_id: '',
        user_name: '',
        user_password: ''
      },
      isLoadPsd: false,
      rules: {
        user_name: [
          {required: true,message: this.$t('common.required'),trigger: "blur"},
          {min: 3,max: 16,message: this.$t('login.unErr'),trigger: 'blur'}
        ],
        user_password: [
          {required: true,message: this.$t('common.required'),trigger: "blur"},
          {min: 5,max: 18,message: this.$t('login.psdErr'),trigger: 'blur'}
        ],
        phone: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        user_email: [
          {required: true,message: this.$t('common.required'),trigger: "blur"},
          {trigger: "blur",validator: (rule, value, callback) => validEmail(value) ? callback() : callback(new Error(this.$t('common.emailErr')))}
        ]
      }
    }
  },
  created() {
    this.getList('User')
  },
  methods: {
    onSortChange({ prop, order }) {
      this.getList('User', { sort: prop, order: order || 'desc' })
    },
    decodeQR(imageData) {
      const code = jsQR(imageData.data, imageData.width, imageData.height);
      if (code) {
          // console.log('QR Code detected:', code.data);
          this.onSearch('User', {multi: code.data})
      } else {
          this.$message({message: this.$t('common.qrErr'),type: 'warning'})
      }
    },
    uploadQr({file}) {
      const img = new Image();
      const ctx = this.$refs.canvas.getContext('2d');
      img.onload = () => {
          this.$refs.canvas.width = img.width;
          this.$refs.canvas.height = img.height;
          ctx.drawImage(img, 0, 0);
          const imageData = ctx.getImageData(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
          this.decodeQR(imageData);
      };
      img.src = URL.createObjectURL(file);
    },
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
      this.$set(this.EFC.formItems[1].item.attr, 'disabled', false)
      this.$set(this.EFC.formItems[4].item.attr, 'disabled', false)
      const psdIndex = this.rules.user_password.findIndex(item => item.required)
      if (psdIndex<0) {
        this.rules.user_password.push({required: true,message: this.$t('common.required'),trigger: "blur"})
      }
    },
    // 更改密码
    async password({user_id, user_name}) {
      Object.assign(this.psd, {user_id, user_name})
      this.showPsd = true
    },
    // 一鍵通過所有未驗證用戶
    verifyAll() {
      this.$confirm('确定要通过所有未验证且已绑定邮箱的用户吗？', '批量通过验证', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        try {
          const res = await verifyAllUserEmail()
          this.$message({ message: res.msg || '批量通过成功', type: 'success' })
          this.getList('User')
        } catch (error) {}
      }).catch(() => {})
    },
    // 單個：通過驗證
    verifyEmail({user_id, user_name, user_email}) {
      this.$confirm(`确定要为用户「${user_name}（${user_email}）」通过验证吗？`, '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(async () => {
        try {
          await verifyUserEmail({ user_id })
          this.$message({ message: '已通过验证', type: 'success' })
          this.getList('User')
        } catch (error) {}
      }).catch(() => {})
    },
    resetPsd() {
      this.psd = {user_id: '', user_name: '',user_password: ''}
    },
    async subPsd() {
      if (!this.psd.user_password) return this.$message({message: this.$t('user.enterPsd'), type: 'warning'})
      try {
        this.isLoadPsd = true
        const {user_name, ...params} = this.psd
        await editUserPsd(params)
        this.$message({message: this.$t('common.success'),type: 'success'})
        this.showPsd = false
      } catch (error) {

      } finally {
        this.isLoadPsd = false
      }
    },
    // 编辑
    edit({user_id, user_name, area_code, user_phone, user_email, user_coin, user_integral, user_balance, user_status,user_sex,login_time}) {
      const psdIndex = this.rules.user_password.findIndex(item => item.required)
      if (psdIndex>-1) {
        this.rules.user_password.splice(psdIndex, 1)
      }
      this.$set(this.EFC.formItems[1].item.attr, 'disabled', true)
      this.$set(this.EFC.formItems[4].item.attr, 'disabled', false)
      const phone = `${area_code},${user_phone || ''}`
      this.echo = {user_id, user_name, phone, user_email, user_coin, user_integral, user_balance, user_status,user_sex,login_time}
      this.show = true
    },
    // 删除
    async del({user_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delUser({user_id: user_id ? [user_id] : this.delIds})
          if (user_id) {
            this.list = this.list.filter(item => item.user_id !== user_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.user_id))
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
      this.$refs.editForm.submit(async ({phone, user_password, ...data}) => {
        const [area_code, user_phone] = phone.split(',')
        try {
          this.isLoadSub = true
          if (this.echo.user_id) {
            await editUser(Object.assign({admin_id: this.$store.state.user.detail.admin_id, area_code, user_phone}, data))
          } else {
            await addUser({user_id: '', area_code, user_phone, user_password, ...data})
          }
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.show = false
          this.getList('User')
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

<style lang="scss" scoped>
.ban-txt {
  color: #f56c6c;
  line-height: 16px;
  margin-top: 4px;
}
</style>
