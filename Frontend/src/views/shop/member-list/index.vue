<template>
  <div class="app-container">
    <!-- <el-card class="box-card">
      <div slot="header" class="clearfix">
        <span>{{$t('level.set')}}</span>
        <el-button style="float: right;" type="primary" :loading="isLoadSub" @click="submit" v-if="nodePower.includes('edit')">{{$t('common.save')}}</el-button>
      </div>
      <el-row :gutter="20" v-loading="isLoading">
        <el-col :span="device==='desktop' ? 8 : 24" v-for="(item, li) in list" :key="li">
          <el-card class="mb20">
            <div slot="header">
              <div class="tag">{{ li+1 }}</div>
            </div>
            <el-form label-position="top">
              <template v-for="(val, key, index) in item">
                <el-form-item :key="index" :label="$t(column[key])" v-if="key!=='member_id'">
                  <el-input :placeholder="$t('common.enter')" v-model="list[li][key]" v-if="['member_name', 'en_member_name'].includes(key)"></el-input>
                  <div class="df" v-else-if="['member_start', 'member_end'].includes(key)"><el-input-number class="f1" :min="0" v-model="list[li][key]"></el-input-number></div>
                  <div class="df" v-else-if="key==='member_status'"><el-select class="f1" v-model="list[li].member_status"><el-option v-for="item in statusOptions" :key="item.value" :label="$t(item.label)" :value="item.value"></el-option></el-select></div>
                </el-form-item>
              </template>
            </el-form>
          </el-card>
        </el-col>
      </el-row>
    </el-card> -->
    <div class="mb20">
      <el-button type="success" icon="el-icon-plus" v-if="device==='desktop' && nodePower.includes('add')" @click="show=true">{{$t('common.add')}}</el-button>
      <el-button type="success" icon="el-icon-plus" v-if="device==='mobile' && nodePower.includes('add')" @click="show=true"></el-button>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('Level')"></el-button>
    </div>
    <!--  -->
    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('Level', $event)" @selection-change="delIds=$event.map(({member_id})=>member_id)">
      <template #member_img="{row}"><ProImage :src="row.member_img || ''"></ProImage></template>
      <template #member_status="{row}"><el-tag :type="row.member_status ? '' : 'danger'">{{$t(statusTxt[row.member_status])}}</el-tag></template>
    </ProTable>

    <!--  -->
    <el-dialog :title="$t(`level.${this.echo.member_id ? 'editLevel' : 'addLevel'}`)" :visible.sync="show" :width="device==='desktop' ? '900px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules"></ProForm>
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { addLevel, editLevel, delLevel } from '@/api'
import { level } from '@/form'
import paging from '@/mixins/paging'
export default {
  name: 'MemberList',
  mixins: [paging],
  data() {
    return {
      EFC: level,
      column: {
        member_img: 'level.img',
        member_name: 'level.zh_name',
        en_member_name: 'level.en_name',
        member_start: 'level.start',
        member_end: 'level.end',
        login_bonus_coin: 'level.loginBonusCoin',
        login_bonus_diamond: 'level.loginBonusDiamond',
        member_status: 'common.status'
      },
      isLoadSub: false,
      show: false,
      echo: {},
      delIds: [],
      rules: {
        member_img: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        member_name: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        en_member_name: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
      }
    }
  },
  created() {
    this.getList('Level')
  },
  methods: {
    // 获取等级列表
    // async getList() {
    //   this.list = new Array(3).fill('seat').map(()=>this.$lodash.cloneDeep({member_id: '', member_name: '', en_member_name:'', member_start: 0, member_end: 0, member_status: 1}))
    //   try {
    //     this.isLoading = true
    //     const {results} = await getLevel()
    //     let newList = results.splice(0,3)
    //     newList.forEach(({created_at, updated_at, ...data}, index) => {
    //       this.$set(this.list, index, data)
    //     });
    //   } catch (error) {

    //   } finally {
    //     this.isLoading = false
    //   }
    // },
    // 提交
    // async submit() {
    //   try {
    //     this.isLoadSub = true
    //     await editLevel(this.list)
    //     this.$message({message: this.$t('common.success'),type: 'success'})
    //     this.show = false
    //     this.getList()
    //   } catch (error) {
    //     console.log(error);
    //   } finally {
    //     this.isLoadSub = false
    //   }
    // }
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
    },
    // 编辑
    edit({member_id, member_img, member_name, en_member_name, member_start, member_end, login_bonus_coin, login_bonus_diamond, member_status}) {
      this.$set(this.EFC.formItems[7].item.attr, 'disabled', false)
      this.echo = {member_id, member_img, member_name, en_member_name, member_start, member_end, login_bonus_coin, login_bonus_diamond, member_status}
      this.show = true
    },
    // 删除
    async del({member_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delLevel({member_id: member_id ? [member_id] : this.delIds})
          if (member_id) {
            this.list = this.list.filter(item => item.member_id !== member_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.member_id))
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
        try {
          this.isLoadSub = true
          if (!this.echo.member_id) {
            await addLevel(data)
          } else {
            await editLevel(data)
          }
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.show = false
          this.getList('Level')
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

<style>
.tag {
  width: 30px;
  height: 30px;
  text-align: center;
  line-height: 30px;
  background-color: #42B983;
  color: #ffffff;
  border-radius: 30px;
}
</style>
