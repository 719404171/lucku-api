<template>
  <div id="app">
    <quill-editor ref="myQuillEditor" :options="editorOption" v-model="content"></quill-editor>
    <EditorImage :show.sync="show" @successCBK="imageSuccessCBK"></EditorImage>
  </div>
</template>
 
<script>
import EditorImage from './components/EditorImage.vue'
import { quillEditor } from 'vue-quill-editor'
import 'quill/dist/quill.core.css'
import 'quill/dist/quill.snow.css'
import 'quill/dist/quill.bubble.css'

const toolbarOptions = [
  ['bold', 'italic', 'underline', 'strike'], //加粗，斜体，下划线，删除线
  ['blockquote'], //引用，代码块
  // [{ 'header': 1 }, { 'header': 2 }], // 标题，键值对的形式；1、2表示字体大小
  [{ 'list': 'ordered' }, { 'list': 'bullet' }], //列表
  [{ 'script': 'sub' }, { 'script': 'super' }], // 上下标
  [{ 'indent': '-1' }, { 'indent': '+1' }], // 缩进
  [{ 'direction': 'rtl' }], // 文本方向
  [{ 'size': ['small', false, 'large', 'huge'] }], // 字体大小
  [{ 'header': [1, 2, 3, 4, 5, 6, false] }], //几级标题
  [{ 'color': [] }, { 'background': [] }], // 字体颜色，字体背景颜色
  [{ 'font': [] }], //字体
  [{ 'align': [] }], //对齐方式
  ['clean'], //清除字体样式
  ['image', 'video'] //上传图片、上传视频
];

export default {
  components: {quillEditor, EditorImage},
  props: {
    value: String
  },
  data() {
    return {
      show: false,
      content: this.value,
      editorOption: {
        modules: {
          toolbar: {
            container: toolbarOptions,  // 工具栏
            handlers: {
              'image': (value) => {
                if (value) {
                  // 调用el-upload的click点击事件实现文件上传
                  this.show = true
                } else {
                  this.$refs['myQuillEditor'].quill.format('image', false);
                }
              }
            }
          }
        }
      }
    }
  },
  watch: {
    value(val) {
      this.content = val
    },
    content(val) {
      this.$emit('input', val)
    }
  },
  methods: {
    imageSuccessCBK(arr) {
      arr.forEach(v => this.content+=`<img class="wscnph" src="${v}" ><p>\n</p>`)
    }
  }
}
</script>

<style lang="scss">
  .ql-editor {
    height: 500px;
  }
</style>