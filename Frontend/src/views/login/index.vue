<template>
  <div class="login-container" :style="{'background-image': `url(${bgm})`}">
    <!-- 第一步：帳號密碼 -->
    <el-form v-if="step === 1" ref="loginForm" :model="loginForm" :rules="loginRules" class="login-form" auto-complete="on" label-position="left">

      <div class="logo-container"><img alt="logo" :src="logo"></div>

      <div class="title-container">
        <h3 class="title">{{title}}</h3>
        <lang-select class="set-language" />
      </div>

      <el-form-item prop="username">
        <span class="svg-container">
          <svg-icon icon-class="user" />
        </span>
        <el-input
          ref="username"
          v-model="loginForm.username"
          :placeholder="$t('login.username')"
          name="username"
          type="text"
          tabindex="1"
          auto-complete="on"
        />
      </el-form-item>

      <el-form-item prop="password">
        <span class="svg-container">
          <svg-icon icon-class="password" />
        </span>
        <el-input
          :key="passwordType"
          ref="password"
          v-model="loginForm.password"
          :type="passwordType"
          :placeholder="$t('login.password')"
          name="password"
          tabindex="2"
          auto-complete="on"
          @keyup.enter.native="handleLogin"
        />
        <span class="show-pwd" @click="showPwd">
          <svg-icon :icon-class="passwordType === 'password' ? 'eye' : 'eye-open'" />
        </span>
      </el-form-item>

      <el-button :loading="loading" type="primary" style="width:100%;margin-bottom:30px;" @click.native.prevent="handleLogin">{{$t('login.logIn')}}</el-button>

    </el-form>

    <!-- 第二步：安全驗證（短信 / 郵箱 / 安全問題可同時開啟） -->
    <div v-else class="login-form verify-form">
      <div class="logo-container"><img alt="logo" :src="logo"></div>

      <div class="verify-head">
        <h3 class="verify-title">安全驗證</h3>
        <p class="verify-sub">
          <span>帳號：{{ maskedUsername }}</span>
          <span class="verify-step">第 2 步 / 共 2 步</span>
        </p>
      </div>

      <!-- 短信驗證 -->
      <div v-if="hasSms" class="verify-block">
        <div class="verify-label">
          <i class="el-icon-mobile-phone" />
          <span>短信驗證</span>
          <em class="verify-req">必填</em>
        </div>
        <div class="verify-desc">驗證碼將發送至綁定手機 {{ challenge.phone_mask || '-' }}</div>
        <el-input
          v-model="verifyForm.sms_code"
          placeholder="請輸入 6 位短信驗證碼"
          maxlength="6"
          class="verify-input"
        >
          <el-button
            slot="append"
            class="code-btn"
            :loading="smsSending"
            :disabled="smsCountdown > 0 || smsSending"
            @click.native.prevent="handleSendCode('sms')"
          >{{ smsCountdown > 0 ? smsCountdown + 's' : '獲取驗證碼' }}</el-button>
        </el-input>
        <div v-if="smsTip" class="verify-tip">{{ smsTip }}</div>
      </div>

      <!-- 郵箱驗證 -->
      <div v-if="hasEmail" class="verify-block">
        <div class="verify-label">
          <i class="el-icon-message" />
          <span>郵箱驗證</span>
          <em class="verify-req">必填</em>
        </div>
        <div class="verify-desc">驗證碼將發送至綁定郵箱 {{ challenge.email_mask || '-' }}</div>
        <el-input
          v-model="verifyForm.email_code"
          placeholder="請輸入 6 位郵箱驗證碼"
          maxlength="6"
          class="verify-input"
        >
          <el-button
            slot="append"
            class="code-btn"
            :loading="emailSending"
            :disabled="emailCountdown > 0 || emailSending"
            @click.native.prevent="handleSendCode('email')"
          >{{ emailCountdown > 0 ? emailCountdown + 's' : '獲取驗證碼' }}</el-button>
        </el-input>
        <div v-if="emailTip" class="verify-tip">{{ emailTip }}</div>
      </div>

      <!-- 安全問題 -->
      <div v-if="hasQuestion" class="verify-block">
        <div class="verify-label">
          <i class="el-icon-question" />
          <span>安全問題</span>
          <em class="verify-req">全部答對</em>
        </div>
        <div class="verify-desc">請回答以下全部安全問題</div>
        <div v-for="(q, idx) in (challenge.questions || [])" :key="q.id" class="question-item">
          <div class="question-text">{{ idx + 1 }}. {{ q.question }}</div>
          <el-input
            v-model="verifyForm.answers[q.id]"
            placeholder="請輸入答案"
            class="verify-input"
          />
        </div>
      </div>

      <el-button :loading="verifying" type="primary" class="verify-submit" @click.native.prevent="handleVerify">完成驗證並登錄</el-button>
      <div class="verify-back" @click="backToLogin">返回重新輸入帳號密碼</div>
    </div>
  </div>
