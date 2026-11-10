<template>
  <div class="navbar">
    <hamburger :is-active="sidebar.opened" class="hamburger-container" @toggleClick="toggleSideBar" />

    <breadcrumb class="breadcrumb-container" />

    <div class="right-menu">
      <template v-if="device!=='mobile'">

        <div class="right-menu-item hover-effect"><i class="el-icon-refresh fs20" @click="refresh"></i></div>

        <el-tooltip :content="$t('navbar.size')" effect="dark" placement="bottom">
          <size-select id="size-select" class="right-menu-item hover-effect" />
        </el-tooltip>

        <lang-select class="right-menu-item hover-effect" />
        
        <div class="right-menu-item hover-effect"><theme-picker style="margin-top:10px" @change="themeChange" /></div>

      </template>

      <el-dropdown class="avatar-container" trigger="click">
        <div class="avatar-wrapper">
          <!-- <img :src="avatar+'?imageView2/1/w/80/h/80'" class="user-avatar"> -->
          <el-avatar :size="40" shape="square" v-bind="avatar ? {src:avatar} : {icon: 'el-icon-user-solid'}"></el-avatar>
          <i class="el-icon-caret-bottom" />
        </div>
        <el-dropdown-menu slot="dropdown">
          <!-- <router-link to="/system/personal">
            <el-dropdown-item>
              {{ $t('navbar.profile') }}
            </el-dropdown-item>
          </router-link> -->
          <router-link to="/">
            <el-dropdown-item>
              {{ $t('navbar.dashboard') }}
            </el-dropdown-item>
          </router-link>
          <el-dropdown-item divided @click.native="pwdDialogVisible = true">
            <span style="display:block;">{{ $t('navbar.changePassword') }}</span>
          </el-dropdown-item>
          <el-dropdown-item divided @click.native="logout">
            <span style="display:block;">{{ $t('navbar.logOut') }}</span>
          </el-dropdown-item>
        </el-dropdown-menu>
      </el-dropdown>
    </div>

    <el-dialog :title="$t('navbar.changePassword')" :visible.sync="pwdDialogVisible" width="420px" append-to-body @closed="resetPwdForm">
      <el-form ref="pwdForm" :model="pwdForm" :rules="pwdRules" label-width="90px">
        <el-form-item :label="$t('navbar.oldPassword')" prop="old_password">
          <el-input v-model="pwdForm.old_password" type="password" show-password autocomplete="off" />
        </el-form-item>
        <el-form-item :label="$t('navbar.newPassword')" prop="new_password">
          <el-input v-model="pwdForm.new_password" type="password" show-password autocomplete="off" />
        </el-form-item>
        <el-form-item :label="$t('navbar.confirmPassword')" prop="confirm_password">
          <el-input v-model="pwdForm.confirm_password" type="password" show-password autocomplete="off" />
        </el-form-item>
      </el-form>
      <div slot="footer">
        <el-button @click="pwdDialogVisible = false">{{ $t('navbar.cancel') }}</el-button>
        <el-button type="primary" :loading="pwdSubmitting" @click="submitPwd">{{ $t('navbar.confirm') }}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import Breadcrumb from '@/components/Breadcrumb'
import Hamburger from '@/components/Hamburger'
import SizeSelect from '@/components/SizeSelect'
import LangSelect from '@/components/LangSelect'
import ThemePicker from '@/components/ThemePicker'
import { editAdminPassword } from '@/api'

export default {
  components: {
    Breadcrumb,
    Hamburger,
    SizeSelect,
    LangSelect,
    ThemePicker
  },
  data() {
    const validateConfirm = (rule, value, callback) => {
      if (value !== this.pwdForm.new_password) {
        callback(new Error(this.$t('navbar.passwordNotMatch')))
      } else {
        callback()
      }
    }
    return {
      pwdDialogVisible: false,
      pwdSubmitting: false,
      pwdForm: {
        old_password: '',
        new_password: '',
        confirm_password: ''
      },
      pwdRules: {
        old_password: [{ required: true, message: this.$t('navbar.inputOldPassword'), trigger: 'blur' }],
        new_password: [
          { required: true, message: this.$t('navbar.inputNewPassword'), trigger: 'blur' },
          { min: 6, message: this.$t('navbar.passwordTooShort'), trigger: 'blur' }
        ],
        confirm_password: [
          { required: true, message: this.$t('navbar.inputConfirmPassword'), trigger: 'blur' },
          { validator: validateConfirm, trigger: 'blur' }
        ]
      }
    }
  },
  computed: {
    ...mapGetters([
      'sidebar',
      'avatar',
      'device'
    ])
  },
  methods: {
    resetPwdForm() {
      this.pwdForm = { old_password: '', new_password: '', confirm_password: '' }
      this.$refs.pwdForm && this.$refs.pwdForm.clearValidate()
    },
    submitPwd() {
      this.$refs.pwdForm.validate(async valid => {
        if (!valid) return
        this.pwdSubmitting = true
        try {
          const res = await editAdminPassword({
            old_password: this.pwdForm.old_password,
            new_password: this.pwdForm.new_password
          })
          if (res.status === 200) {
            this.$message.success(res.msg)
            this.pwdDialogVisible = false
            // 密碼已修改，自動退出並跳轉登錄頁重新登錄
            await this.$store.dispatch('user/logout')
            this.$router.push(`/login?redirect=${this.$route.fullPath}`)
          }
        } catch (e) {
          // 錯誤提示已由 request 攔截器統一彈出
        } finally {
          this.pwdSubmitting = false
        }
      })
    },
    refresh() {
      this.$store.dispatch('tagsView/delCachedView', this.$route).then(() => {
        const { fullPath } = this.$route
        this.$nextTick(() => {
          this.$router.replace({
            path: '/redirect' + fullPath
          })
        })
      })
    },
    themeChange(val) {
      this.$store.dispatch('settings/changeSetting', {
        key: 'theme',
        value: val
      })
    },
    toggleSideBar() {
      this.$store.dispatch('app/toggleSideBar')
    },
    async logout() {
      await this.$store.dispatch('user/logout')
      this.$router.push(`/login?redirect=${this.$route.fullPath}`)
    }
  }
}
</script>

<style lang="scss" scoped>
.navbar {
  height: 50px;
  overflow: hidden;
  position: relative;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0,21,41,.08);

  .hamburger-container {
    line-height: 46px;
    height: 100%;
    float: left;
    cursor: pointer;
    transition: background .3s;
    -webkit-tap-highlight-color:transparent;

    &:hover {
      background: rgba(0, 0, 0, .025)
    }
  }

  .breadcrumb-container {
    float: left;
  }

  .right-menu {
    float: right;
    height: 100%;
    line-height: 50px;

    &:focus {
      outline: none;
    }

    .right-menu-item {
      display: inline-block;
      padding: 0 8px;
      height: 100%;
      font-size: 18px;
      color: #5a5e66;
      vertical-align: text-bottom;

      &.hover-effect {
        cursor: pointer;
        transition: background .3s;

        &:hover {
          background: rgba(0, 0, 0, .025)
        }
      }
    }

    .avatar-container {
      margin-right: 30px;

      .avatar-wrapper {
        margin-top: 5px;
        position: relative;

        .user-avatar {
          cursor: pointer;
          width: 40px;
          height: 40px;
          border-radius: 10px;
        }

        .el-icon-caret-bottom {
          cursor: pointer;
          position: absolute;
          right: -20px;
          top: 25px;
          font-size: 12px;
        }
      }
    }
  }
}
</style>
