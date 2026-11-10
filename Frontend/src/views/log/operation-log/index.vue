<template>
  <div class="app-container">
    <div class="df mb20" v-if="device==='desktop'">
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('ActLog')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('ActLog')"></el-button>
    </div>
    <div class="df mb20" v-else>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('ActLog')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('ActLog')"></el-button>
    </div>

    <!--  -->
    <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['del']" @row-control-click="onRcc" @page-change="onPageChange('ActLog', $event)" @selection-change="delIds=$event.map(({activity_id})=>activity_id)">
      <template #activity_type="{row}">{{$t('log.t'+row.activity_type)}}</template>
    </ProTable>

  </div>
</template>

<script>
import { delActLog } from '@/api'
import { log } from '@/form'
import paging from '@/mixins/paging'
export default {
  name: 'OperationLog',
  mixins: [paging],
  data() {
    return {
      SFC: log,
      delIds: [],
      column: {
        activity_value: 'log.value',
        activity_type: 'log.type',
        admin_name: 'coupon.admin',
        // created_at: 'log.date'
      },
    }
  },
  created() {
    this.getList('ActLog')
  },
  methods: {
    // 删除
    async del({activity_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delActLog({activity_id: activity_id ? [activity_id] : this.delIds})
          if (activity_id) {
            this.list = this.list.filter(item => item.activity_id !== activity_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.activity_id))
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