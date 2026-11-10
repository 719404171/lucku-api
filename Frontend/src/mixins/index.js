export default {
  data() {
    return {
      cascaderProps: {
        lazy: true,
        lazyLoad: this.courseTeacher,
        checkStrictly: true
      },
      statusTxt: ['common.disabled', 'common.normal'],
      statusOptions: [
        { label: 'common.normal', value: 1 },
        { label: 'common.disabled', value: 0 }
      ]
    }
  },
  computed: {
    device() {
      return this.$store.state.app.device
    },
    nodePower() {
      return this.$route.meta.power && this.$route.meta.power.map(({type})=>type)
    }
  },
  methods: {
    addZero(n) {
      return n < 10 ? '0'+n : n
    },
    // 异步弹窗
    asyncMsg(asyncFunc, message=this.$t('common.delConfirm')) {
      this.$msgbox({
        title: this.$t('common.tips'),
        message,
        type: 'warning',
        center: this.device==='mobile',
        customClass: this.device==='mobile' ? 'mmw' : '',
        closeOnClickModal: false,
        showCancelButton: true,
        confirmButtonText: this.$t('common.confirm'),
        cancelButtonText: this.$t('common.cancel'),
        beforeClose: async (action, instance, done) => {
          if (action === 'confirm') {
            asyncFunc(instance, done)
          } else {
            done();
          }
        }
      }).catch(e=>e)
    },
    // 异步级联
    // async getCascaderOption(node, resolve) {
    //   const { level, data = { id: 0 } } = node;
    //   let result = null
    //   if (!data.id) {
    //     result = await req()
    //   } else {
    //     result = await reqSub()
    //   }
    //   const nodes = result.content.map(({value, label}) => ({value,label,id: value,leaf: level >= 1}));
    //   resolve(nodes);
    // }
  }
}