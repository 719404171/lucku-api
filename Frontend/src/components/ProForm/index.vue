<template>
  <el-form ref="form" v-bind="config.attr" :model="form" :rules="rulesInit">
    <template v-if="config.flex">
      <div v-for="(row, index) in config.formItems" :key="index">
        <el-row v-if="config.titles"
          ><el-col
            ><h4>{{ config.titles[index] }}</h4></el-col
          ></el-row
        >
        <div :class="{ 'has-title': config.titles }">
          <el-row :gutter="20">
            <el-col
              v-for="(formItem, index) in row"
              :key="index"
              :span="device === 'desktop' ? 24 / row.length : 24"
            >
              <el-form-item
                v-bind="formItem.attr"
                :label="$t(formItem.attr.label)"
              >
                <div style="display: flex; align-items: center">
                  <component
                    class="f1"
                    :is="formItem.name"
                    v-model.trim="form[formItem.attr.prop]"
                    :config="formItem.item || {}"
                    v-on="(formItem.item && formItem.item.listeners) || {}"
                  ></component>
                  <div class="unit" v-if="formItem.unit">
                    {{ formItem.unit }}
                  </div>
                  <!-- <el-tooltip style="margin-left:10px" v-if="formItem.desc" v-bind="formItem.desc">
                  <i style="font-size: 18px;color: #999;" class="el-icon-question"></i>
                </el-tooltip> -->
                  <i
                    style="font-size: 18px; color: #999; margin-left: 10px"
                    class="el-icon-question"
                    :title="$t(formItem.desc)"
                    v-if="formItem.desc"
                  ></i>
                </div>
                <div class="form-item-tip" v-if="formItem.tip">{{ formItem.tip }}</div>
              </el-form-item>
            </el-col>
          </el-row>
        </div>
      </div>
      <div :class="{ 'has-title': config.titles }" style="margin-top: 20px">
        <el-row
          ><el-col><slot /></el-col
        ></el-row>
      </div>
    </template>
    <template v-else>
      <el-form-item
        v-for="(formItem, index) in config.formItems"
        :key="index"
        v-bind="formItem.attr"
        :label="$t(formItem.attr.label)"
      >
        <div style="display: flex; align-items: center">
          <component
            :is="formItem.name"
            v-model.trim="form[formItem.attr.prop]"
            :config="formItem.item || {}"
            v-on="(formItem.item && formItem.item.listeners) || {}"
          ></component>
          <div class="unit" v-if="formItem.unit">{{ formItem.unit }}</div>
          <i
            style="font-size: 18px; margin-left: 10px; color: #999"
            class="el-icon-question"
            :title="$t(formItem.desc)"
            v-if="formItem.desc"
          ></i>
        </div>
        <div class="form-item-tip" v-if="formItem.tip">{{ formItem.tip }}</div>
      </el-form-item>
      <slot />
    </template>
  </el-form>
</template>

<script>
import Tinymce from "@/components/Tinymce/index.vue";
import QuillEditor from "@/components/QuillEditor/index.vue";
import Upload from "@/components/Upload/index.vue";
import UploadMultiple from "@/components/Upload/multiple.vue";
import BaseInput from "./baseInput.vue";
import BaseInputNumber from "./baseInputNumber.vue";
import BaseInputRange from "./baseInputRange.vue";
import BaseSelect from "./baseSelect.vue";
import BaseRadio from "./baseRadio.vue";
import BaseCascader from "./baseCascader.vue";
import BaseTimePicker from "./baseTimePicker.vue";
import BaseDatePicker from "./baseDatePicker.vue";
import BaseSwitch from "./baseSwitch.vue";
import BaseSlider from "./baseSlider.vue";
import BaseColorPicker from "./baseColorPicker.vue";
import BaseCheckbox from "./baseCheckbox.vue";
import BaseMemberLevelLimit from "./baseMemberLevelLimit.vue";
import BaseRate from "./baseRate.vue";
import BaseFile from "./baseFile.vue";
import FileSelectBtn from "./FileSelectBtn.vue";
import { formatTime } from "@/utils";

export default {
  name: "ProForm",
  components: {
    Tinymce,
    QuillEditor,
    Upload,
    UploadMultiple,
    BaseInput,
    BaseInputNumber,
    BaseInputRange,
    BaseSelect,
    BaseRadio,
    BaseCascader,
    BaseTimePicker,
    BaseDatePicker,
    BaseSwitch,
    BaseSlider,
    BaseColorPicker,
    BaseCheckbox,
    BaseMemberLevelLimit,
    BaseRate,
    BaseFile,
    FileSelectBtn,
  },
  props: {
    config: {
      type: Object,
      default: () => ({}),
    },
    rules: Object,
    echo: {
      type: Object,
      default: () => ({}),
    },
    refresh: {
      type: Number,
      default: 0,
    },
    msg: String,
  },
  data() {
    return {
      form: {},
      rulesInit: {},
    };
  },
  created() {
    this.init();
  },
  methods: {
    init() {
      const form = {};
      this.config.formItems.flat().forEach((formItem) => {
        form[formItem.attr.prop] = formItem.value;
      });
      this.form = form;
      Object.assign(this.form, this.$lodash.cloneDeep(this.echo));
      this.rulesInit = this.config.rules ? this.config.rules : this.rules;
    },
    submit(cb) {
      this.$refs.form.validate((valid) => {
        if (!valid) {
          if (this.msg) this.$message({ message: this.msg, type: "warning" });
          return;
        }
        cb && cb(this.form);
      });
    },
    reset(cb) {
      this.init();
      this.$refs.form.resetFields();
      cb && cb();
    },
  },
  computed: {
    span() {
      return this.config.flex
        ? Math.max(...this.config.formItems.map((item) => item.length))
        : 0;
    },
  },
  watch: {
    echo: {
      handler (val){
        Object.keys(val).length
          ? Object.assign(this.form, this.$lodash.cloneDeep(val))
          : this.reset();
      },
      deep: true,
    }
  },
};
</script>

<style lang="scss" scoped>
.has-title {
  padding-left: 15px;
}
.unit {
  margin-left: 10px;
  font-size: 12px;
  color: #aaa;
}
.form-item-tip {
  margin-top: 6px;
  font-size: 12px;
  color: #909399;
  line-height: 1.4;
}
</style>
