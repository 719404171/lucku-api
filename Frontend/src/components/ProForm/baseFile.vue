<template>
  <div>
    <div class="uploadm">
      <div class="image" v-for="(item, index) in imageUrls" :key="'k'+index">
        <ProVideo class="img" :src="item" v-if="['avi','mov','qt','asf','rm','mpeg','mpg','dat','mp4'].includes(item.split('.').reverse()[0])"></ProVideo>
        <el-image class="img" :src="item" fit="contain" :preview-src-list="psl" v-else></el-image>
        <div class="mask" :class="{desktop: device==='desktop'}"><i class="el-icon-delete" @click="del(index)"></i></div>
      </div>
      
      <div class="upload" v-if="isShow" @click="showFile=true"><i class="el-icon-plus upload-icon"></i></div>
    </div>
    <ProFile :show.sync="showFile" :limit="myLimit" :have="fileCount" v-model="files"></ProFile>
  </div>
</template>

<script>
export default {
  name: 'BaseFile',
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    value: [String, Array]
  },
  data() {
    return {
      showFile: false,
      files: [],
      imageUrls: []
    }
  },
  computed: {
    myLimit() {
      return this.config?.attr?.limit
    },
    fileCount() {
      // value 可能是字符串（逗号分隔的 URL）或数组，这里统一计算图片数量
      if (!this.value) return 0
      if (Array.isArray(this.value)) return this.value.length
      return this.value.split(',').filter(Boolean).length
    },
    isShow() {
      return this.imageUrls.length !== this.config?.attr?.limit
    },
    psl() {
      return this.imageUrls.filter(item => ['bmp','jpg','jpeg','tif','gif','png','fli','flc','swf'].includes(item.split('.').reverse()[0]))
    }
  },
  methods: {
    del(index) {
      this.imageUrls.splice(index, 1)
      this.$emit('input', Array.isArray(this.value) ? this.imageUrls : this.imageUrls.join(','))
    }
  },
  watch: {
    files(val) {
      this.imageUrls = [...this.imageUrls, ...val]
      this.$emit('input', Array.isArray(this.value) ? this.imageUrls : this.imageUrls.join(','))
    },
    value: {
      handler(val) {
        this.imageUrls = val ? (Array.isArray(this.value) ? val : val.split(',')) : []
      },
      immediate: true
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
      text-align: center;
      line-height: 20px;
      transition-duration: 500ms;
      color: rgba(255, 255, 255, 1);
      background-color: #F56C6C;
      i:hover {
        cursor: pointer;
      }
    }
    .desktop {
      visibility: hidden;
      color: rgba(255, 255, 255, 0);
      background-color: rgba(0, 0, 0, 0);
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
    border: 1px dashed #d9d9d9;
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    &:hover {
      border-color: #409EFF;
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
