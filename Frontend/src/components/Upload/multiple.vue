<template>
<div class="uploadm">
  <transition-group class="uploadm" name="fade">
    <div class="image" v-for="(item, index) in imageUrls" :key="item">
      <ProVideo class="img" :src="item" v-if="['avi','mov','qt','asf','rm','mpeg','mpg','dat','mp4'].includes(item.split('.').reverse()[0])"></ProVideo>
      <el-image class="img" :src="item" fit="contain" :preview-src-list="imageUrls.filter(item => ['bmp','jpg','jpeg','tif','gif','png','fli','flc','swf'].includes(item.split('.').reverse()[0]))" v-else></el-image>
      <div class="mask"><i class="el-icon-delete" @click="del(index)"></i></div>
    </div>
  </transition-group>
  
  <el-upload class="upload" action="#" v-bind="config.attr || {}" v-if="isShow" :show-file-list="false" :http-request="httpRequest" multiple>
    <i class="upload-icon" :class="[`el-icon-${isLoading ? 'loading' : 'plus'}`]"></i>
  </el-upload>
</div>
</template>

<script>
import {reqUpload} from '@/api'
export default {
  name: 'UploadMultiple',
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    value: [String, Array]
  },
  data() {
    return {
      imageUrls: [],
      isShow: true,
      isLoading: false
    };
  },
  methods: {
    async httpRequest({file}) {
      try {
        this.isLoading = true
        const formData = new FormData()
        formData.append('file', file)
        // 以下部分为自己的上传图片接口
        const {results: {file_path}} = await reqUpload(formData)
        this.imageUrls.push(file_path)
        this.$emit('input', Array.isArray(this.value) ? this.imageUrls : this.imageUrls.join(','))
        this.isLoading = false
      } catch (error) {
        this.isLoading = false
      }
    },
    del(index) {
      this.imageUrls.splice(index, 1)
      this.$emit('input', Array.isArray(this.value) ? this.imageUrls : this.imageUrls.join(','))
    },
    beforeUpload(file) {
      // console.log(file);
    }
  },
  watch: {
    value: {
      handler(val) {
        this.imageUrls = val ? (Array.isArray(this.value) ? val : val.split(',')) : []
      },
      immediate: true
    },
    imageUrls(val) {
      this.isShow = val.length !== this.config?.attr?.limit
    }
  }
}
</script>

<style lang="scss" scoped>
.fade-enter {
  transform: translateY(-20px);
  opacity: 0;
  transition: all .5s;
}

.fade-enter-active, .fade-leave-active {
  transform: translateY(-10px);
  opacity: 0.5;
  transition: all .5s;
}

.fade-enter-to {
  transform: translateY(0);
  opacity: 1;
  transition: all .5s;
}

.fade-leave {
  transform: translateY(0);
  transition: all .5s;
}

.fade-leave-to {
  transform: translateY(-20px);
  opacity: 0;
  transition: all .5s;
}

.uploadm {
  display: flex;
  flex-wrap: wrap;
  .image {
    position: relative;
    width: 120px;
    height: 120px;
    margin-right: 10px;
    margin-bottom: 10px;
    border-radius: 6px;
    overflow: hidden;
    .img {
      width: 120px;
      height: 120px;
      background-color: #eee;
    }
    .mask {
      position: absolute;
      top: 0;
      right: 0;
      width: 20px;
      height: 20px;
      font-size: 12px;
      color: rgba(255, 255, 255, 0);
      text-align: center;
      line-height: 20px;
      visibility: hidden;
      background-color: rgba(0, 0, 0, 0);
      transition-duration: 500ms;
      i:hover {
        cursor: pointer;
      }
    }
    &:hover .mask {
      visibility: visible;
      color: rgba(255, 255, 255, 1);
      background-color: #F56C6C;
    }
  }
  .loading {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 120px;
    height: 120px;
    margin-right: 10px;
    margin-bottom: 10px;
    border-radius: 6px;
    border: 1px solid #eee;
  }
  .upload {
    margin-right: 10px;
    margin-bottom: 10px;
    ::v-deep .el-upload {
      border: 1px dashed #d9d9d9;
      border-radius: 6px;
      cursor: pointer;
      position: relative;
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
  }
}
</style>
