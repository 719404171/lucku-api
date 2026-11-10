<template>
  <div class="app-container">
    <el-card :loading="isLoading" shadow="never">
      <div slot="header" class="clearfix">
        <span style="font-size: 16px; font-weight: bold;">{{ $t('bannerPopup.img') }}</span>
        <el-tag class="ml10" :type="echo.popup_enable ? 'success' : 'info'" size="small">
          {{ echo.popup_enable ? $t('bannerPopup.enableOn') : $t('bannerPopup.enableOff') }}
        </el-tag>
      </div>
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules"></ProForm>
      <div class="mt20 text-center">
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{ $t('bannerPopup.save') }}</el-button>
      </div>
    </el-card>
  </div>
</template>

<script>
import api from '@/api';
import { bannerPopup } from '@/form';

export default {
  name: 'BannerPopupList',
  data() {
    return {
      EFC: bannerPopup.EFC,
      echo: {
        popup_id: null,
        popup_title: '',
        popup_img: '',
        popup_enable: 1,
        popup_status: 1
      },
      isLoading: false,
      isLoadSub: false,
      rules: {
        popup_img: [{ required: true, message: this.$t('common.required'), trigger: 'change' }]
      }
    }
  },
  created() {
    this.fetchData()
  },
  methods: {
    async fetchData() {
      this.isLoading = true
      try {
        const { results } = await api.getBannerPopup()
        if (results) {
          this.echo = { ...this.echo, ...results }
        }
      } catch (e) {
        console.error(e)
      } finally {
        this.isLoading = false
      }
    },
    submit() {
      this.$refs.editForm.submit(async (data) => {
        try {
          this.isLoadSub = true
          await api.editBannerPopup(data)
          this.$message({ message: this.$t('common.success'), type: 'success' })
          this.fetchData()
        } catch (e) {
          console.error(e)
        } finally {
          this.isLoadSub = false
        }
      })
    }
  }
}
</script>
