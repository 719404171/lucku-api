// const api = Object.assign({})
import api from '@/api'

export default {
  data() {
    return {
      list: [],
      searchForm: {},
      page: {
        current: 1,
        size: 10,
        total: 0
      },
      isLoading: false
    }
  },
  methods: {
    // 搜索
    setSearchForm(e) {
      return Object.assign(this.searchForm, e)
    },
    onSearch(name, elseParams={}) {
      // 部分頁面（如卡牌訂單/訂單列表/發貨訂單）沒有 ProForm 搜索表單，
      // 只有頂部關鍵字輸入框，此時直接用傳入參數查詢，避免 $refs.searchForm 為 undefined 報錯
      if (!this.$refs.searchForm) {
        this.getList(name, this.setSearchForm(elseParams))
        return
      }
      this.$refs.searchForm.submit(({delivery_area=[], ...params}={}) => {
        delivery_area = delivery_area.join(',')
        this.getList(name, this.setSearchForm(delivery_area.length ? {delivery_area, ...params, ...elseParams} : {...params, ...elseParams}))
      })
    },
    // 重置
    reset(refName, name, params={}) {
      this.$refs[refName].reset(() => {
        if (refName === 'editForm') {
          this.show = false
          this.echo = {}
        } else {
          this.searchForm = {}
          this.getList(name, params)
        }
      })
    },
    // 获取列表
    async getList(name, {page=1, limit=10, ...elseParams}={}) {
      try {
        this.isLoading = true
        const {results: {
          data, count,
          day_money, month_money, total_money //订单列表
        }} = await api['req'+name]({page, limit, ...elseParams})
        if (this.sales) {
          this.sales = [day_money, month_money, total_money]
        }
        this.list = data
        this.page.current = page
        this.page.size = limit
        this.page.total = count
        this.isLoading = false
      } catch (error) {
        console.log(error);
        this.isLoading = false
      }
    },
    // 分页
    onPageChange(name, { e, type }, elseParams={}) {
      type === "size" ? this.getList(name, Object.assign(elseParams, { limit: e }, this.searchForm)) : this.getList(name, Object.assign(elseParams,{ page: e, limit: this.page.size },this.searchForm));
    },
  }
}