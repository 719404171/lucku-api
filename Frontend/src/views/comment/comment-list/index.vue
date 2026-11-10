<template>
  <div class="app-container">
    <div class="mb20" v-if="device==='desktop'">
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('Comment')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Comment')"></el-button>
    </div>
    <div class="mb20" v-else>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click" width="320">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('Comment')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Comment')"></el-button>
    </div>

    <!--  -->
    <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['del']" @row-control-click="onRcc" @page-change="onPageChange('Comment', $event)" @selection-change="delIds=$event.map(({comment_id})=>comment_id)">
      <template #comment_img="{row}"><ProImage :src="row.comment_img"></ProImage></template>
    </ProTable>

  </div>
</template>

<script>
import { delComment } from '@/api'
import paging from '@/mixins/paging'
import { comment } from '@/form'
export default {
  name: 'CommentList',
  mixins: [paging],
  data() {
    return {
      SFC: comment,
      delIds: [],
      column: {
        comment_img: 'comment.img',
        comment_text:'comment.text',
        comment_score: 'comment.score',
        product_name: 'goods.name',
        user_name: 'user.name',
        // created_at: 'comment.time'
      },
    }
  },
  created() {
    this.getList('Comment')
  },
  methods: {
    // 删除
    async del({comment_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delComment({comment_id: comment_id ? [comment_id] : this.delIds})
          if (comment_id) {
            this.list = this.list.filter(item => item.comment_id !== comment_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.comment_id))
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
    }
  }
}
</script>