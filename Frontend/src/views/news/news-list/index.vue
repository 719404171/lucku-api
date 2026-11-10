<template>
  <div class="app-container">
    <div class="df mb20" v-if="device==='desktop'">
      <div class="control-search">
        <el-input :placeholder="$t('news.title')" clearable v-model="news_title" class="search">
          <el-button slot="append" icon="el-icon-search" @click="getList('News', {news_title})"></el-button>
        </el-input>
      </div>
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true">{{$t('common.add')}}</el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('News')"></el-button>
    </div>
    <el-row v-else>
      <el-col :span="24">
        <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true"></el-button>
        <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
        <el-button type="primary" icon="el-icon-refresh" plain @click="getList('News')"></el-button>
      </el-col>
      <el-col :span="24">
        <div class="control-search mtb20">
          <el-input :placeholder="$t('news.title')" clearable v-model="news_title" class="search">
            <el-button slot="append" icon="el-icon-search" @click="getList('News', {news_title})"></el-button>
          </el-input>
        </div>
      </el-col>
    </el-row>

    <!--  -->
    <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('News', $event)" @selection-change="delIds=$event.map(({news_id})=>news_id)">
      <template #news_img="{row}"><ProImage :src="row.news_img"></ProImage></template>
      <template #news_text="{row}"><el-link class="row-control-item" :class="$store.state.app.size" type="primary" :underline="false" @click="preview(row.news_text)">{{ $t('common.preview') }}</el-link></template>
      <template #en_news_text="{row}"><el-link class="row-control-item" :class="$store.state.app.size" type="primary" :underline="false" @click="preview(row.en_news_text)">{{ $t('common.preview') }}</el-link></template>
      <template #news_status="{row}"><el-tag :type="row.news_status ? '' : 'danger'">{{$t(statusTxt[row.news_status])}}</el-tag></template>
    </ProTable>

    <!--  -->
    <el-dialog :title="$t(`news.${this.echo.news_id ? 'editNews' : 'addNews'}`)" :visible.sync="show" :width="device==='desktop' ? '900px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules"></ProForm>
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>

    <!--  -->
    <el-dialog :title="$t('common.preview')" :visible.sync="showPrev" :width="device==='desktop' ? '426px' : '100%'" :close-on-click-modal="false" @closed="content=''">
      <ProPreview :content="content"></ProPreview>
    </el-dialog>
  </div>
</template>

<script>
import { editNews, delNews } from '@/api';
import { news } from '@/form';
import paging from '@/mixins/paging'
export default {
  name: 'NewsList',
  mixins: [paging],
  data() {
    return {
      EFC: news,
      news_title: '',
      show: false,
      delIds: [],
      column: {
        news_img: 'news.img',
        news_title: 'news.zh_title',
        en_news_title: 'news.en_title',
        news_text: 'news.zh_text',
        en_news_text: 'news.en_text',
        news_status: 'common.status'
      },
      echo: {},
      isLoadSub: false,
      showPrev: false,
      content: '',
      rules: {
        news_img: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        news_title: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        en_news_title: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        news_text: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        en_news_text: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
      }
    }
  },
  created() {
    this.getList('News')
  },
  watch: {
    news_title(val) {
      if (!val) {
        this.getList('News')
      }
    }

  },
  methods: {
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
      this.$set(this.EFC.formItems[5].item.attr, 'disabled', true)
    },
    // 预览
    preview(content) {
      this.content = content.replace(/<img/g, '<img style="width:100%"')
      this.showPrev = true
    },
    // 编辑
    edit({news_id, news_img, news_title, en_news_title, news_text, en_news_text, news_status}) {
      this.$set(this.EFC.formItems[5].item.attr, 'disabled', false)
      this.echo = {news_id, news_img, news_title, en_news_title, news_text, en_news_text, news_status}
      this.show = true
    },
    // 删除
    async del({news_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delNews({news_id: news_id ? [news_id] : this.delIds})
          if (news_id) {
            this.list = this.list.filter(item => item.news_id !== news_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.news_id))
            this.page.total -= this.delIds.length
          }
          this.delIds = []
          done()
        } catch (error) {
          
        } finally {
          instance.confirmButtonLoading = false;
        }
      })
    },
    // 操作
    onRcc({scope: {row},type}) {
      this[type](row)
    },
    submit() {
      this.$refs.editForm.submit(async (data) => {
        const params = this.echo.news_id ? data : {news_id: '', ...data}
        try {
          this.isLoadSub = true
          await editNews(params)
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.show = false
          this.getList('News')
        } catch (error) {
          console.log(error);
        } finally {
          this.isLoadSub = false
        }
      })
    }
  }
}
</script>

<style lang="scss">

</style>