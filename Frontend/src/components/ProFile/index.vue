<template>
<div class="file">
  <!--  -->
  <el-dialog :title="$t('file.title')" :width="device==='desktop' ? '1000px' : '100%'" :close-on-click-modal="false" :visible.sync="dialogShow" append-to-body @close="resetFile" @open="onOpen">
    <div class="file-cont">
      <div class="cate" v-loading="isLoadCate" v-if="device==='desktop'">
        <el-scrollbar wrap-class="scrollbar-wrapper">
          <div class="item" :class="{on: !file_sort_id, hov: !!file_sort_id}" @click="file_sort_id=''">
            <i :class="['el-icon-folder'+(!file_sort_id ? '-opened' : '')]"></i>
            <div class="txt">{{$t('file.all')}}</div><i class="el-icon-folder-add" :title="$t('file.addFolder')" @click="showLangCate=true"></i>
          </div>
          <div class="item" :class="{on: file_sort_id===item.file_sort_id, hov: file_sort_id!==item.file_sort_id}" v-for="(item, index) in cates" :key="item.file_sort_id" @click="file_sort_id = item.file_sort_id">
            <i :class="['el-icon-folder'+(file_sort_id===item.file_sort_id ? '-opened' : '')]"></i>
            <div class="txt" :title="item[(language==='en' ? 'en_' : '') +'file_sort_name']">{{item[(language==='en' ? 'en_' : '') +'file_sort_name']}}</div>
            <el-dropdown @command="handleCommand($event, item, index)">
              <i class="el-icon-more"></i>
              <el-dropdown-menu slot="dropdown">
                <el-dropdown-item command="showEdiCate">{{$t('common.edit')}}</el-dropdown-item>
                <el-dropdown-item command="delCate">{{$t('common.del')}}</el-dropdown-item>
              </el-dropdown-menu>
            </el-dropdown>
          </div>
        </el-scrollbar>
      </div>
      <div class="body">
        <div class="mc" v-if="device==='mobile' && showMc">
          <div class="cate" v-loading="isLoadCate">
            <el-scrollbar wrap-class="scrollbar-wrapper">
              <div class="item" :class="{on: !file_sort_id, hov: !!file_sort_id}" @click="file_sort_id=''">
                <i :class="['el-icon-folder'+(!file_sort_id ? '-opened' : '')]"></i>
                <div class="txt">{{$t('file.all')}}</div><i class="el-icon-folder-add" :title="$t('file.addFolder')" @click="showLangCate=true"></i>
              </div>
              <div class="item" :class="{on: file_sort_id===item.file_sort_id, hov: file_sort_id!==item.file_sort_id}" v-for="(item, index) in cates" :key="item.file_sort_id" @click="file_sort_id = item.file_sort_id">
                <i :class="['el-icon-folder'+(file_sort_id===item.file_sort_id ? '-opened' : '')]"></i>
                <div class="txt" :title="item[(language==='en' ? 'en_' : '') +'file_sort_name']">{{item[(language==='en' ? 'en_' : '') +'file_sort_name']}}</div>
                <el-dropdown trigger="click" @command="handleCommand($event, item, index)">
                  <i class="el-icon-more"></i>
                  <el-dropdown-menu slot="dropdown">
                    <el-dropdown-item command="showEdiCate">{{$t('common.edit')}}</el-dropdown-item>
                    <el-dropdown-item command="delCate">{{$t('common.del')}}</el-dropdown-item>
                  </el-dropdown-menu>
                </el-dropdown>
              </div>
            </el-scrollbar>
          </div>
        </div>
        <div class="control" :class="{pl20:device==='desktop', pl5: device==='mobile'}">
          <el-button type="primary" :icon="`el-icon-s-${showMc ? 'fold' : 'unfold'}`" v-if="device==='mobile'" @click="showMc=!showMc"></el-button>
          <el-button type="primary" icon="el-icon-refresh" plain @click="onOpen"></el-button>
          
          <template v-if="device==='desktop'">
            <el-button type="primary" @click="showUpload=true">{{$t('file.upload')}}</el-button>
            <el-button type="primary" :disabled="!modelValue.length" @click="useFile">{{$t('file.use')}}</el-button>
            <el-button type="danger" :disabled="!sltId.length" @click="delFile(sltId)">{{$t('file.del')}}</el-button>
          </template>
          <template v-if="device==='mobile'">
            <el-button type="primary" icon="el-icon-upload" @click="showUpload=true"></el-button>
            <el-button type="primary" icon="el-icon-success" :disabled="!modelValue.length" @click="useFile"></el-button>
            <el-button type="danger" icon="el-icon-delete-solid" :disabled="!sltId.length" @click="delFile(sltId)"></el-button>
          </template>
          
          <el-select style="margin-left: 10px" :placeholder="$t('file.move')" :disabled="!sltId.length" value="" @change="moveFile" v-if="device==='desktop' && !file_sort_id">
            <template v-for="(item, index) in options">
              <el-option v-if="index>0" :key="index" :label="item[(language==='en' ? 'en_' : '') + 'file_sort_name']" :value="item.file_sort_id"></el-option>
            </template>
          </el-select>
        </div>
        <div class="imgs" :class="{pl15:device==='desktop'}" v-loading="isLoading">
          <div class="item" v-for="item in list" :key="item.file_id">
            <template v-if="isVideo(item.file_url)">
              <div class="img video-item" :class="{on: sltId.includes(item.file_id)}" @click="sltFileMobile(item)">
                <video :src="item.file_url" preload="metadata" muted style="width:100%;height:100%;object-fit:contain;"></video>
                <i class="el-icon-video-play video-play-icon"></i>
              </div>
            </template>
            <el-image v-else class="img" :class="{on: sltId.includes(item.file_id)}" :src="item.file_url" fit="contain" v-bind="device==='desktop' ? {'preview-src-list':list.map(({file_url})=>file_url)} : {}" @click="sltFileMobile(item)"></el-image>
            <template v-if="device==='desktop'">
              <div class="ctr" v-if="sltId.includes(item.file_id)">
                <div class="btn" @click="cancelFile(item.file_id)">{{$t('common.cancel')}}</div>
              </div>
              <div class="ctr" v-else>
                <div class="btn" @click="sltFile(item)">{{$t('common.select')}}</div>
                <div class="btn" v-if="!sltId.includes(item.file_id)" @click="delFile([item.file_id])">{{$t('common.del')}}</div>
              </div>
            </template>
          </div>
        </div>
        <div class="pagination-box" :class="{pr80:device==='desktop', pr20:device==='mobile'}">
          <el-pagination @size-change="handlePageChange($event, 'size')" @current-change="handlePageChange($event, 'current')" :current-page.sync="page.current" :page-sizes="page.sizes || [10, 20, 30, 40, 50, 80]" :page-size.sync="page.size" layout="total, prev, pager, next" :total="page.total"></el-pagination>
        </div>
      </div>
    </div>
  </el-dialog>
  <!--  -->
  <el-dialog :title="$t('file.upload')" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" :visible.sync="showUpload" append-to-body @close="resetForm">
    <el-form label-position="top">
      <el-form-item :label="$t('file.type')">
        <el-radio-group v-model="form.type">
          <el-radio :label="1">{{$t('file.local')}}</el-radio>
          <el-radio :label="2">{{$t('file.net')}}</el-radio>
          <!-- <el-radio :label="2">扫码上传</el-radio> -->
        </el-radio-group>
      </el-form-item>
      <el-form-item :label="$t('file.cate')">
        <el-select style="width:280px" v-model="form.file_sort_id">
          <el-option v-for="(item, index) in options" :key="index" :label="item[(language==='en' ? 'en_' : '') + 'file_sort_name']" :value="item.file_sort_id"></el-option>
        </el-select>
      </el-form-item>
      <el-form-item :label="$t('file.upload')" v-if="form.type===1">
        <Upload v-model="form.file_url"></Upload>
      </el-form-item>
      <el-form-item :label="$t('file.netImg')" v-if="form.type===2">
        <div class="df">
          <el-input style="width:280px;margin-right: 10px;" :placeholder="$t('file.netPh')" v-model="file_url"></el-input>
          <el-button type="primary" :disabled="!file_url" @click="extImg">{{$t('file.extImg')}}</el-button>
        </div>
        <div class="ext-img"><ProImage :src="form.file_url" :size="80" delable expand @del="delExt"></ProImage></div>
      </el-form-item>
    </el-form>
    <span slot="footer" class="dialog-footer">
      <el-button @click="showUpload=false">{{$t('common.cancel')}}</el-button>
      <el-button type="primary" :loading="isLoadSub" @click="addFile">{{$t('common.confirm')}}</el-button>
    </span>
  </el-dialog>
  <ProLanginps :show.sync="showLangCate" :echo="cateEcho" @submit="editCate"></ProLanginps>