</template>

<script>
import { validUsername } from '@/utils/validate'
import { sendVerifyCode, verifyLogin } from '@/api/user'
import LangSelect from '@/components/LangSelect'
import { mapGetters } from 'vuex'

export default {
  name: 'Login',
  components: { LangSelect },
  data() {
    const validateUsername = (rule, value, callback) => {
      if (!validUsername(value)) {
        callback(new Error('Please enter the correct user name'))
      } else {
        callback()
      }
    }
    const validatePassword = (rule, value, callback) => {
      if (value.length < 5) {
        callback(new Error('The password can not be less than 5 digits'))
      } else {
        callback()
      }
    }
    return {
      bgm: process.env.VUE_APP_LOGIN_BG,
      step: 1,
      loginForm: {
        username: '',
        password: ''
      },
      loginRules: {
        username: [{ required: true, trigger: 'blur', validator: validateUsername }],
        password: [{ required: true, trigger: 'blur', validator: validatePassword }]
      },
      // 第二階段
      challenge: {},
      verifyForm: {
        sms_code: '',
        email_code: '',
        answers: {}
      },
      smsCountdown: 0,
      emailCountdown: 0,
      smsSending: false,
      emailSending: false,
      smsTip: '',
      emailTip: '',
      smsTimer: null,
      emailTimer: null,
      verifying: false,
      loading: false,
      passwordType: 'password',
      redirect: undefined
    }
  },
  computed: {
    ...mapGetters(['logo', 'title']),
    hasSms() {
      return (this.challenge.methods || []).indexOf('sms') > -1
    },
    hasEmail() {
      return (this.challenge.methods || []).indexOf('email') > -1
    },
    hasQuestion() {
      return (this.challenge.methods || []).indexOf('question') > -1
    },
    maskedUsername() {
      return this.loginForm.username
    }
  },
  beforeDestroy() {
    this.clearTimer('sms')
    this.clearTimer('email')
  },
  watch: {
    $route: {
      handler: function(route) {
        this.redirect = route.query && route.query.redirect
      },
      immediate: true
    }
  },
  methods: {
    showPwd() {
      if (this.passwordType === 'password') {
        this.passwordType = ''
      } else {
        this.passwordType = 'password'
      }
      this.$nextTick(() => {
        this.$refs.password.focus()
      })
    },
    handleLogin() {
      this.$refs.loginForm.validate(valid => {
        if (!valid) {
          return false
        }
        this.loading = true
        this.$store.dispatch('user/login', this.loginForm).then(result => {
          this.loading = false
          if (result && result.need_verify) {
            this.challenge = result
            this.verifyForm.sms_code = ''
            this.verifyForm.email_code = ''
            // 預先建立答案鍵，保證 Vue2 對動態 key 的響應式
            const answers = {}
            ;(result.questions || []).forEach(q => {
              answers[q.id] = ''
            })
            this.verifyForm.answers = answers
            this.smsTip = ''
            this.emailTip = ''
            this.step = 2
            return
          }
          this.$router.push({ path: this.redirect || '/' })
        }).catch(() => {
          this.loading = false
        })
      })
    },
    handleSendCode(channel) {
      if (channel === 'sms' && (this.smsCountdown > 0 || this.smsSending)) return
      if (channel === 'email' && (this.emailCountdown > 0 || this.emailSending)) return
      if (channel === 'sms') this.smsSending = true
      else this.emailSending = true
      sendVerifyCode({
        challenge_token: this.challenge.challenge_token,
        channel: channel
      }).then(response => {
        const result = response.results || {}
        const target = result.target || ''
        if (channel === 'sms') {
          this.smsTip = target ? `驗證碼已發送至 ${target}` : '驗證碼已發送'
          this.startCountdown('sms', 60)
        } else {
          this.emailTip = target ? `驗證碼已發送至 ${target}` : '驗證碼已發送'
          this.startCountdown('email', 60)
        }
      }).catch(() => {}).finally(() => {
        if (channel === 'sms') this.smsSending = false
        else this.emailSending = false
      })
    },
    startCountdown(channel, seconds) {
      this.clearTimer(channel)
      if (channel === 'sms') this.smsCountdown = seconds
      else this.emailCountdown = seconds
      const timer = setInterval(() => {
        if (channel === 'sms') {
          this.smsCountdown -= 1
          if (this.smsCountdown <= 0) this.clearTimer('sms')
        } else {
          this.emailCountdown -= 1
          if (this.emailCountdown <= 0) this.clearTimer('email')
        }
      }, 1000)
      if (channel === 'sms') this.smsTimer = timer
      else this.emailTimer = timer
    },
    clearTimer(channel) {
      const key = channel === 'sms' ? 'smsTimer' : 'emailTimer'
      if (this[key]) {
        clearInterval(this[key])
        this[key] = null
      }
      if (channel === 'sms') this.smsCountdown = 0
      else this.emailCountdown = 0
    },
    handleVerify() {
      if (this.hasSms && !/^\d{6}$/.test(this.verifyForm.sms_code || '')) {
        this.$message.warning('請輸入 6 位短信驗證碼')
        return
      }
      if (this.hasEmail && !/^\d{6}$/.test(this.verifyForm.email_code || '')) {
        this.$message.warning('請輸入 6 位郵箱驗證碼')
        return
      }
      if (this.hasQuestion) {
        const questions = this.challenge.questions || []
        for (let i = 0; i < questions.length; i++) {
          const val = (this.verifyForm.answers[questions[i].id] || '').trim()
          if (!val) {
            this.$message.warning('請回答所有安全問題')
            return
          }
        }
      }

      const data = { challenge_token: this.challenge.challenge_token }
      if (this.hasSms) data.sms_code = this.verifyForm.sms_code
      if (this.hasEmail) data.email_code = this.verifyForm.email_code
      if (this.hasQuestion) {
        const answers = []
        ;(this.challenge.questions || []).forEach(q => {
          answers.push({ id: q.id, answer: (this.verifyForm.answers[q.id] || '').trim() })
        })
        data.answers = answers
      }

      this.verifying = true
      verifyLogin(data).then(response => {
        const token = (response.results || {}).token
        if (!token) {
          this.verifying = false
          return
        }
        this.$store.dispatch('user/loginByToken', token).then(() => {
          this.verifying = false
          this.$router.push({ path: this.redirect || '/' })
        })
      }).catch(() => {
        this.verifying = false
      })
    },
    backToLogin() {
      this.step = 1
      this.challenge = {}
      this.verifyForm.sms_code = ''
      this.verifyForm.email_code = ''
      this.verifyForm.answers = {}
      this.clearTimer('sms')
      this.clearTimer('email')
      this.smsTip = ''
      this.emailTip = ''
    }
  }
}
</script>

