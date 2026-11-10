<template>
  <div class="app-container">
    <div class="mb20" v-if="device==='desktop'">
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true">{{$t('common.add')}}</el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('Banner')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Banner')"></el-button>
    </div>
    <div class="mb20" v-else>
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="show=true"></el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
      <el-popover class="mlr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('Banner')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="reset('searchForm', 'Banner')"></el-button>
    </div>

    <!--  -->
    <ProTable index selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('Banner', $event)" @selection-change="delIds=$event.map(({banner_id})=>banner_id)">
      <template #banner_img="{row}"><ProImage :src="row.banner_img || ''"></ProImage></template>
      <template #banner_status="{row}"><el-tag :type="row.banner_status ? '' : 'danger'">{{$t(statusTxt[row.banner_status])}}</el-tag></template>
    </ProTable>

    <!--  -->
    <el-dialog :title="$t(`banner.${this.echo.banner_id ? 'editBanner' : 'addBanner'}`)" :visible.sync="show" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="onFormClose">
      <ProForm ref="editForm" :echo="echo" :config="EFC" :rules="rules"></ProForm>
      <span slot="footer" class="dialog-footer">
        <el-button @click="show = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { editBanner, delBanner } from '@/api';
import { banner } from '@/form';
import paging from '@/mixins/paging'
export default {
  name: 'BannerList',
  mixins: [paging],
  data() {
    return {
      SFC: banner.SFC,
      EFC: banner.EFC,
      show: false,
      delIds: [],
      column: {
        banner_img: 'common.image',
        banner_title: 'banner.zh_title',
        banner_url: 'banner.url',
        banner_status: 'common.status'
      },
      echo: {},
      isLoadSub: false,
      rules: {
        banner_img: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
        banner_title: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
      }
    }
  },
  created() {
    this.getList('Banner')
  },
  methods: {
    // 关闭表单
    onFormClose() {
      this.reset('editForm')
      // this.$set(this.EFC.formItems[3].item.attr, 'disabled', false)
    },
    // 编辑
    edit({banner_id, banner_img, banner_title, banner_url, banner_status}) {
      this.echo = {banner_id, banner_img, banner_title, banner_url, banner_status}
      this.show = true
    },
    // 删除
    async del({banner_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delBanner({banner_id: banner_id ? [banner_id] : this.delIds})
          if (banner_id) {
            this.list = this.list.filter(item => item.banner_id !== banner_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.banner_id))
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
        const params = this.echo.banner_id ? data : {banner_id: '', ...data}
        try {
          this.isLoadSub = true
          await editBanner(params)
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.show = false
          this.getList('Banner')
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