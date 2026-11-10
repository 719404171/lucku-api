<template>
  <div class="app-container">
    <el-card v-loading="isLoading">
      <div slot="header" class="clearfix">
        <span>{{$t('push.title')}}</span>
        <el-button style="float: right;" type="primary" :loading="isLoadSub" @click="submit" v-if="nodePower.includes('edit')">{{$t('common.save')}}</el-button>
      </div>
      <el-form label-position="top" ref="form" :model="form" :rules="rules">
        <template v-for="(val, key) in form">
          <el-form-item :key="key" :label="kebabToCamel(key.replace('_', '-'))" :prop="key" v-if="key!=='getui_id'"> 
            <el-input :placeholder="$t('common.enter')" v-model="form[key]"></el-input>
          </el-form-item>
        </template>
      </el-form>
    </el-card>
  </div>
</template>

<script>
import { kebabToCamel } from '@/utils'
import { editPushShop, getPushShop } from '@/api'
export default {
  name: 'PushSettings',
  data() {
    return {
      isLoading: false,
      isLoadSub: false,
      form: {
        getui_id: '',
        app_id: '',
        app_secret: '',
        app_key: '',
        master_secret: '',
        host: ''
      },
      rules: {}
    }
  },
  created() {
    this.init()
    this.getPush()
  },
  methods: {
    kebabToCamel,
    init() {
      for (const key in this.form) {
        this.$set(this.rules, key, [{required: true,message: this.$t('common.required'),trigger: "blur"}])
      }
    },
    // 获取推送设置
    async getPush() {
      try {
        this.isLoading = true
        const {results: {getui_id,app_id,app_secret,app_key,master_secret,host}} = await getPushShop()
        this.form = {getui_id,app_id,app_secret,app_key,master_secret,host}
      } catch (error) {
        
      } finally {
        this.isLoading = false
      }
    },
    // 提交表单
    submit() {
      this.$refs.form.validate(async valid => {
        if (!valid) return
        try {
          this.isLoadSub = true
          await editPushShop(this.form)
          this.$message({message: this.$t('common.success'),type: 'success'})
        } catch (error) {
          
        } finally {
          this.isLoadSub = false
        }
      })
    }
  }
}
</script>