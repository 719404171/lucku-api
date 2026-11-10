<template>
<div class="pro-table">
  <el-table :border="border" :data="data" :row-class-name="tableRowClassName" v-loading="isLoading" @selection-change="handleSelectionChange" @expand-change="handleExpandChange" @sort-change="handleSortChange">
    <el-empty slot="empty" :description="$t('common.nodata')"></el-empty>
    <el-table-column type="selection" v-if="selection"></el-table-column>
    <el-table-column label="#" type="index" v-if="index"></el-table-column>
    <el-table-column type="expand" v-if="expand">
      <template slot-scope="{ row, column, $index }">
        <slot :row="row" :column="column" :index="$index" name="expand"></slot>
      </template>
    </el-table-column>
    <el-table-column v-for="(value, key) in column" :key="value" :label="$t(value)" :prop="key" v-bind="columnAttr[key] || {}">
      <template slot-scope="{ row, column, $index }">
        <slot :row="row" :column="column" :index="$index" :name="key"><span style="white-space: nowrap;" :title="row[key] ? row[key] : ''">{{row[key] === null ? '--' : row[key]}}</span></slot>
      </template>
    </el-table-column>
    <el-table-column fixed="right" :label="$t('common.control')" :min-width="controlWidth" v-bind="columnAttr.control" v-if="permControl.length || $scopedSlots.control">
      <template slot-scope="scope">
        <div class="df fwn">
          <slot name="control" :scope="scope"></slot>
          <template v-for="item in permControl">
            <el-link class="row-control-item" :class="$store.state.app.size" :type="controlAttr[item].type" :underline="false" :icon="btnico ? controlAttr[item].icon : ''" :key="item" @click="onRowControl(scope, item)" v-if="rowControlType==='link'">{{ $t(controlText[item].text) }}</el-link>
            <el-button :type="controlAttr[item].type" :icon="btnico ? controlAttr[item].icon : ''" :key="item" @click="onRowControl(scope, item)" v-if="rowControlType==='button'">{{ $t(controlText[item].text) }}</el-button>
            <!-- <el-popconfirm
                style="margin-left: 10px"
                :key="item"
                :title="$t('common.delConfirm')"
                :confirm-button-text="$t('common.submit')"
                :cancel-button-text="$t('common.cancel')"
                @confirm="onRowControl(scope, 'del')"
              >
                <el-button type="text" icon="el-icon-delete" slot="reference">{{$t('common.del')}}</el-button>
              </el-popconfirm> -->
          </template>
          <el-dropdown style="margin-left:10px" v-if="hideControl.length" placement="bottom" @command="onRowControl(scope, $event)">
            <el-link :class="$store.state.app.size" type="primary" :underline="false" v-if="rowControlType==='link'"><i class="el-icon-more"></i></el-link>
            <el-button type="primary" icon="el-icon-more" v-else></el-button>
            <el-dropdown-menu slot="dropdown">
              <el-dropdown-item v-for="(item, index) in hideControl" :key="index" :command="index">{{item}}</el-dropdown-item>
            </el-dropdown-menu>
          </el-dropdown>
        </div>
      </template>
    </el-table-column>
  </el-table>
  <!-- 分页 -->
  <div class="pagination-box" v-if="Object.keys(page).length">
    <el-pagination v-if="page" @size-change="handlePageChange($event, 'size')" @current-change="handlePageChange($event, 'current')" :current-page.sync="page.current" :page-sizes="page.sizes || [10, 20, 30, 40, 50, 80]" :page-size.sync="page.size" :layout="device==='desktop' ? 'total, sizes, prev, pager, next, jumper' : 'prev, pager, next'" :total="page.total">
    </el-pagination>
  </div>
</div>
</template>

<script>
export default {
  name: "ProTable",
  props: {
    border: Boolean,
    expand: Boolean,
    selection: Boolean,
    index: Boolean,
    btnico: Boolean,
    isLoading: Boolean,
    data: {
      type: Array,
      default: () => [],
    },
    column: {
      type: Object,
      default: () => ({}),
    },
    columnAttr: {
      type: Object,
      default: () => ({}),
    },
    controlWidth: {
      type: String,
      default: '120px'
    },
    rowControl: {
      type: Array,
      default: () => [],
    },
    rowControlType: {
      type: String,
      default: 'link'
    },
    hideControl: {
      type: Array,
      default: () => [],
    },
    page: {
      type: Object,
      default: () => ({}),
    },
    tableRowClassName: {
      type: Function,
      default: () => {}
    }
  },
  data() {
    return {
      controlAttr: {
        del: {
          icon: "el-icon-delete",
          type: "danger",
        },
        edit: {
          icon: "el-icon-edit",
          type: "warning",
        },
        view: {
          icon: "el-icon-view",
          type: "primary",
        },
        spec: {
          icon: '',
          type: 'success'
        },
        password: {
          icon: '',
          type: 'warning'
        }
      },
      controlText: {
        del: {
          text: 'common.del'
        },
        edit: {
          text: 'common.edit',
        },
        view: {
          text: 'common.view',
        },
        spec: {
          text: 'common.spec'
        },
        password: {
          text: 'login.password'
        }
      }
    };
  },
  computed: {
    permControl() {
      const power = this.$route.meta.power.map(({type})=> type)
      return this.rowControl.filter(ri => power.includes(ri))
    }
  },
  methods: {
    /**
     *  多选框操作
     *  @rows {arry} 选中行数据 
     */
    handleSelectionChange(rows) {
      this.$emit("selection-change", rows);
    },
    /**
     *  行内按钮操作
     *  @scope {object} 行数据
     *  @type {string}  <edit/del/power/detail>按钮标识
     */
    async onRowControl(scope, item) {
      // if (item === 'del') {
      //   const info = await this.$confirm(this.$t('common.delConfirm'),this.$t('common.tips'), {type: "warning"}).catch((err) => err);
      //   if (info === "cancel") return;
      // }
      this.$emit("row-control-click", {scope,type: item});
    },
    /**
     *  行内展开操作
     *  @row {object} 当前点击的行
     *  @expanded {array} 已经展开的行
     */
    handleExpandChange(row, expanded) {
      this.$emit("expand-change", {row,expanded});
    },
    /**
     *  分页操作
     *  @e {number} 每页条数/当前页
     *  @type {string} <size|current>改变类型
     */
    handlePageChange(e, type) {
      this.$emit("page-change", {e,type});
    },
    /**
     *  排序操作
     *  @sortInfo {object} { prop, order } 排序信息
     */
    handleSortChange(sortInfo) {
      this.$emit("sort-change", sortInfo);
    }
  }
};
</script>

<style lang="scss" scoped>
.pro-table {
  .row-control-item {
    margin: 0 5px;
  }

  .pagination-box {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    height: 100px;
  }
}
</style>

<style>
.default {font-size: 14px;} 
.medium {font-size: 14px;} 
.small {font-size: 13px;} 
.mini {font-size: 12px;}
.el-table .warning-row {
  background: rgba(253, 225, 225, 0.3);
}
</style>
