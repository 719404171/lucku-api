<template>
  <el-upload class="upload" v-bind="config.attr || {}" action="#" :show-file-list="false" :http-request="httpRequest">
    <el-image class="img" v-if="imageUrl" :src="imageUrl" fit="contain"></el-image>
    <template v-else>
      <i class="upload-icon" :class="[`el-icon-${isLoading ? 'loading' : 'plus'}`]"></i>
    </template>
  </el-upload>
</template>

<script>
import {reqUpload} from '@/api'
export default {
  name: 'Upload',
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    value: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      imageUrl: '',
      isLoading: false
    };
  },
  methods: {
    async httpRequest({file}) {
      try {
        this.imageUrl = ''
        this.isLoading = true
        this.$emit('upload-start')
        const formData = new FormData()
        formData.append('file', file)
        // 以下部分为自己的上传图片接口
        const {results: {file_path}} = await reqUpload(formData);
        this.imageUrl = file_path
        this.$emit('input', file_path)
        this.isLoading = false
        this.$emit('upload-end')
      } catch (error) {
        this.isLoading = false
        this.$emit('upload-end')
      }
    }
  },
  watch: {
    value: {
      handler(val) {
        this.imageUrl = val ? val : ''
      },
      immediate: true
    }
  }
}
</script>

<style lang="scss" scoped>
.upload {
  ::v-deep .el-upload {
    width: 120px;
    height: 120px;
    border: 1px dashed #d9d9d9;
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    &:hover {
      border-color: #409EFF;
    }
  }
  .upload-icon {
    font-size: 28px;
    color: #8c939d;
    width: 120px;
    height: 120px;
    line-height: 120px;
    text-align: center;
  }
  .img {
    width: 120px;
    height: 120px;
    background-color: #eee;
  }
  .loading {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 120px;
    height: 120px;
  }
}
</style>
