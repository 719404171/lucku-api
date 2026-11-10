<template>
  <div>
    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;">
      <div v-if="value" style="width:60px;height:60px;border-radius:4px;border:1px solid #eee;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#f5f5f5;">
        <el-image v-if="!isVideoUrl(value)" :src="value" fit="contain" style="width:60px;height:60px;" :preview-src-list="[value]">
          <div slot="error" style="font-size:20px;color:#bbb;"><i class="el-icon-picture-outline"></i></div>
        </el-image>
        <video v-else :src="value" style="width:100%;height:100%;object-fit:contain;"></video>
      </div>
      <el-button type="primary" plain size="small" @click="showFile=true">
        <i class="el-icon-folder-opened"></i> {{ $t('file.selectFromFile') }}
      </el-button>
      <el-button v-if="value" type="danger" plain size="small" @click="clearFile">
        <i class="el-icon-delete"></i>
      </el-button>
    </div>
    <ProFile :show.sync="showFile" :limit="1" :have="value ? 1 : 0" v-model="selectedFile"></ProFile>
  </div>
</template>

<script>
export default {
  name: 'FileSelectBtn',
  components: {
    ProFile: () => import('@/components/ProFile/index.vue')
  },
  props: {
    config: { type: Object, default: () => ({}) },
    value: [String, Array]
  },
  data() {
    return { showFile: false, selectedFile: [] }
  },
  watch: {
    selectedFile(val) {
      if (val.length) {
        this.$emit('input', Array.isArray(this.value) ? val : val[0])
      }
    }
  },
  methods: {
    isVideoUrl(url) {
      if (!url) return false
      const ext = url.split('.').reverse()[0].toLowerCase()
      return ['avi','mov','qt','asf','rm','mpeg','mpg','dat','mp4','webm','mkv','flv'].includes(ext)
    },
    clearFile() {
      this.$emit('input', Array.isArray(this.value) ? [] : '')
    }
  }
}
</script>
