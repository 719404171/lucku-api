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
      <div class="verify-steps">
        <span class="vs-item is-done"><i class="el-icon-check" />帳號密碼</span>
        <span class="vs-line" />
        <span class="vs-item is-active">安全驗證</span>
      </div>

      <div class="verify-head">
        <div class="logo-container"><img alt="logo" :src="logo"></div>
        <h3 class="verify-title">安全驗證</h3>
        <p class="verify-sub">當前帳號 <b>{{ maskedUsername }}</b>，請完成以下驗證</p>
      </div>

      <div class="verify-body">
        <!-- 短信驗證 -->
        <div v-if="hasSms" class="verify-block is-sms">
          <div class="verify-block-head">
            <span class="verify-ico is-sms"><i class="el-icon-mobile-phone" /></span>
            <div class="verify-block-title">
              <div class="verify-label">短信驗證<em class="verify-req">必填</em></div>
              <div class="verify-desc">驗證碼將發送至綁定手機 {{ challenge.phone_mask || '-' }}</div>
            </div>
          </div>
          <div class="code-row">
            <el-input
              v-model="verifyForm.sms_code"
              placeholder="請輸入 6 位短信驗證碼"
              maxlength="6"
              class="verify-input"
              @keyup.enter.native="handleVerify"
            />
            <el-button
              class="code-btn"
              :loading="smsSending"
              :disabled="smsCountdown > 0 || smsSending"
              @click.native.prevent="handleSendCode('sms')"
            >{{ smsCountdown > 0 ? smsCountdown + 's' : '獲取驗證碼' }}</el-button>
          </div>
          <div v-if="smsTip" class="verify-tip"><i class="el-icon-success" />{{ smsTip }}</div>
        </div>

        <!-- 郵箱驗證 -->
        <div v-if="hasEmail" class="verify-block is-email">
          <div class="verify-block-head">
            <span class="verify-ico is-email"><i class="el-icon-message" /></span>
            <div class="verify-block-title">
              <div class="verify-label">郵箱驗證<em class="verify-req">必填</em></div>
              <div class="verify-desc">驗證碼將發送至綁定郵箱 {{ challenge.email_mask || '-' }}</div>
            </div>
          </div>
          <div class="code-row">
            <el-input
              v-model="verifyForm.email_code"
              placeholder="請輸入 6 位郵箱驗證碼"
              maxlength="6"
              class="verify-input"
              @keyup.enter.native="handleVerify"
            />
            <el-button
              class="code-btn"
              :loading="emailSending"
              :disabled="emailCountdown > 0 || emailSending"
              @click.native.prevent="handleSendCode('email')"
            >{{ emailCountdown > 0 ? emailCountdown + 's' : '獲取驗證碼' }}</el-button>
          </div>
          <div class="verify-hint"><i class="el-icon-info" />驗證碼 10 分鐘內有效，若未收到請檢查垃圾郵件</div>
          <div v-if="emailTip" class="verify-tip"><i class="el-icon-success" />{{ emailTip }}</div>
        </div>

        <!-- 安全問題 -->
        <div v-if="hasQuestion" class="verify-block is-question">
          <div class="verify-block-head">
            <span class="verify-ico is-question"><i class="el-icon-question" /></span>
            <div class="verify-block-title">
              <div class="verify-label">安全問題<em class="verify-req">隨機一題</em></div>
              <div class="verify-desc">系統已隨機抽取一題，答對即可通過</div>
            </div>
          </div>
          <div v-for="(q, idx) in (challenge.questions || [])" :key="q.id" class="question-item">
            <div class="question-text">{{ idx + 1 }}. {{ q.question }}</div>
            <el-input
              v-model="verifyForm.answers[q.id]"
              placeholder="請輸入該問題的答案"
              class="verify-input"
              @keyup.enter.native="handleVerify"
            />
          </div>
        </div>
      </div>

      <div class="verify-actions">
        <el-button :loading="verifying" type="primary" class="verify-submit" @click.native.prevent="handleVerify">驗證並登錄</el-button>
        <div class="verify-back" @click="backToLogin"><i class="el-icon-back" />返回重新輸入帳號密碼</div>
      </div>
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
            this.$message.warning('請回答安全問題')
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
    width: 460px;
    padding: 28px 32px 26px;
    margin: 5vh auto 40px;
    max-height: calc(100vh - 10vh);
    overflow-y: auto;
    background: rgba(18, 27, 40, 0.88);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(8px);

    &::-webkit-scrollbar {
      width: 6px;
    }

    &::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.18);
      border-radius: 3px;
    }

    .verify-steps {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      font-size: 12px;

      .vs-item {
        display: inline-flex;
        align-items: center;
        color: $dark_gray;

        &.is-done {
          color: #67c23a;

          i {
            margin-right: 4px;
            font-size: 13px;
          }
        }

        &.is-active {
          color: #fff;
          font-weight: 600;

          &::before {
            content: '';
            width: 6px;
            height: 6px;
            margin-right: 6px;
            border-radius: 50%;
            background: #409eff;
            box-shadow: 0 0 0 3px rgba(64, 158, 255, 0.18);
          }
        }
      }

      .vs-line {
        width: 46px;
        height: 1px;
        margin: 0 12px;
        background: linear-gradient(90deg, rgba(103, 194, 58, 0.6), rgba(64, 158, 255, 0.6));
      }
    }

    .verify-head {
      text-align: center;
      margin-bottom: 22px;

      .logo-container {
        margin-bottom: 14px;

        img {
          width: 54px;
          height: 54px;
          padding: 7px;
          border-radius: 12px;
        }
      }

      .verify-title {
        font-size: 22px;
        color: $light_gray;
        margin: 0 0 6px;
        font-weight: 600;
      }

      .verify-sub {
        margin: 0;
        font-size: 13px;
        color: $dark_gray;
        line-height: 1.6;

        b {
          color: #d7e1ec;
          font-weight: 600;
        }
      }
    }

    .verify-body {
      margin-bottom: 20px;
    }

    .verify-block {
      background: rgba(255, 255, 255, 0.045);
      border: 1px solid rgba(255, 255, 255, 0.09);
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 12px;
      transition: background 0.2s, border-color 0.2s;

      &:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.16);
      }

      &.is-sms {
        border-left: 3px solid rgba(64, 158, 255, 0.85);
      }

      &.is-email {
        border-left: 3px solid rgba(103, 194, 58, 0.85);
      }

      &.is-question {
        border-left: 3px solid rgba(230, 162, 60, 0.85);
      }

      &:last-child {
        margin-bottom: 0;
      }

      .verify-block-head {
        display: flex;
        align-items: flex-start;
        margin-bottom: 12px;
      }

      .verify-ico {
        flex: none;
        width: 32px;
        height: 32px;
        margin-right: 10px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;

        &.is-sms {
          color: #409eff;
          background: rgba(64, 158, 255, 0.12);
        }

        &.is-email {
          color: #67c23a;
          background: rgba(103, 194, 58, 0.12);
        }

        &.is-question {
          color: #e6a23c;
          background: rgba(230, 162, 60, 0.12);
        }
      }

      .verify-block-title {
        flex: 1;
        min-width: 0;
      }

      .verify-label {
        display: flex;
        align-items: center;
        color: $light_gray;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.4;
      }

      .verify-req {
        margin-left: 8px;
        font-style: normal;
        font-size: 12px;
        font-weight: normal;
        color: #e6a23c;
        border: 1px solid rgba(230, 162, 60, 0.6);
        border-radius: 3px;
        padding: 0 6px;
        line-height: 18px;
      }

      .verify-desc {
        margin-top: 4px;
        font-size: 12px;
        color: $dark_gray;
        line-height: 1.5;
      }

      .verify-input {
        width: 100% !important;

        ::v-deep .el-input__inner {
          height: 44px;
          line-height: 44px;
          padding: 0 14px;
          background: rgba(255, 255, 255, 0.06);
          border: 1px solid rgba(255, 255, 255, 0.14);
          border-radius: 8px;
          color: #fff;
          font-size: 14px;
          transition: border-color 0.2s, box-shadow 0.2s;

          &::placeholder {
            color: rgba(255, 255, 255, 0.32);
          }

          &:focus {
            border-color: #409eff;
            box-shadow: 0 0 0 3px rgba(64, 158, 255, 0.12);
          }
        }
      }

      .code-row {
        display: flex;
        align-items: center;

        .verify-input {
          flex: 1;
          min-width: 0;
          width: auto !important;
        }

        .code-btn {
          flex: none;
          width: 124px;
          height: 44px;
          margin-left: 10px;
          padding: 0;
          border: 0;
          border-radius: 8px;
          background: linear-gradient(135deg, #409eff, #2f7fe0);
          color: #fff;
          font-size: 13px;
          font-weight: 500;
          box-shadow: 0 6px 14px rgba(64, 158, 255, 0.25);
          transition: background 0.2s, box-shadow 0.2s;

          &:hover,
          &:focus {
            background: linear-gradient(135deg, #5aa8ff, #3a8bea);
            color: #fff;
            box-shadow: 0 8px 18px rgba(64, 158, 255, 0.35);
          }

          &.is-disabled,
          &.is-disabled:hover,
          &.is-disabled:focus {
            background: rgba(255, 255, 255, 0.07);
            color: rgba(255, 255, 255, 0.4);
            box-shadow: none;
          }
        }
      }

      .verify-hint {
        margin-top: 8px;
        font-size: 12px;
        color: $dark_gray;
        line-height: 1.5;

        i {
          margin-right: 4px;
        }
      }

      .verify-tip {
        margin-top: 8px;
        font-size: 12px;
        color: #67c23a;

        i {
          margin-right: 4px;
        }
      }

      .question-item {
        margin-bottom: 12px;

        &:last-child {
          margin-bottom: 0;
        }

        .question-text {
          color: $light_gray;
          font-size: 13px;
          margin-bottom: 8px;
          line-height: 1.5;
        }
      }
    }

    .verify-actions {
      .verify-submit {
        width: 100%;
        height: 46px;
        font-size: 15px;
        font-weight: 600;
        letter-spacing: 1px;
        border: 0;
        border-radius: 9px;
        background: linear-gradient(135deg, #3a8ee6, #1f6fd0);
        box-shadow: 0 10px 24px rgba(31, 111, 208, 0.35);
        transition: transform 0.15s, box-shadow 0.2s, filter 0.2s;

        &:hover,
        &:focus {
          background: linear-gradient(135deg, #4c9bef, #2b7cdd);
          box-shadow: 0 12px 28px rgba(31, 111, 208, 0.45);
          transform: translateY(-1px);
          color: #fff;
        }

        &:active {
          transform: translateY(0);
        }
      }

      .verify-back {
        text-align: center;
        margin-top: 16px;
        font-size: 13px;
        color: $dark_gray;
        cursor: pointer;
        user-select: none;
        transition: color 0.2s;

        &:hover {
          color: #66b1ff;
        }

        i {
          margin-right: 4px;
        }
      }
    }

    @media (max-width: 560px) {
      width: calc(100% - 24px);
      margin: 2vh auto 20px;
      padding: 22px 16px;
      max-height: none;
      border-radius: 12px;

      .code-row .code-btn {
        width: 108px;
        font-size: 12px;
      }
    }
  }
}
</style>
