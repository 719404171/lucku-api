<template>
  <div class="app-container">
    <div class="df aic mb20" v-if="device==='desktop'">
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('EmailSendLog')">确定</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">搜索</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('EmailSendLog')"></el-button>
    </div>
    <div class="df aic mb20" v-else>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('EmailSendLog')">确定</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('EmailSendLog')"></el-button>
    </div>

    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column"
      :column-attr="{ to_email: { minWidth: '180px' }, subject: { minWidth: '180px' }, error_msg: { minWidth: '200px' }, created_at: { minWidth: '160px' } }"
      @page-change="onPageChange('EmailSendLog', $event)">
      <template #status="{row}">
        <el-tag v-if="row.status === 1" type="success">成功</el-tag>
        <el-tag v-else-if="row.status === 2" type="danger">失败</el-tag>
        <el-tag v-else type="warning">发送中</el-tag>
      </template>
      <template #mailer="{row}">
        <el-tag v-if="row.mailer === 'smtp'" size="small">SMTP-1</el-tag>
        <el-tag v-else-if="row.mailer === 'smtp2'" size="small" type="info">SMTP-2</el-tag>
        <el-tag v-else size="small" type="info">{{row.mailer || '—'}}</el-tag>
      </template>
      <template #attempt="{row}">
        <el-tag v-if="row.attempt === 1" size="small">1</el-tag>
        <el-tag v-else size="small" type="warning">{{row.attempt}}</el-tag>
      </template>
      <template #user_id="{row}">
        <span v-if="row.user_id">{{row.user_id}}</span>
        <span v-else style="color:#ccc">—</span>
      </template>
      <template #user_name="{row}">
        <span v-if="row.user_name">{{row.user_name}}</span>
        <span v-else style="color:#ccc">—</span>
      </template>
      <template #user_status="{row}">
        <el-tag v-if="row.user_status === 1" size="small" type="success">已通过</el-tag>
        <el-tag v-else-if="row.user_status === 0" size="small" type="warning">未验证</el-tag>
        <el-tag v-else-if="row.user_id" size="small" type="danger">封禁</el-tag>
        <span v-else style="color:#ccc">—</span>
      </template>
      <template #duration_ms="{row}">{{row.duration_ms ? row.duration_ms + 'ms' : '—'}}</template>
      <template #error_msg="{row}">
        <el-tooltip v-if="row.error_msg" :content="row.error_msg" placement="top" :disabled="row.error_msg.length < 30">
          <span style="color:#f56c6c">{{ row.error_msg.length > 30 ? row.error_msg.substring(0, 30) + '...' : row.error_msg }}</span>
        </el-tooltip>
        <span v-else>—</span>
      </template>
    </ProTable>
  </div>
</template>

<script>
import paging from '@/mixins/paging'
export default {
  name: 'EmailSendLog',
  mixins: [paging],
  data() {
    return {
      SFC: {
        multi: { label: '邮箱/用户名', component: 'el-input', value: '', clearable: true },
        status: { label: '发送状态', component: 'el-select', value: '', clearable: true, options: [
          { label: '全部', value: '' },
          { label: '成功', value: 1 },
          { label: '失败', value: 2 },
          { label: '发送中', value: 0 },
        ]},
        mailer: { label: 'Mailer', component: 'el-select', value: '', clearable: true, options: [
          { label: '全部', value: '' },
          { label: 'SMTP-1', value: 'smtp' },
          { label: 'SMTP-2', value: 'smtp2' },
        ]},
      },
      column: {
        log_id: 'ID',
        to_email: '收件邮箱',
        subject: '邮件标题',
        mailer: 'Mailer',
        attempt: '尝试次数',
        status: '发送状态',
        duration_ms: '耗时',
        user_id: '用户ID',
        user_name: '用户名',
        user_status: '用户验证',
        created_at: '发送时间',
        error_msg: '失败原因',
      },
    }
  },
  created() {
    this.getList('EmailSendLog')
  },
}
</script>
