<template>
  <div class="app-container">
    <el-card v-loading="isLoading" class="box-card">
      <div slot="header" class="clearfix">
        <span>维护公告</span>
        <el-button v-if="nodePower.includes('edit')" style="float: right;" type="primary" :loading="isLoadSub" @click="submit">保存</el-button>
      </div>
      <el-form label-position="top" :model="form">
        <el-form-item label="维护模式">
          <el-switch v-model="form.maintenance_enabled" :active-value="1" :inactive-value="0" />
          <div class="gray9 mt4">开启后：网站所有内容隐藏，只显示维护公告。APP 端每次请求接口会从响应头 X-Maintenance-Enabled 感知维护状态，无需轮询。保存后立即生效。</div>
        </el-form-item>
        <el-form-item label="公告内容">
          <QuillEditor v-model="form.notice_content" />
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script>
import { getMaintenanceNotice, editMaintenanceNotice } from '@/api'
import QuillEditor from '@/components/QuillEditor'

export default {
  name: 'MaintenanceNotice',
  components: { QuillEditor },
  data() {
    return {
      isLoading: false,
      isLoadSub: false,
      form: {
        maintenance_enabled: 0,
        notice_content: ''
      }
    }
  },
  created() {
    this.getData()
  },
  methods: {
    async getData() {
      try {
        this.isLoading = true
        const { results } = await getMaintenanceNotice()
        Object.assign(this.form, {
          maintenance_enabled: results.maintenance_enabled || 0,
          notice_content: results.notice_content || ''
        })
      } catch (error) {
      } finally {
        this.isLoading = false
      }
    },
    async submit() {
      try {
        this.isLoadSub = true
        await editMaintenanceNotice(this.form)
        this.$message({ message: '保存成功', type: 'success' })
      } catch (error) {
      } finally {
        this.isLoadSub = false
      }
    }
  }
}
</script>

<style lang="scss" scoped>
</style>
