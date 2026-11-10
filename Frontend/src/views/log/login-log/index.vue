<template>
  <div class="app-container">
    <div class="mb20">
      <div class="control-search">
        <el-input :placeholder="$t('coupon.admin')" clearable v-model="login_admin_name" class="search">
          <el-button slot="append" icon="el-icon-search" @click="getList('LoginLog', {login_admin_name})"></el-button>
        </el-input>
      </div>
    </div>

    <!--  -->
    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column" @page-change="onPageChange('LoginLog', $event)">
      <template #login_lng="{row}">{{row.login_lng}}, {{row.login_lat}}</template>
      <template #login_nation="{row}">{{row.login_nation}}, {{row.login_city}}, {{row.login_region}}</template>
    </ProTable>

  </div>
</template>

<script>
import paging from '@/mixins/paging'
export default {
  name: 'LoginLog',
  mixins: [paging],
  data() {
    return {
      login_admin_name: '',
      column: {
        login_admin_name: 'coupon.admin',
        login_ip: 'log.ip',
        login_nation: 'log.location',
        login_lng: 'log.lal',
        created_at: 'log.time'
      }
    }
  },
  created() {
    this.getList('LoginLog')
  },
  watch: {
    login_admin_name(val) {
      if (!val) {
        this.getList('LoginLog')
      }
    }
  },
}
</script>