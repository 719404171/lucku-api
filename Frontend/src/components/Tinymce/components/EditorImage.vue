<template>
  <div class="upload-container">
    <el-button :style="{background:color,borderColor:color}" icon="el-icon-upload" size="mini" type="primary" @click=" dialogVisible=true" v-if="device==='desktop'">{{$t('file.upload')}}</el-button>
    <el-button :style="{background:color,borderColor:color}" icon="el-icon-upload" size="mini" type="primary" @click=" dialogVisible=true" v-else></el-button>
    <el-dialog :visible.sync="dialogVisible" :title="$t('file.upload')" :width="device==='desktop' ? '1000px' : '100%'" append-to-body>
      <!-- <el-upload
        :multiple="true"
        :file-list="fileList"
        :show-file-list="true"
        :on-remove="handleRemove"
        :on-success="handleSuccess"
        :before-upload="beforeUpload"
        class="editor-slide-upload"
        action="https://httpbin.org/post"
        list-type="picture-card"
      >
        <el-button size="small" type="primary">
          Click upload
        </el-button>
      </el-upload> -->
      <!-- <UploadMultiple v-model="imgSrcs"></UploadMultiple> -->
      <BaseFile v-model="imgSrcs"></BaseFile>
      <el-button @click="dialogVisible = false">
        {{$t('common.cancel')}}
      </el-button>
      <el-button type="primary" @click="handleSubmit">
        {{$t('common.submit')}}
      </el-button>
    </el-dialog>
  </div>
</template>

<script>
// import { getToken } from 'api/qiniu'
import UploadMultiple from '@/components/Upload/multiple.vue'
import BaseFile from '@/components/ProForm/baseFile.vue'

export default {
  components: {UploadMultiple, BaseFile},
  name: 'EditorSlideUpload',
  props: {
    color: {
      type: String,
      default: '#1890ff'
    }
  },
  data() {
    return {
      dialogVisible: false,
      // listObj: {},
      // fileList: [],
      imgSrcs: ''
    }
  },
  methods: {
    // checkAllSuccess() {
    //   return Object.keys(this.listObj).every(item => this.listObj[item].hasSuccess)
    // },
    handleSubmit() {
      // const arr = Object.keys(this.listObj).map(v => this.listObj[v])
      if (!this.imgSrcs) {
        this.$message('请上传图片')
        return
      }
      this.$emit('successCBK', this.imgSrcs.split(','))
      this.imgSrcs = ''
      // this.listObj = {}
      // this.fileList = []
      this.dialogVisible = false
    },
    // handleSuccess(response, file) {
    //   const uid = file.uid
    //   const objKeyArr = Object.keys(this.listObj)
    //   for (let i = 0, len = objKeyArr.length; i < len; i++) {
    //     if (this.listObj[objKeyArr[i]].uid === uid) {
    //       this.listObj[objKeyArr[i]].url = response.files.file
    //       this.listObj[objKeyArr[i]].hasSuccess = true
    //       return
    //     }
    //   }
    // },
    // handleRemove(file) {
    //   const uid = file.uid
    //   const objKeyArr = Object.keys(this.listObj)
    //   for (let i = 0, len = objKeyArr.length; i < len; i++) {
    //     if (this.listObj[objKeyArr[i]].uid === uid) {
    //       delete this.listObj[objKeyArr[i]]
    //       return
    //     }
    //   }
    // },
    // beforeUpload(file) {
    //   const _self = this
    //   const _URL = window.URL || window.webkitURL
    //   const fileName = file.uid
    //   this.listObj[fileName] = {}
    //   return new Promise((resolve, reject) => {
    //     const img = new Image()
    //     img.src = _URL.createObjectURL(file)
    //     img.onload = function() {
    //       _self.listObj[fileName] = { hasSuccess: false, uid: file.uid, width: this.width, height: this.height }
    //     }
    //     resolve(true)
    //   })
    // }
  }
}
</script>

<style lang="scss" scoped>
.editor-slide-upload {
  margin-bottom: 20px;
  ::v-deep .el-upload--picture-card {
    width: 100%;
  }
}
</style>