</div>
</template>

<script>
import paging from '@/mixins/paging'
import { mapGetters } from 'vuex'
import { getFileCate, editFileCate, delFileCate, addFile, delFile } from '@/api'
import Upload from '@/components/Upload/multiple'
export default {
  mixins: [paging],
  components: {Upload},
  props: {
    limit: {
      type: Number,
      default: Infinity
    },
    have: {
      type: Number,
      default: 0
    },
    show: Boolean
  },
  data() {
    return {
      showUpload: false,
      showLangCate: false,
      showMc: false,
      isLoadCate: false,
      isLoadSub: false,
      file_sort_id: '',
      cates: [],
      cateEcho: {},
      sltId: [],
      file_url: '',
      form: {
        type: 1,
        file_sort_id: '',
        file_url: []
      },
      options: [],
      modelValue: []
    }
  },
  computed: {
    ...mapGetters(['language']),
    dialogShow: {
      get() {
        return this.show
      },
      set(val) {
        this.$emit('update:show', val)
      }
    }
  },
  watch: {
    'form.type'() {
      this.form.file_url = []
    },
    file_sort_id(file_sort_id) {
      this.getList('File', {file_sort_id, limit: this.device==='desktop' ? 18 : 9})
    },
    device(val) {
      this.list = []
      this.getList('File', {limit: val==='desktop' ? 18 : 9, file_sort_id: this.file_sort_id})
    }
  },
  methods: {
    // 判断档案是否为影片
    isVideo(url) {
      if (!url) return false
      const ext = url.split('.').reverse()[0].toLowerCase()
      return ['avi','mov','qt','asf','rm','mpeg','mpg','dat','mp4','webm','mkv','flv'].includes(ext)
    },
    onOpen() {
      this.getCate()
      this.getList('File', {limit: this.device==='desktop' ? 18 : 9, file_sort_id: this.file_sort_id})
    },
    // 获取文件分类
    async getCate() {
      try {
        this.isLoadCate = true
        const {results} = await getFileCate()
        this.cates = results
        this.options = [{file_sort_name: this.$t('file.all'), en_file_sort_name:this.$t('file.all'), file_sort_id: ''}, ...results]
      } catch (error) {

      } finally {
        this.isLoadCate = false
      }
    },
    // 新增/编辑文件分类
    async editCate({zh:file_sort_name, en:en_file_sort_name, file_sort_id=''}) {
      try {
        this.isLoadCate = true
        await editFileCate(Object.assign({file_sort_id}, {file_sort_name, en_file_sort_name}))
        await this.getCate()
        this.$message({message: this.$t('common.success'), type: 'success'})
      } catch (error) {

      } finally {
        this.isLoadCate = false
      }
    },
    handleCommand(command, item, i) {
      this[command](item, i)
    },
    // 弹出编辑分类
    showEdiCate({file_sort_name:zh, en_file_sort_name: en, file_sort_id}) {
      this.cateEcho = {zh, en, file_sort_id}
      this.showLangCate = true
    },
    // 删除文件分类
    delCate({file_sort_id}, i) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delFileCate({file_sort_id})
          this.cates.splice(i, 1)
          const ci = this.options.findIndex(item => item.file_sort_id === file_sort_id)
          this.options.splice(ci, 1)
          if (this.file_sort_id == file_sort_id) {
            this.file_sort_id = ''
            this.getList('File', {limit: this.device==='desktop' ? 18 : 9, file_sort_id: this.file_sort_id})
          }
          this.$message({message: this.$t('common.success'), type: 'success'})
          done()
        } catch (error) {

        } finally {
          instance.confirmButtonLoading = false;
        }
      })
    },
    // 提取照片
    extImg() {
      if (!/(http.*?[jpg|png|jpeg|bmp])/g.test(this.file_url)) return this.$message({message: this.$t('file.netErr'),type: 'warning'})
      this.form.file_url.push(this.file_url)
      this.file_url = ''
    },
    // 删除提取照片
    delExt(index) {
      this.form.file_url.splice(index, 1)
    },
    // 新增文件
    async addFile() {
      const {type, file_sort_id, file_url} = this.form
      if (!file_url.length) return this.$message({message: this.$t('file.tips'+type), type: 'warning'})
      try {
        this.isLoadSub = true
        const params = file_url.map(file_url => ({file_sort_id, file_url}))
        await addFile(params)
        await this.getList('File', {limit: this.device==='desktop' ? 18 : 9, file_sort_id: this.file_sort_id})
        this.$message({message: this.$t('common.success'), type: 'success'})
        this.showUpload = false
      } catch (error) {
        
      } finally {
        this.isLoadSub = false
      }
    },
    // 重置上传表单
    resetForm() {
      this.form = {type: 1,file_sort_id: '',file_url: []}
    },
    // 文件列表分页
    handlePageChange(e, type) {
      this.onPageChange('File', { e, type })
    },
    // 重置图片选择
    resetFile() {
      this.sltId = []
      this.modelValue = []
      this.file_sort_id = ''
      this.showMc = false
    },
    // 选择图片
    sltFile({file_id, file_url}) {
      if (this.sltId.length === (this.limit-this.have)) return this.$message({message: this.$t('file.imgMax'), type: 'warning'})
      this.sltId.push(file_id)
      this.modelValue.push(file_url)
    },
    sltFileMobile({file_id, file_url}) {
      if (this.device==='desktop') return
      const index = this.sltId.findIndex(item => item === file_id)
      if (index > -1) {
        this.sltId.splice(index, 1)
        this.modelValue.splice(index, 1)
      } else {
        if (this.sltId.length === (this.limit-this.have)) return this.$message({message: this.$t('file.imgMax'), type: 'warning'})
        this.sltId.push(file_id)
        this.modelValue.push(file_url)
      }
    },
    // 取消选择
    cancelFile(file_id) {
      const index = this.sltId.findIndex(item => item === file_id)
      this.sltId.splice(index, 1)
      this.modelValue.splice(index, 1)
    },
    // 使用图片
    useFile() {
      this.$emit('input', this.modelValue)
      this.dialogShow = false
      this.sltId = []
      this.modelValue = []
    },
    // 删除图片
    delFile(file_id) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delFile({file_id})
          this.getList('File', {limit: this.device==='desktop' ? 18 : 9, file_sort_id: this.file_sort_id})
          this.sltId = []
          this.modelValue = []
          this.$message({message: this.$t('common.success'), type: 'success'})
          done()
        } catch (error) {
          console.log(error);
        } finally {
          instance.confirmButtonLoading = false;
        }
      })
    },
    // 移动图片
    async moveFile(file_sort_id) {
      try {
        this.isLoading = true
        const params = this.modelValue.map(file_url => ({file_sort_id, file_url}))
        await addFile(params)
        await this.getList('File', {limit: this.device==='desktop' ? 18 : 9, file_sort_id: this.file_sort_id})
        this.sltId = []
        this.modelValue = []
        this.$message({message: this.$t('common.success'), type: 'success'})
      } catch (error) {
        
      } finally {
        this.isLoading = false
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.file-cont {
  display: flex;
  align-items: stretch;
  height: 560px;
  margin-bottom: 20px;
  .cate {
    width: 220px;
    height: 560px;
    padding-right: 20px;
    border-right: 1px solid #dddddd;
    .el-scrollbar {
      height: 100%;
    }
    .item {
      display: flex;
      align-items: center;
      height: 30px;
      padding: 0 10px;
      box-sizing: border-box;
      .txt {
        max-width: 100px;
        margin-left: 10px;
        margin-right: auto;
        font-size: 12px;
        word-break: keep-all;
        text-overflow: ellipsis;
        overflow: hidden;
      }
    }
    .on {
      background-color: #ECF5FF;
    }
    .hov:hover {
      background-color: #F5F7FA;
    }
    .item:hover {
      cursor: pointer;
    }
  }
  .body {
    position: relative;
    flex: 1;
    display: flex;
    flex-direction: column;
    .mc {
      position: absolute;
      top: 55px;
      left: 5px;
      z-index: 99;
      width: 220px;
      height: 420px;
      background-color: #ffffff;
      border: 1px solid #eee;
      .cate {
        height: 420px;
        padding-right: 0;
        border-right: 0;
      }
    }
    .control {
      height: 50px;
    }
    .imgs {
      display: flex;
      flex-wrap: wrap;
      width: 100%;
      box-sizing: border-box;
      .item {
        width: 100px;
        height: 130px;
        margin: 5px;
        .img {
          width: 100px;
          height: 100px;
          background-color: #F8F8F8;
          border: 1px solid #F8F8F8;
          box-sizing: border-box;
        }
        .video-item {
          width: 100px;
          height: 100px;
          background-color: #F8F8F8;
          border: 1px solid #F8F8F8;
          box-sizing: border-box;
          cursor: pointer;
          position: relative;
          overflow: hidden;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        .video-play-icon {
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          font-size: 28px;
          color: rgba(255, 255, 255, 0.85);
          text-shadow: 0 0 6px rgba(0, 0, 0, 0.5);
          pointer-events: none;
        }
        .on {
          border:  1px solid #1890FF;
        }
        .ctr {
          display: none;
          align-items: center;
          justify-content: space-around;
          width: 100px;
          height: 30px;
          color: #1890FF;
          font-size: 12px;
          .btn {
            cursor: pointer;
          }
        }
      }
      .item:hover .ctr {
        display: flex;
      }
    }
    .pagination-box {
      display: flex;
      justify-content: flex-end;
      width: 100%;
      margin-top: auto;
      box-sizing: border-box;
    }
  }
}

::v-deep .el-scrollbar .scrollbar-wrapper {
  overflow-x: hidden !important;
}
</style>
