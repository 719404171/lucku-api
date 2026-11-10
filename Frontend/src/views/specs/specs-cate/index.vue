<template>
<div class="app-container">
  <!--  -->
  <div class="mb20 df" v-if="device==='desktop'">
    <div class="control-search">
      <el-input :placeholder="$t('specs.specs_sort_namePh')" clearable v-model="specs_sort_name" class="search">
        <el-button slot="append" icon="el-icon-search" @click="getList('Specs', {specs_sort_name})"></el-button>
      </el-input>
    </div>
    <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="showForm=true">{{$t('common.add')}}</el-button>
    <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del">{{$t('common.deletes')}}</el-button>
    <el-button type="primary" icon="el-icon-refresh" plain @click="getList('Specs')"></el-button>
  </div>
  <el-row v-else>
    <el-col :span="24">
      <el-button type="success" icon="el-icon-plus" v-if="nodePower.includes('add')" @click="showForm=true"></el-button>
      <el-button type="danger" icon="el-icon-delete" v-if="nodePower.includes('del')" :disabled="!!!delIds.length" @click="del"></el-button>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('Specs')"></el-button>
    </el-col>
    <el-col :span="24">
      <div class="control-search mtb20">
        <el-input :placeholder="$t('specs.specs_sort_namePh')" clearable v-model="specs_sort_name" class="search">
          <el-button slot="append" icon="el-icon-search" @click="getList('Specs', {specs_sort_name})"></el-button>
        </el-input>
      </div>
    </el-col>
  </el-row>


  <!--  -->
  <ProTable index expand selection border :is-loading="isLoading" :data="list" :page="page" :column="column" :rowControl="['edit', 'del']" @row-control-click="onRcc" @page-change="onPageChange('Specs', $event)" @selection-change="delIds=$event.map(({specs_sort_id})=>specs_sort_id)">
    <template #expand="{row}">
      <div class="ptb20" v-for="lab in row.specs_key" :key="lab.specs_key_id">
        <div class="lab">{{ lab[(language==='en' ? 'en_' : '') + 'specs_key_name']}}</div>
        <div class="plr10">
          <el-tag class="mlr10" type="primary" v-for="val in lab.specs_value" :key="val.specs_value_id">{{ language==='zh' ? val.specs_value_name : val.en_specs_value_name }}</el-tag>
        </div>
      </div>
    </template>
    <template #status="{row}">
      <el-tag :type="row.status ? '' : 'danger'">{{$t(statusTxt[row.status])}}</el-tag>
    </template>
  </ProTable>
  <!--  -->
  <el-dialog :title="$t(`specs.${specsForm.specs_sort_id ? 'editSpecs' : 'addSpecs'}`)" :visible.sync="showForm" :width="device==='desktop' ? '800px' : '100%'" :close-on-click-modal="false" @closed="reset">
    <el-form :model="specsForm" :rules="rules" ref="specsForm" label-position="top">
      <el-form-item :label="$t('specs.zh_specs_sort_name')" prop="specs_sort_name">
        <el-input :placeholder="$t('specs.specs_sort_namePh')" v-model="specsForm.specs_sort_name"></el-input>
      </el-form-item>
      <el-form-item :label="$t('specs.en_specs_sort_name')" prop="en_specs_sort_name">
        <el-input :placeholder="$t('specs.specs_sort_namePh')" v-model="specsForm.en_specs_sort_name"></el-input>
      </el-form-item>
      <el-form-item :label="$t('common.status')" prop="status">
        <div class="df">
          <el-select class="f1" v-model="specsForm.status" :placeholder="$t('common.selectPh')" :disabled="!specsForm.specs_sort_id">
            <el-option v-for="item in statusOptions" :key="item.value" :label="$t(item.label)" :value="item.value"></el-option>
          </el-select>
        </div>
      </el-form-item>
    </el-form>

    <!--  -->
    <div class="mb10">
      <el-button type="primary" @click="addSpecs">{{ $t('specs.addSpec') }}</el-button>
    </div>
    <div class="card" v-for="(key, index) in specsForm.specs_key" :key="index">
      <div class="header">
        <el-row :gutter="device==='desktop' ? 10 : 5">
          <el-col :span="device==='desktop' ? 11 : 10">
            <el-input :placeholder="$t('specs.zh_specs_key_name')" v-model="key.specs_key_name"></el-input>
          </el-col>
          <el-col :span="device==='desktop' ? 11 : 10">
            <el-input :placeholder="$t('specs.en_specs_key_name')" v-model="key.en_specs_key_name"></el-input>
          </el-col>
          <el-col :span="device==='desktop' ? 2 : 4">
            <el-button type="danger" plain icon="el-icon-delete" @click="delSpecs(index)"></el-button>
          </el-col>
        </el-row>
      </div>
      <div class="body">
        <el-tag class="el-tag" :key="valIndex" v-for="(value, valIndex) in key.specs_value" closable :disable-transitions="false" @close="delSpecsValue(valIndex, index)">{{ value[language==='zh' ? 'specs_value_name' : 'en_specs_value_name']}}</el-tag>
        <el-button class="button-new-tag" @click="showSpecsValForm(index)">{{ $t('specs.addValue') }}</el-button>
      </div>
    </div>
    <span slot="footer" class="dialog-footer">
      <el-button @click="showForm = false">{{$t('common.cancel')}}</el-button>
      <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
    </span>
  </el-dialog>
  <!--  -->
  <ProLanginps :show.sync="inputSpecsVisible" @submit="addSpecsValue" @close="curSpecsIndex = ''"></ProLanginps>
