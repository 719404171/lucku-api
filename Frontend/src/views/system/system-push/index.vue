<template>
  <div class="app-container">
    <div class="mb20 df" v-if="device==='desktop'">
      <div class="control-search">
        <el-input :placeholder="$t('pushMsg.search')" clearable v-model="username" class="search">
          <el-button slot="append" icon="el-icon-search" @click="getList('PushMsg', {username})"></el-button>
        </el-input>
      </div>
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="showForm=true">{{$t('common.add')}}</el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('PushMsg')"></el-button>
    </div>
    <el-row v-else>
      <el-col :span="24">
        <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="showForm=true"></el-button>
        <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
        <el-button type="primary" icon="el-icon-refresh" plain @click="getList('PushMsg')"></el-button>
      </el-col>
      <el-col :span="24">
        <div class="control-search mtb20">
          <el-input :placeholder="$t('pushMsg.search')" clearable v-model="username" class="search">
            <el-button slot="append" icon="el-icon-search" @click="getList('PushMsg', {username})"></el-button>
          </el-input>
        </div>
      </el-col>
    </el-row>

    <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['del']" @row-control-click="onRcc" @page-change="onPageChange('PushMsg', $event)" @selection-change="delIds=$event.map(({push_id})=>push_id)">
      <template #push_platform="{row}">{{$t('pushMsg.ppt'+row.push_platform)}}</template>
      <template #push_type="{row}">{{$t('pushMsg.pt'+row.push_type)}}</template>
    </ProTable>

    <el-dialog :title="$t('pushMsg.title')" :visible.sync="showForm" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="reset('editForm')">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules">
        <el-form-item :label="$t('pushMsg.push_type')">
          <div class="df">
            <el-select class="f1" v-model="elseForm.push_type">
              <el-option v-for="i in 2" :key="i" :label="$t('pushMsg.pt'+i)" :value="i*1"></el-option>
            </el-select>
          </div>
        </el-form-item>
        <el-form-item :label="$t('pushMsg.user_id')" v-if="elseForm.push_type===1">
          <div class="df">
            <el-select class="f1" v-model="elseForm.user_id">
              <el-option v-for="(item, index) in userOptions" :key="index" :label="item.user_name" :value="item.user_id"></el-option>
            </el-select>
          </div>
        </el-form-item>
      </ProForm>
      <span slot="footer" class="dialog-footer">
        <el-button @click="showForm = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { sendPush, delPushMsg, getUser } from '@/api'
import { push } from '@/form'
import paging from '@/mixins/paging'
export default {
  name: 'SystemPush',
  mixins: [paging],
  data() {
    return {
      showForm: false,
      isLoadSub: false,
      username: '',
      EFC: push,
      delIds: [],
      echo: {},
      elseForm: {
        push_type: 1,
        user_id: ''
      },
      userOptions: {},
      rules: {
        push_title: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        push_content: [{required: true,message: this.$t('common.required'),trigger: "blur"}]
      },
      column: {
        push_title: 'pushMsg.push_title',
        push_content: 'pushMsg.push_content',
        //push_platform: 'pushMsg.push_platform',
        push_type: 'pushMsg.push_type',
        user_name: 'pushMsg.user_id',
        username: 'coupon.admin'
      }
    }
  },
  created() {
    this.getList('PushMsg')
    this.getUser()
  },
  watch: {
    username(val) {
      if (!val) {
        this.getList('pushMsg')
      }
    },
    'elseForm.push_type'(val) {
      if (val===2) {
        this.elseForm.user_id = ''
      }
    }
  },
  methods: {
    // 获取所以用户
    async getUser() {
      const {results} = await getUser()
      this.userOptions = results
    },
    // 批量删除
    async del({push_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delPushMsg({push_id: push_id ? [push_id] : this.delIds})
          if (push_id) {
            this.list = this.list.filter(item => item.push_id !== push_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.push_id))
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
        const {push_type, user_id} = this.elseForm
        if (push_type===1 && !user_id) return this.$message({message: this.$t('pushMsg.userErr'), type: 'warning'})
        try {
          this.isLoadSub = true
          await sendPush(Object.assign({admin_id: this.$store.state.user.detail.admin_id},data, this.elseForm))
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.showForm = false
          this.getList('PushMsg')
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