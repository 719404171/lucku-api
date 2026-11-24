<template>
  <div class="profile-page">
    <el-card shadow="never" class="profile-card">
      <div slot="header" class="card-header">
        <span>個人資料</span>
        <el-tag size="small" type="info">登錄帳號：{{ profile.username }}</el-tag>
      </div>

      <el-alert
        v-if="enabledTips.length"
        :title="'已開啟的登錄驗證：' + enabledTips.join('、')"
        type="warning"
        :closable="false"
        show-icon
        class="mb16"
      />

      <el-form :model="profileForm" label-width="120px" class="profile-form">
        <el-form-item label="頭像">
          <el-upload
            class="avatar-uploader"
            action="#"
            :show-file-list="false"
            :before-upload="beforeAvatarUpload"
            :http-request="uploadAvatar"
          >
            <div v-loading="avatarUploading" class="avatar-box">
              <img v-if="profile.avatar" :src="profile.avatar" class="avatar-img">
              <i v-else class="el-icon-plus avatar-uploader-icon" />
              <div class="avatar-mask"><i class="el-icon-camera" /><span>更換頭像</span></div>
            </div>
          </el-upload>
          <div class="field-tip">支持 JPG / PNG / GIF / WEBP，大小不超過 2MB</div>
        </el-form-item>
        <el-form-item label="綁定手機">
          <el-input v-model="profileForm.phone" maxlength="8" placeholder="香港 8 位手機號，例如 98839669" style="width: 320px;" />
          <div class="field-tip">用於接收後台登錄短信驗證碼</div>
        </el-form-item>
        <el-form-item label="綁定郵箱">
          <el-input v-model="profileForm.email" placeholder="用於接收後台登錄郵箱驗證碼" style="width: 320px;" />
          <div class="field-tip">開啟郵箱驗證後，登錄時需輸入該郵箱收到的驗證碼</div>
        </el-form-item>
        <el-form-item label="當前密碼">
          <el-input v-model="profileForm.password" type="password" show-password placeholder="修改綁定信息需驗證當前密碼" style="width: 320px;" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="savingProfile" @click="handleSaveProfile">保存綁定信息</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never" class="profile-card">
      <div slot="header" class="card-header">
        <span>安全問題</span>
        <el-button type="text" icon="el-icon-plus" :disabled="questionList.length >= 10" @click="addQuestion">新增問題</el-button>
      </div>

      <el-alert
        title="開啟安全問題驗證後，登錄時需要回答下面全部問題且答案正確才能通過。答案不區分大小寫。"
        type="info"
        :closable="false"
        show-icon
        class="mb16"
      />

      <div v-if="!questionList.length" class="empty-tip">暫未設置安全問題</div>

      <div v-for="(item, index) in questionList" :key="index" class="question-row">
        <div class="q-index">{{ index + 1 }}</div>
        <div class="q-body">
          <el-input v-model="item.question" placeholder="請輸入問題，例如：我的第一隻寵物叫什麼？" maxlength="255" />
          <el-input v-model="item.answer" placeholder="請輸入答案" class="q-answer" />
        </div>
        <el-button type="text" class="q-del" icon="el-icon-delete" @click="removeQuestion(index)" />
      </div>

      <el-form label-width="120px" class="mt16">
        <el-form-item label="當前密碼">
          <el-input v-model="questionForm.password" type="password" show-password placeholder="保存安全問題需驗證當前密碼" style="width: 320px;" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :loading="savingQuestions" @click="handleSaveQuestions">保存安全問題</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script>
import { getAdminProfile, editAdminProfile, saveAdminQuestion, editAdminAvatar } from '@/api/user'
import { reqUpload } from '@/api'

