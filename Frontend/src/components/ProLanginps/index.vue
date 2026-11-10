<template>
  <div>
    <el-dialog :width="device==='desktop' ? '400px' : '100%'" :title="$t('common.enter')" :visible.sync="dialogShow" append-to-body @close="reset">
      <el-form ref="form" :model="form" :rules="rules">
        <el-form-item prop="zh">
          <el-input :placeholder="$t('common.zh')" v-model="form.zh"></el-input>
        </el-form-item>
        <el-form-item prop="en">
          <el-input :placeholder="$t('common.en')" v-model="form.en"></el-input>
        </el-form-item>
      </el-form>
      <span slot="footer" class="dialog-footer">
        <el-button @click="dialogShow = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
export default {
  props: {
    show: Boolean,
    echo:Object
  },
  model: {
    prop: 'value',
    event: 'change'
  },
  data() {
    return {
      showUpload: false,
      form: {
        zh: '',
        en: ''
      },
      rules: {
        zh: [{ required: true, message: this.$t('common.required'), trigger: "blur" }],
        en: [{ required: true, message: this.$t('common.required'), trigger: "blur" }]
      }
    }
  },
  computed: {
    dialogShow: {
      get() {
        return this.show
      },
      set(val) {
        this.$emit('update:show', val)
      }
    }
  },
  watch: {
    echo: {
      handler(val) {
        this.form = this.$lodash.cloneDeep(val)
      },
      deep: true
    }
  },
  methods: {
    reset() {
      this.$refs.form.resetFields();
      this.form = {zh: '', en: ''}
      this.$emit('close')
    },
    submit() {
      this.$refs.form.validate(async valid => {
        if (!valid) return
        this.$emit('submit', this.form)
        this.dialogShow = false
      })
    }
  }
}
</script>