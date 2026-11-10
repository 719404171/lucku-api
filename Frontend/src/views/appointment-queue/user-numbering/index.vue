<template>
  <div class="app-container">
    <div class="mb20" v-if="device==='desktop'">
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('UserNum')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'UserNum')"></el-button>
    </div>
    <div class="mb20" v-else>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('UserNum')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'UserNum')"></el-button>
    </div>

    <!--  -->
    <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['del']" @row-control-click="onRcc" @page-change="onPageChange('UserNum', $event)" @selection-change="delIds=$event.map(({user_reservation_id})=>user_reservation_id)">
      <template #user_status="{row}">
        <el-select v-if="nodePower.includes('status')" v-loading="isLoadStatus && curUser === row.user_reservation_id" :value="row.user_status" @change="editUserStatus($event, row.user_reservation_id)"><el-option v-for="i in 4" :key="i" :label="$t('userNum.us'+i)" :value="i*1"></el-option></el-select>
        <el-tag type="primary" v-else>{{$t('userNum.us'+row.user_status)}}</el-tag>
      </template>
    </ProTable>

  </div>
</template>

<script>
import { editUserNum, delUserNum } from '@/api';
import { userNum } from '@/form';
import paging from '@/mixins/paging'
export default {
  name: 'UserNumbering',
  mixins: [paging],
  data() {
    return {
      SFC: userNum,
      delIds: [],
      column: {
        reservation_project_name: 'appoNum.name',
        user_name: 'user.name',
        user_ref: 'userNum.user_ref',
        user_numerical: 'userNum.user_numerical',
        user_date: 'userNum.user_date',
        user_status: 'userNum.user_status',
      },
      isLoadStatus: false,
      curUser: ''
    }
  },
  created() {
    this.getList('UserNum')
  },
  methods: {
    // 删除
    async del({user_reservation_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delUserNum({user_reservation_id: user_reservation_id ? [user_reservation_id] : this.delIds})
          if (user_reservation_id) {
            this.list = this.list.filter(item => item.user_reservation_id !== user_reservation_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.user_reservation_id))
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
    // 修改用户状态
    async editUserStatus(user_status, user_reservation_id) {
      try {
        this.curUser = user_reservation_id
        this.isLoadStatus = true
        await editUserNum({user_status, user_reservation_id})
        this.list.find(item => item.user_reservation_id === user_reservation_id).user_status = user_status
      } catch (error) {
        
      } finally {
        this.isLoadStatus = false
        this.curUser = ''
      }
    }
  }
}
</script>