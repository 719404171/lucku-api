<template>
  <div class="app-container">
    <div class="df mb20" v-if="device==='desktop'">
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('DrawRecord')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('DrawRecord')"></el-button>
      <el-form label-suffix="：" inline class="ml20">
        <el-form-item :label="$t('draw.totalMoney')">{{ sales[2] }}</el-form-item>
      </el-form>
    </div>
    <div class="df mb20" v-else>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('DrawRecord')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('DrawRecord')"></el-button>
    </div>

    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column"
      :column-attr="{ task_id: { minWidth: '80px' }, user_id: { minWidth: '80px' }, total_cost: { minWidth: '100px' } }"
      @page-change="onPageChange('DrawRecord', $event)">
      <template #status="{row}">
        <el-tag size="small" :type="statusType(row.status)">{{ $t('draw.st' + row.status) }}</el-tag>
      </template>
    </ProTable>
  </div>
</template>

<script>
import { drawRecord } from '@/form'
import paging from '@/mixins/paging'
export default {
  name: 'DrawRecord',
  mixins: [paging],
  data() {
    return {
      SFC: drawRecord,
      sales: [],
      column: {
        task_id: 'draw.taskId',
        user_id: 'draw.userId',
        user_name: 'draw.userName',
        user_email: 'common.email',
        card_sort_name: 'draw.cardSort',
        card_count: 'draw.cardCount',
        total_cost: 'draw.totalCost',
        status: 'draw.status',
        created_at: 'draw.time',
      }
    }
  },
  created() {
    this.getList('DrawRecord')
  },
  methods: {
    statusType(status) {
      return { 0: 'warning', 1: 'success', 2: 'danger' }[status] || 'info'
    }
  }
}
</script>
