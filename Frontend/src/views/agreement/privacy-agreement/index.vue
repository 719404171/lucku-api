<template>
  <div class="app-container">
    <el-card>
      <div slot="header" class="clearfix">
        <span>{{$t('agreement.privacy')}}</span>
        <el-button style="float: right;" type="primary" :loading="isLoadSub" @click="submit" v-if="nodePower.includes('edit')">{{$t('common.save')}}</el-button>
      </div>
      <el-form ref="form" label-position="top" :model="content" v-loading="isLoading">
        <el-form-item prop="privacy_content" :label="$t('common.zh')"><Tinymce v-model="content.privacy_content"></Tinymce></el-form-item>
        <el-form-item prop="en_privacy_content" :label="$t('common.en')"><Tinymce v-model="content.en_privacy_content"></Tinymce></el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script>
import { getPrivacy, editPrivacy } from '@/api'
import Tinymce from '@/components/Tinymce'
export default {
  name: 'PrivacyAgreement',
  components: {Tinymce},
  data() {
    return {
      content: {
        privacy_id: '',
        privacy_content: '',
        en_privacy_content: ''
      },
      isLoading: false,
      isLoadSub: false,
      rules: {
        privacy_content: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        en_privacy_content: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
      }
    }
  },
  created() {
    this.getAgreement()
  },
  methods: {
    // 获取用户协议
    async getAgreement() {
      try {
        this.isLoading = true
        const {results: {privacy_id, privacy_content, en_privacy_content}} = await getPrivacy()
        this.content = {privacy_id, privacy_content, en_privacy_content}
      } catch (error) {
        
      } finally {
        this.isLoading = false
      }
    },
    // 提交表单
    async submit() {
      this.$refs.form.validate(async (valid) => {
        if (!valid) return
        try {
          this.isLoadSub = true
          await editPrivacy(this.content)
          this.$message({message: this.$t('common.success'), type: 'success'})
        } catch (error) {
          
        } finally {
          this.isLoadSub = false
        }
      });
    }
  }
}
</script>