<style lang="scss">
/* 修复input 背景不协调 和光标变色 */
$bg:#283443;
$light_gray:#fff;
$cursor: #fff;

@supports (-webkit-mask: none) and (not (cater-color: $cursor)) {
  .login-container .el-input input {
    color: $cursor;
  }
}

/* reset element-ui css */
.login-container {
  .el-input {
    display: inline-block;
    height: 47px;
    width: 85%;

    input {
      background: transparent;
      border: 0px;
      -webkit-appearance: none;
      border-radius: 0px;
      padding: 12px 5px 12px 15px;
      color: $light_gray;
      height: 47px;
      caret-color: $cursor;

      &:-webkit-autofill {
        box-shadow: 0 0 0px 1000px $bg inset !important;
        -webkit-text-fill-color: $cursor !important;
      }
    }
  }

  .el-form-item {
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.1);
    border-radius: 5px;
    color: #454545;
  }
}
</style>

<style lang="scss" scoped>
$bg:#2d3a4b;
$dark_gray:#889aa4;
$light_gray:#eee;

.login-container {
  min-height: 100%;
  width: 100%;
  background-color: $bg;
  background-repeat: no-repeat;
  background-position: center;
  background-size: cover;
  overflow: hidden;

  .login-form {
    position: relative;
    width: 520px;
    max-width: 100%;
    padding: 160px 35px 0;
    margin: 0 auto;
    overflow: hidden;
  }

  .svg-container {
    padding: 6px 5px 6px 15px;
    color: $dark_gray;
    vertical-align: middle;
    width: 30px;
    display: inline-block;
  }

  .logo-container {
    display: flex;
    justify-content: center;
    margin-bottom: 40px;
    img {
      width: 80px;
      height: 80px;
      padding: 10px;
      background-color: #fff;
      border-radius: 8px;
    }
  }

  .title-container {
    position: relative;

    .title {
      font-size: 26px;
      color: $light_gray;
      margin: 0px auto 40px auto;
      padding-right: 50px;
      text-align: center;
      font-weight: bold;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }

    .set-language {
      color: #fff;
      position: absolute;
      top: 3px;
      font-size: 18px;
      right: 0px;
      cursor: pointer;
    }
  }

  .show-pwd {
    position: absolute;
    right: 10px;
    top: 7px;
    font-size: 16px;
    color: $dark_gray;
    cursor: pointer;
    user-select: none;
  }

  /* ===== 第二步：安全驗證 ===== */
  .verify-form {
    padding-top: 120px;
    padding-bottom: 40px;

    .verify-head {
      text-align: center;
      margin-bottom: 24px;

      .verify-title {
        font-size: 24px;
        color: $light_gray;
        margin: 0 0 10px;
        font-weight: bold;
      }

      .verify-sub {
        margin: 0;
        font-size: 13px;
        color: $dark_gray;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 6px;

        .verify-step {
          color: #409eff;
        }
      }
    }

    .verify-block {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 6px;
      padding: 14px 16px 16px;
      margin-bottom: 16px;

      .verify-label {
        display: flex;
        align-items: center;
        color: $light_gray;
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 6px;

        i {
          margin-right: 8px;
          color: #409eff;
          font-size: 17px;
        }

        .verify-req {
          margin-left: auto;
          font-style: normal;
          font-size: 12px;
          font-weight: normal;
          color: #e6a23c;
          border: 1px solid rgba(230, 162, 60, 0.6);
          border-radius: 3px;
          padding: 0 6px;
          line-height: 18px;
        }
      }

      .verify-desc {
        font-size: 12px;
        color: $dark_gray;
        margin-bottom: 10px;
      }

      .verify-input {
        width: 100% !important;

        ::v-deep .el-input-group__append {
          background: transparent;
          border: 1px solid rgba(255, 255, 255, 0.15);
          border-left: 0;
          color: #409eff;
          cursor: pointer;
        }
      }

      .verify-tip {
        margin-top: 8px;
        font-size: 12px;
        color: #67c23a;
      }

      .question-item {
        margin-bottom: 12px;

        &:last-child {
          margin-bottom: 0;
        }

        .question-text {
          color: $light_gray;
          font-size: 13px;
          margin-bottom: 6px;
          line-height: 1.5;
        }
      }
    }

    .verify-submit {
      width: 100%;
      margin-top: 8px;
    }

    .verify-back {
      text-align: center;
      margin-top: 14px;
      font-size: 13px;
      color: $dark_gray;
      cursor: pointer;

      &:hover {
        color: #409eff;
      }
    }
  }
}
</style>