</div>
</template>

<script>
import paging from '@/mixins/paging'
import { mapGetters } from 'vuex'
import { specsTemp, specsCateTemp } from './temp'
import { addSpecs, editSpecs, delSpecs } from '@/api'
export default {
  name: 'SpecsCate',
  mixins: [paging],
  data() {
    return {
      specs_sort_name: '',
      delIds: [],
      column: {
        specs_sort_name: 'specs.zh_specs_sort_name',
        en_specs_sort_name: 'specs.en_specs_sort_name',
        status: 'common.status'
      },
      specsForm: {
        specs_sort_name: '',
        en_specs_sort_name: '',
        specs_key: [],
        status: 1
      },
      curSpecsIndex: '',
      inputSpecsVisible: false,
      isLoadSub: false,
      showForm: false,
      echo: {},
      rules: {
        specs_sort_name: [{ required: true, message: this.$t('common.required'), trigger: "blur" }],
        en_specs_sort_name: [{ required: true, message: this.$t('common.required'), trigger: "blur" }]
      }
    }
  },
  created() {
    this.addSpecs()
    this.getList('Specs')
  },
  computed: {
    ...mapGetters(['language'])
  },
  watch: {
    specs_sort_name(val) {
      if (val) return
      this.getList('Specs')
    },
  },
  methods: {
    // 规格表单处理
    showSpecsValForm(index){
      this.curSpecsIndex=index
      this.inputSpecsVisible = true
    },
    specsValReset() {
      this.curSpecsIndex = ''
      this.$refs.specsValForm.resetFields();
    },
    // 添加规格
    addSpecs() {
      this.specsForm.specs_key.push(this.$lodash.cloneDeep(specsTemp))
    },
    // 删除规格
    delSpecs(index) {
      this.specsForm.specs_key.splice(index, 1)
    },
    // 添加规格值
    addSpecsValue({zh: specs_value_name, en: en_specs_value_name}) {
      this.specsForm.specs_key[this.curSpecsIndex].specs_value.push({specs_value_name, en_specs_value_name})
      this.inputSpecsVisible = false
    },
    // 删除规格值
    delSpecsValue(valIndex, index) {
      this.specsForm.specs_key[index].specs_value.splice(valIndex, 1)
    },
    // 编辑
    edit(row) {
      this.specsForm = this.$lodash.cloneDeep(row)
      this.showForm = true
    },
    // 删除
    async del({specs_sort_id}={}) {
      this.asyncMsg(async (instance, done) => {
        instance.confirmButtonLoading = true;
        try {
          await delSpecs({specs_sort_id: specs_sort_id ? [specs_sort_id] : this.delIds})
          if (specs_sort_id) {
            this.list = this.list.filter(item => item.specs_sort_id !== specs_sort_id)
            this.page.total--
          } else {
            this.list = this.list.filter(item => !this.delIds.includes(item.specs_sort_id))
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
    // 重置
    reset() {
      this.$refs.specsForm.resetFields();
      this.specsForm = this.$lodash.cloneDeep(specsCateTemp)
    },
    // 操作
    onRcc({scope: {row}, type}) {
      this[type](row)
    },
    // 提交表单
    submit() {
      this.$refs.specsForm.validate(async valid => {
        if (!valid) return
        if (this.specsForm.specs_key.some(item => !item.specs_key_name || !item.en_specs_key_name)) return this.$message({message: this.$t('specs.noKey'), type: 'error'})
        if (this.specsForm.specs_key.some(item => !item.specs_value.length)) return this.$message({message: this.$t('specs.noValue'), type: 'error'})
        try {
          this.isLoadSub = true
          if (this.specsForm.specs_sort_id) {
            await editSpecs(this.specsForm)
          } else {
            await addSpecs(this.specsForm)
          }
          this.$message({message: this.$t('common.success'), type: 'success'})
          this.showForm = false
          this.getList('Specs')
        } catch (error) {

        } finally {
          this.isLoadSub = false
        }
      })
    }
  }
}
</script>

<style lang="scss">
  .lab {
    padding-left: 20px;
    margin-bottom: 20px;
    font-size: 26rpx;
    font-weight: bold;
  }
  .card {
    margin-bottom: 15px;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    overflow: hidden;
    .header {
      padding: 10px;
      background-color: #F9FAFB;
      border-bottom: 1px solid #E5E7EB;
    }
    .body {
      padding: 10px;
    }
  }
  .el-tag {
    margin: 5px;
  }
  .button-new-tag {
    margin-left: 5px;
  }
</style>