export default {
  name: 'AdminProfile',
  data() {
    return {
      profile: {
        username: '',
        avatar: '',
        email: '',
        phone: '',
        sms_enabled: 0,
        email_enabled: 0,
        question_enabled: 0
      },
      profileForm: {
        email: '',
        phone: '',
        password: ''
      },
      questionList: [],
      questionForm: {
        password: ''
      },
      savingProfile: false,
      savingQuestions: false,
      avatarUploading: false
    }
  },
  computed: {
    enabledTips() {
      const tips = []
      if (Number(this.profile.sms_enabled) === 1) tips.push('短信驗證')
      if (Number(this.profile.email_enabled) === 1) tips.push('郵箱驗證')
      if (Number(this.profile.question_enabled) === 1) tips.push('安全問題')
      return tips
    }
  },
  created() {
    this.loadProfile()
  },
  methods: {
    loadProfile() {
      getAdminProfile().then(response => {
        const result = response.results || {}
        this.profile = result
        this.profileForm.email = result.email || ''
        this.profileForm.phone = result.phone || ''
        this.questionList = (result.questions || []).map(q => ({
          id: q.id,
          question: q.question,
          answer: ''
        }))
      })
    },
    beforeAvatarUpload(file) {
      const isImage = /^image\/(jpeg|png|gif|webp)$/.test(file.type)
      const isLt2M = file.size / 1024 / 1024 < 2
      if (!isImage) {
        this.$message.error('只能上傳 JPG / PNG / GIF / WEBP 格式的圖片')
        return false
      }
      if (!isLt2M) {
        this.$message.error('圖片大小不能超過 2MB')
        return false
      }
      return true
    },
    uploadAvatar(option) {
      const formData = new FormData()
      formData.append('file', option.file)
      this.avatarUploading = true
      reqUpload(formData).then(response => {
        const url = (response.results || {}).file_path
        if (!url) {
          this.$message.error('上傳失敗，請稍後重試')
          return
        }
        return editAdminAvatar({ avatar: url }).then(() => {
          this.profile.avatar = url
          this.$store.commit('user/SET_AVATAR', url)
          this.$message.success('頭像已更新')
        })
      }).catch(() => {}).finally(() => {
        this.avatarUploading = false
      })
    },
    addQuestion() {
      if (this.questionList.length >= 10) {
        this.$message.warning('最多只能設置 10 個安全問題')
        return
      }
      this.questionList.push({ question: '', answer: '' })
    },
    removeQuestion(index) {
      this.questionList.splice(index, 1)
    },
    handleSaveProfile() {
      if (!this.profileForm.password) {
        this.$message.warning('請輸入當前密碼')
        return
      }
      if (!this.profileForm.phone && !this.profileForm.email) {
        this.$message.warning('手機和郵箱至少填寫一項')
        return
      }
      this.savingProfile = true
      editAdminProfile({
        email: this.profileForm.email,
        phone: this.profileForm.phone,
        password: this.profileForm.password
      }).then(() => {
        this.profileForm.password = ''
        this.$message.success('保存成功')
        this.loadProfile()
      }).catch(() => {}).finally(() => {
        this.savingProfile = false
      })
    },
    handleSaveQuestions() {
      if (!this.questionForm.password) {
        this.$message.warning('請輸入當前密碼')
        return
      }
      const questions = []
      for (let i = 0; i < this.questionList.length; i++) {
        const text = (this.questionList[i].question || '').trim()
        const answer = (this.questionList[i].answer || '').trim()
        if (text === '') {
          this.$message.warning(`第 ${i + 1} 個問題內容不能為空`)
          return
        }
        if (answer === '') {
          this.$message.warning(`第 ${i + 1} 個問題答案不能為空`)
          return
        }
        questions.push({ question: text, answer: answer })
      }
      if (!questions.length) {
        this.$message.warning('請至少設置一個安全問題')
        return
      }
      this.savingQuestions = true
      saveAdminQuestion({
        password: this.questionForm.password,
        questions: questions
      }).then(() => {
        this.questionForm.password = ''
        this.$message.success('保存成功')
        this.loadProfile()
      }).catch(() => {}).finally(() => {
        this.savingQuestions = false
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.profile-page {
  padding: 20px;

  .profile-card {
    max-width: 860px;
    margin-bottom: 20px;
    border-radius: 8px;
  }

  .card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
    color: #1f2d3d;
  }

  .mb16 {
    margin-bottom: 16px;
  }

  .mt16 {
    margin-top: 16px;
  }

  .field-tip {
    font-size: 12px;
    color: #909399;
    line-height: 1.6;
  }

  .avatar-uploader {
    .avatar-box {
      position: relative;
      width: 88px;
      height: 88px;
      border-radius: 8px;
      overflow: hidden;
      border: 1px dashed #d9d9d9;
      background: #fafbfc;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: border-color 0.2s;

      &:hover {
        border-color: #409eff;

        .avatar-mask {
          opacity: 1;
        }
      }

      .avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
      }

      .avatar-uploader-icon {
        font-size: 24px;
        color: #c0c4cc;
      }

      .avatar-mask {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.45);
        color: #fff;
        font-size: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;

        i {
          font-size: 18px;
          margin-bottom: 4px;
        }
      }
    }
  }

  .empty-tip {
    padding: 20px 0;
    text-align: center;
    color: #909399;
    font-size: 13px;
  }

  .question-row {
    display: flex;
    align-items: flex-start;
    padding: 12px;
    border: 1px solid #ebeef5;
    border-radius: 6px;
    margin-bottom: 12px;
    background: #fafbfc;

    .q-index {
      width: 28px;
      height: 28px;
      line-height: 28px;
      text-align: center;
      border-radius: 50%;
      background: #409eff;
      color: #fff;
      font-size: 13px;
      flex: none;
      margin-right: 12px;
    }

    .q-body {
      flex: 1;

      .q-answer {
        margin-top: 10px;
      }
    }

    .q-del {
      margin-left: 10px;
      color: #f56c6c;
    }
  }
}
</style>
