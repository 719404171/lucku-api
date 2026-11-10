<template>
  <div class="app-container">
    <div class="df mb20">
      <div class="control-search">
        <el-input :placeholder="$t('user.name')" clearable v-model="user_name" class="search">
          <el-button slot="append" icon="el-icon-search" @click="getList('Message', {user_name})"></el-button>
        </el-input>
      </div>
      <el-button type="danger" icon="el-icon-delete" v-if="device==='desktop' && nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="device==='mobile' && nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('Message')"></el-button>
    </div>

    <!--  -->
    <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['del']" @row-control-click="onRcc" @page-change="onPageChange('Message', $event)" @selection-change="delIds=$event.map(({message_id})=>message_id)">
      <!-- <template #comment_img="{row}"><ProImage :src="row.comment_img"></ProImage></template> -->
    </ProTable>

  </div>
</template>

<script>
import { delMessage } from '@/api'
import paging from '@/mixins/paging'
export default {
  name: 'MessageList',
  mixins: [paging],
  data() {
    return {
      user_name: '',
      delIds: [],
      column: {
        message_content: 'message.content',
        user_name: 'user.name',
        // created_at: 'common.created_at'
      },
    }
  },
  created() {
    this.getList('Message')
  },
  watch: {
    user_name(val) {
      if (!val) {
        this.getList('Message')
      }
    }
  },
  methods: {
    // 删除
    async del({message_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delMessage({message_id: message_id ? [message_id] : this.delIds})
          if (message_id) {
            this.list = this.list.filter(item => item.message_id !== message_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.message_id))
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