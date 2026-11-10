<template>
  <div class="app-container">
    <el-card>
      <div slot="header" class="clearfix">
        <span>{{$t('agreement.user')}}</span>
        <el-button style="float: right;" type="primary" :loading="isLoadSub" @click="submit" v-if="nodePower.includes('edit')">{{$t('common.save')}}</el-button>
      </div>
      <el-form ref="form" label-position="top" :model="content" v-loading="isLoading">
        <el-form-item prop="clause_content" :label="$t('common.zh')"><Tinymce v-model="content.clause_content"></Tinymce></el-form-item>
        <el-form-item prop="en_clause_content" :label="$t('common.en')"><Tinymce v-model="content.en_clause_content"></Tinymce></el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script>
import { getClause, editClause } from '@/api'
import Tinymce from '@/components/Tinymce'
export default {
  name: 'UserAgreement',
  components: {Tinymce},
  data() {
    return {
      content: {
        clause_id: '',
        clause_content: '',
        en_clause_content: ''
      },
      isLoading: false,
      isLoadSub: false,
      rules: {
        clause_content: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        en_clause_content: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
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
        const {results: {clause_id, clause_content, en_clause_content}} = await getClause()
        this.content = {clause_id, clause_content, en_clause_content}
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
          await editClause(this.content)
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