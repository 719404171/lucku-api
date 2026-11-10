<template>
  <div class="app-container">
    <el-card>
      <div slot="header" class="clearfix">
        <span>{{$t('agreement.userProtocol')}}</span>
        <el-button style="float: right;" type="primary" :loading="isLoadSub" @click="submit" v-if="nodePower.includes('edit')">{{$t('common.save')}}</el-button>
      </div>
      <el-form ref="form" label-position="top" :model="content" v-loading="isLoading">
        <el-form-item prop="ua_content" :label="$t('common.zh')"><Tinymce v-model="content.ua_content"></Tinymce></el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script>
import { getUserAgreement, editUserAgreement } from '@/api'
import Tinymce from '@/components/Tinymce'
export default {
  name: 'UserProtocol',
  components: {Tinymce},
  data() {
    return {
      content: {
        ua_id: '',
        ua_content: ''
      },
      isLoading: false,
      isLoadSub: false,
      rules: {
        ua_content: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
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
        const {results: {ua_id, ua_content}} = await getUserAgreement()
        this.content = {ua_id, ua_content}
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
          await editUserAgreement(this.content)
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
