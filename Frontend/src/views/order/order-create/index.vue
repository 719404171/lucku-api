<template>
  <div class="app-container">
    <!--  -->
    <div class="mb20 df" v-if="device==='desktop'">
      <el-popover class="mr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('OfflineGoods')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference">{{ $t('common.search') }}</el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('OfflineGoods')"></el-button>
      <el-badge :value="totalNum" class="mlr20"><el-button :disabled="!addedProducts.length" @click="showForm=true">{{$t('order.indType1')}}</el-button></el-badge>
    </div>
    <div class="mb20 df" v-else>
      <el-popover class="mr10" placement="bottom" trigger="click">
        <ProForm ref="searchForm" :config="SFC"></ProForm>
        <el-button type="primary" @click="onSearch('OfflineGoods')">{{$t('common.confirm')}}</el-button>
        <el-button type="primary" icon="el-icon-search" slot="reference"></el-button>
      </el-popover>
      <el-button type="primary" icon="el-icon-refresh" plain @click="getList('OfflineGoods')"></el-button>
      <el-badge :value="totalNum" class="mlr20"><el-button icon="el-icon-goods" :disabled="!addedProducts.length" @click="showForm=true"></el-button></el-badge>
    </div>

    <!--  -->
    <ProTable index border :is-loading="isLoading" :data="list" :page="page" :column="column" @page-change="onPageChange('OfflineGoods', $event)">
      <template #product_img="{row}"><ProImage :src="row.product_img || ''"></ProImage></template>
      <template #product_type="{row}">{{ $t(goodsType[row.product_type]) }}</template>
      <template #product_sort_id="{row}">{{ goodsSort[row.product_sort_id] }}</template>
      <template #product_stock="{row}">{{ row.product_type===1 ? row.product_stock : '--' }}</template>
      <template #control="{scope:{row}}">
        <i v-if="row.product_type===1" class="el-icon-circle-plus c-btn" :title="$t('offlineOrder.add')" @click="add_appo(row)"></i>
        <i v-if="row.product_type===2" class="el-icon-time c-btn" :title="$t('offlineOrder.appo')" @click="add_appo(row)"></i>
      </template>
    </ProTable>

    <!--  -->
    <el-dialog :title="$t(curSltRow && curSltRow.product_type===1 ? 'offlineOrder.addTo' : 'offlineOrder.appo')" :close-on-click-modal="false" :width="device==='desktop' ? '800px' : '100%'" :visible.sync="showSpec" @closed="reset">
      <template v-if="curSltRow">
        <el-card>
          <div class="df">
            <ProImage size="100" :src="curSltRow.product_img"></ProImage>
            <div class="f1 ml20">
              <div class="mb10 fs15 fwb">{{curSltRow.product_name}}</div>
              <div class="df jcsb">
                <span>${{((curSltRow.product_type===2 && !curSltRow.is_tostore) || curSltRow.product_type===1 ? (curSltRow.is_specs ? specTarget.price : curSltRow.product_price) : curSltRow.tostore_price) || 0}}</span>
                <span v-if="curSltRow.product_type===1">{{$t('goods.stock')}}：{{(curSltRow.is_specs ? specTarget.stock : curSltRow.product_stock) || 0}}</span>
              </div>
            </div>
          </div>
        </el-card>
        <template v-if="curSltRow.is_specs">
          <div class="df aic"><h4 class="mr10">{{ $t('common.spec') }}</h4><span class="fs12">（{{specTarget.product_specs_value ? `${$t('common.slted')}：${specTarget.product_specs_value.map(item => item[language==='zh' ? 'product_specs_value' : 'en_product_specs_value']).join(',')}` : $t('common.selectPh')}}）</span></div>
          <div v-for="(key, index) in curSltRow.product_specs_key" :key="index">
            <div class="mtb20 fs12">{{key[language==='zh' ? 'product_specs_key' : 'en_product_specs_key']}}</div>
            <div>
              <el-tag class="ml10 mr10 mt5 mb5 cp" size="default" :effect="psvi.includes(val.product_specs_value_id) ? 'dark' : 'plain'" v-for="(val, vi) in key.product_specs_value" :key="vi" @click="psvi.splice(index, 1, val.product_specs_value_id)">{{val[language==='zh' ? 'product_specs_value' : 'en_product_specs_value']}}</el-tag>
            </div>
          </div>
        </template>
        <template v-if="curSltRow.product_type===1">
          <h4>{{$t('offlineOrder.num')}}</h4>
          <el-input-number :min="1" v-model="number"></el-input-number>
        </template>
        <el-form class="mt20" ref="specForm" :model="formData" :rules="rules" label-position="top" v-if="curSltRow.product_type===2">
          <el-form-item :label="$t('pushMsg.user_id')" prop="user_id"><el-select :placeholder="$t('common.selectPh')" v-model="formData.user_id"><el-option v-for="item in userOp" :key="item.user_id" :label="item.user_name" :value="item.user_id"></el-option></el-select></el-form-item>
          <el-form-item :label="$t('order.apoTime')" prop="order_time"><el-date-picker v-model="formData.order_time" type="datetime" :placeholder="$t('common.selectPh')" :picker-options="{disabledDate}" value-format="yyyy-MM-dd HH:mm" format="yyyy-MM-dd HH:mm"></el-date-picker></el-form-item>
          <el-form-item :label="$t('order.delivery_name')" prop="delivery_name"><el-input :placeholder="$t('common.enter')" v-model="formData.delivery_name"></el-input></el-form-item>
          <el-form-item :label="$t('common.email')" prop="delivery_mail"><el-input :placeholder="$t('common.enter')" v-model="formData.delivery_mail"></el-input></el-form-item>
          <el-form-item :label="$t('common.tel')" prop="delivery_phone"><el-input :placeholder="$t('common.enter')" v-model="formData.delivery_phone"></el-input></el-form-item>
          <el-form-item :label="$t('order.order_remark')" prop="order_remark"><el-input :placeholder="$t('common.enter')" v-model="formData.order_remark"></el-input></el-form-item>
          <el-form-item :label="$t('createOrder.orderType')" prop="order_type"><el-select :placeholder="$t('common.selectPh')" v-model="formData.order_type"><el-option v-for="i in 2" :key="i" :label="$t('createOrder.ot'+i)" :value="i*1"></el-option></el-select></el-form-item>
          <el-form-item :label="$t('order.payment_status')" prop="payment_status"><el-select :placeholder="$t('common.selectPh')" v-model="formData.payment_status"><el-option v-for="i in 5" :key="i" :label="$t('order.ps'+i)" :value="i*1"></el-option></el-select></el-form-item>
          <el-form-item :label="$t('order.payment_mode')" prop="payment_mode"><el-select :placeholder="$t('common.selectPh')" v-model="formData.payment_mode"><el-option v-for="i in 5" :key="i" :label="$t('order.pt'+i)" :value="i*1"></el-option></el-select></el-form-item>
          <el-form-item :label="$t('order.payTime')" prop="payment_at"><el-date-picker v-model="formData.payment_at" type="datetime" :placeholder="$t('common.selectPh')" value-format="yyyy-MM-dd HH:mm:ss" format="yyyy-MM-dd HH:mm:ss"></el-date-picker></el-form-item>
          <el-form-item :label="$t('common.created_at')" prop="created_at"><el-date-picker v-model="formData.created_at" type="datetime" :placeholder="$t('common.selectPh')" value-format="yyyy-MM-dd HH:mm:ss" format="yyyy-MM-dd HH:mm:ss"></el-date-picker></el-form-item>
        </el-form>
      </template>
      <span slot="footer" class="dialog-footer">
        <el-button @click="showSpec = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="confirm_add_appo">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>

    <!--  -->
    <el-dialog :title="$t('offlineOrder.offPur')" :close-on-click-modal="false" :width="device==='desktop' ? '800px' : '100%'" :visible.sync="showForm" @closed="reset">
      <h3>{{$t('order.indType1')}}</h3>
      <el-card class="mb20" v-for="(item, index) in addedProducts" :key="item.product_relation_id">
        <div class="df" :class="{fdc:device==='mobile'}">
          <div class="df jcsb">
            <ProImage size="100" :src="item.product_img"></ProImage>
            <span v-if="device==='mobile'">{{$t('offlineOrder.num')}}：<el-input-number size="mini" :min="1" :max="item.product_stock" :value="item.product_number" @change="updateNum($event, item.product_stock, index)"></el-input-number></span>
          </div>
          <div class="f1" :class="{ml20: device==='desktop'}">
            <div class="df jcfe"><i style="color:red" class="el-icon-delete-solid cp" @click="delAdded(index)"></i></div>
            <div class="mtb10 fs15 fwb">{{item.product_name}}</div>
            <div class="mb10 df jcsb">
              <span>{{$t('common.spec')}}：{{item.specs_value || '--'}}</span>
              <span>{{$t('goods.stock')}}：{{item.product_stock}}</span>
            </div>
            <div class="df aic jcsb">
              <span>{{$t('offlineOrder.uniPrice')}}：${{item.product_price}}<span class="line" v-if="![0,10].includes(discount)">${{item.uniPrice}}</span></span>
              <span v-if="device==='desktop'">{{$t('offlineOrder.num')}}：<el-input-number size="mini" :min="1" :max="item.product_stock" :value="item.product_number" @change="updateNum($event, item.product_stock, index)"></el-input-number></span>
              <span>{{$t('offlineOrder.subTotal')}}：${{item.product_money}}<span class="line" v-if="![0,10].includes(discount)">${{item.subTotal}}</span></span>
            </div>
          </div>
        </div>
      </el-card>
      <div class="mb30">{{$t('offlineOrder.total')}}：${{order_money}}<span class="line" v-if="![0,10].includes(discount)">${{original_cost}}</span></div>
      <el-form ref="form" :model="formData" :rules="rules" label-position="top">
        <el-form-item :label="$t('coupon.discount')"><el-input-number :min="0" :max="10" v-model="discount"></el-input-number></el-form-item>
        <el-form-item :label="$t('pushMsg.user_id')" prop="user_id"><el-select :placeholder="$t('common.selectPh')" v-model="formData.user_id"><el-option v-for="item in userOp" :key="item.user_id" :label="item.user_name" :value="item.user_id"></el-option></el-select></el-form-item>
        <el-form-item :label="$t('order.receiving_type')" prop="receiving_type"><el-select :placeholder="$t('common.selectPh')" v-model="formData.receiving_type"><el-option v-for="i in 2" :key="i" :label="$t('order.rt'+i)" :value="i*1"></el-option></el-select></el-form-item>
        <template v-if="formData.receiving_type==1">
          <el-form-item :label="$t('order.delivery_area')" prop=""><el-cascader :placeholder="$t('common.selectPh')" :options="areaOptions" v-model="formData.delivery_area"></el-cascader></el-form-item>
          <el-form-item :label="$t('common.address')" prop="delivery_address"><el-input :placeholder="$t('common.enter')" v-model="formData.delivery_address"></el-input></el-form-item>
        </template>
        <el-form-item :label="$t('order.spTime')" prop="order_time" v-if="formData.receiving_type==2"><el-date-picker v-model="formData.order_time" type="datetime" :placeholder="$t('common.selectPh')" :picker-options="{disabledDate}" value-format="yyyy-MM-dd HH:mm" format="yyyy-MM-dd HH:mm"></el-date-picker></el-form-item>
        <el-form-item :label="$t('order.delivery_name')" prop="delivery_name"><el-input :placeholder="$t('common.enter')" v-model="formData.delivery_name"></el-input></el-form-item>
        <el-form-item :label="$t('common.email')" prop="delivery_mail"><el-input :placeholder="$t('common.enter')" v-model="formData.delivery_mail"></el-input></el-form-item>
        <el-form-item :label="$t('common.tel')" prop="delivery_phone"><el-input :placeholder="$t('common.enter')" v-model="formData.delivery_phone"></el-input></el-form-item>
        <el-form-item :label="$t('order.order_remark')" prop="order_remark"><el-input :placeholder="$t('common.enter')" v-model="formData.order_remark"></el-input></el-form-item>
        <el-form-item :label="$t('createOrder.orderType')" prop="order_type"><el-select :placeholder="$t('common.selectPh')" v-model="formData.order_type"><el-option v-for="i in 2" :key="i" :label="$t('createOrder.ot'+i)" :value="i*1"></el-option></el-select></el-form-item>
        <el-form-item :label="$t('order.payment_status')" prop="payment_status"><el-select :placeholder="$t('common.selectPh')" v-model="formData.payment_status"><el-option v-for="i in 5" :key="i" :label="$t('order.ps'+i)" :value="i*1"></el-option></el-select></el-form-item>
        <el-form-item :label="$t('order.payment_mode')" prop="payment_mode"><el-select :placeholder="$t('common.selectPh')" v-model="formData.payment_mode"><el-option v-for="i in 5" :key="i" :label="$t('order.pt'+i)" :value="i*1"></el-option></el-select></el-form-item>
        <el-form-item :label="$t('order.payTime')" prop="payment_at"><el-date-picker v-model="formData.payment_at" type="datetime" :placeholder="$t('common.selectPh')" value-format="yyyy-MM-dd HH:mm:ss" format="yyyy-MM-dd HH:mm:ss"></el-date-picker></el-form-item>
        <el-form-item :label="$t('common.created_at')" prop="created_at"><el-date-picker v-model="formData.created_at" type="datetime" :placeholder="$t('common.selectPh')" value-format="yyyy-MM-dd HH:mm:ss" format="yyyy-MM-dd HH:mm:ss"></el-date-picker></el-form-item>
      </el-form>

      <span slot="footer" class="dialog-footer">
        <el-button @click="showForm = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :loading="isLoadSub" @click="submit">{{$t('common.confirm')}}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import paging from '@/mixins/paging'
import {mapGetters} from 'vuex'
import { offlineOrder } from '@/form'
import { getGoodsCate, getUser, addOrder, getArea } from '@/api'
import { recursion } from '@/utils'
export default {
  name: 'OfflineOrders',
  mixins: [paging],
  data() {
    return {
      SFC: offlineOrder,
      column: {
        product_img: 'goods.image',
        product_name: 'goods.name',
        product_type: 'goods.type',
        product_sort_id: 'goods.cate',
        product_price: 'goods.price',
        product_cost: 'goods.cost',
        product_stock: 'goods.stock'
      },
      goodsSort: {},
      goodsType: ['', 'goods.product', 'goods.services'],
      curSltRow: null,
      showSpec: false,
      psvi: [],
      showForm: false,
      isLoadSub: false,
      formData: {
        user_id: '',
        order_time: '',
        delivery_address: '',
        delivery_area: [],
        delivery_name: '',
        delivery_phone: '',
        delivery_mail: '',
        payment_type: 1,
        payment_mode: '',
        order_type: '',
        receiving_type: '',
        payment_status: '',
        payment_at: '',
        created_at: '',
        order_remark: '',
      },
      areaOptions: [],
      addedProducts: [],
      userOp: [],
      discount: 0,
      number: 1,
      rules: {
        user_id: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
      },
      rulesAppo: {
        order_time: [{required: true,message: this.$t('common.required'),trigger: "blur"}],
      }
    }
  },
  created() {
    this.init()
    this.getList('OfflineGoods')
  },
  watch: {
    // 折扣金额变化
    discount(val) {
      this.addedProducts.forEach(item => {
        item.product_price = val ? (item.uniPrice * (val/10)).toFixed(2) : item.uniPrice  // 单价
        item.product_money = val ? (item.subTotal * (val/10)).toFixed(2) : item.subTotal  // 小计
      });
    },
    'formData.receiving_type'(val) {
      if (val==1) {
        this.formData.order_time = ''
      } else {
        this.formData.delivery_address = ''
        this.formData.delivery_area = []
      }
    },
    'formData.user_id'(val) {
      const user = this.userOp.find(item => item.user_id === val)
        if (user) {
          this.formData.delivery_name = user.user_name
          this.formData.delivery_phone = user.user_phone
          this.formData.delivery_mail = user.user_email
        }
    }
  },
  computed: {
    ...mapGetters(['language']),
    // 操作目标项目规格信息变化
    specTarget() {
      return this.curSltRow ? this.curSltRow.product_relation.find(item => item.product_specs_value_id === this.psvi.join('-')) || {} : {}
    },
    // 购买项目按钮角标总数
    totalNum() {
      return this.addedProducts.reduce((pre, cur) => pre + cur.product_number, 0)
    },
    // 总计（订单支付金额）
    order_money() {
      return (this.addedProducts.reduce((pre, cur) => pre + cur.product_money*1, 0)).toFixed(2)
    },
    // 订单原价
    original_cost() {
      return (this.addedProducts.reduce((pre, cur) => pre + cur.subTotal*1, 0)).toFixed(2)
    }
  },
  methods: {
    // 获取选项
    async init() {
      getArea().then(({results}) => {
        const {result} = recursion({data: results, match: [['label', 'value'],['area_name', 'area_name']]})
        this.areaOptions = result
      })
      getGoodsCate().then(({results}) => {
        results.forEach(item => this.goodsSort[item.product_sort_id] = item.product_sort_name);
        const cate = results.map(({product_sort_id, product_sort_name}) =>({label: product_sort_name, value: product_sort_id, trans: '1'}))
        this.$set(this.SFC.formItems[2].item, 'options', cate)
      })
      getUser().then(({results}) => {
        this.userOp = results
      })
    },
    // 预约时间过期禁用
    disabledDate(e) {
      // return (Date.now()-24*60*60*1000) > new Date(e).getTime()
    },
    // 添加/预约商品
    add_appo(row) {
      this.curSltRow = row
      if (row.product_type===2 || row.is_specs) {
        // 预约项目/购买项目（多规格）弹出
        this.psvi.length = this.curSltRow.product_specs_key.length
        this.showSpec = true
      } else {
        // 直接添加到购买项目（单规格）
        const {product_id, product_name, product_img, product_type, product_price, product_stock} = this.curSltRow
        const product = {product_id, product_name, product_img, product_type, product_relation_id: '', product_number: 1, product_price, uniPrice: product_price, product_money: product_price, subTotal: product_price,product_stock}
        const existProdInd = this.addedProducts.findIndex(item => item.product_id === product_id)
        if (!product_stock) return this.$message({message: this.$t('offlineOrder.noStock'), type: 'warning'})
        if (existProdInd === -1) {
          this.addedProducts.push(product)
        } else {
          const newNum = this.addedProducts[existProdInd].product_number + 1
          if (newNum > product_stock) return this.$message({message: this.$t('offlineOrder.mtStock'), type: 'warning'})
          this.updateNum(newNum, product_stock, existProdInd)
        }
        this.$message({message: this.$t('common.success'),type: 'success'})
      }

    },
    // 修改购买项目数量
    updateNum(newNum, product_stock,index) {
      const {product_price, uniPrice} = this.addedProducts[index]
      const newPM = product_price * newNum
      const newST = uniPrice * newNum
      this.$set(this.addedProducts[index], 'product_stock', product_stock)
      this.$set(this.addedProducts[index], 'product_number', newNum)
      this.$set(this.addedProducts[index], 'product_money', newPM)
      this.$set(this.addedProducts[index], 'subTotal', newST)
    },
    // 添加到购买项目/添加预约订单
    confirm_add_appo() {
      if (this.curSltRow.is_specs && this.psvi.includes(undefined)) return this.$message({message: this.$t('offlineOrder.speced'), type: 'warning'})
      const target = this.curSltRow.product_relation.find(item => item.product_specs_value_id === this.psvi.join('-'))
      const {product_relation_id='', product_specs_value, price=this.curSltRow.product_price, stock} = target || {}
      const {is_tostore, tostore_price, product_id, product_name, product_img, product_type} = this.curSltRow

      if (product_type === 1) {
        // 添加到购买项目（多规格）
        if (!stock) return this.$message({message: this.$t('offlineOrder.noStock'), type: 'warning'})
        if (this.number > stock) return this.$message({message: this.$t('offlineOrder.mtStock'), type: 'warning'})
        const subTotal = this.number*price
        const product = {product_id, product_name, product_img, product_type, product_relation_id, product_number: this.number, product_price: price, uniPrice: price, product_money: subTotal, subTotal, product_stock: stock, specs_value:product_specs_value.map(item => item[this.language==='zh' ? 'product_specs_value' : 'en_product_specs_value']).join(',')}
        const existProdInd = this.addedProducts.findIndex(item => item.product_relation_id === product_relation_id)
        if (existProdInd === -1) {
          this.addedProducts.push(product)
        } else {
          const newNum = this.addedProducts[existProdInd].product_number + this.number
          if (newNum > this.addedProducts[existProdInd].product_stock) return this.$message({message: this.$t('offlineOrder.mtStock'), type: 'warning'})
          this.$set(this.addedProducts[existProdInd], 'product_number', newNum)
          this.$set(this.addedProducts[existProdInd], 'product_stock', stock)
        }
        this.$message({message: this.$t('common.success'),type: 'success'})
        this.showSpec = false
      } else {
        // 添加（创建）预约订单
        const elseParams = is_tostore ?
        {product_price: tostore_price, order_money: tostore_price, original_cost: tostore_price, product_money: tostore_price} :
        {product_price: price, order_money: price, original_cost: price, product_money: price}
        Object.assign(this.formData, {product_id, product_img, product_name, product_type, product_number: 1, product_relation_id}, elseParams)
        const params = {...this.formData, product_id, product_img, product_name, product_type, product_number: 1, product_relation_id, ...elseParams, receiving_type:2, delivery_area:''}

        this.$refs.specForm.validate(async (valid) => {
          if (!valid) return
          try {
            this.isLoadSub = true
            console.log(params);

            await addOrder([params])
            this.showSpec = false
            this.$message({message: this.$t('common.success'),type: 'success'})
          } catch (error) {

          } finally {
            this.isLoadSub = false
          }
        })

      }
    },
    // 删除已添加购买项目
    delAdded(index){
      this.addedProducts.splice(index, 1)
      if (!this.addedProducts.length) {
        this.showForm = false
      }
    },
    // 弹框关闭重置
    reset() {
      // this.curSltRow = null
      this.formData = {
        user_id: '',
        order_time: '',
        delivery_address: '',
        delivery_area: [],
        delivery_name: '',
        delivery_phone: '',
        delivery_mail: '',
        payment_type: 1,
        payment_mode: '',
        order_type: '',
        receiving_type: '',
        payment_status: '',
        payment_at: '',
        created_at: '',
      }
      this.discount = 0
      this.number = 1
      this.psvi = []
      if (this.$refs.specForm) {
        this.$refs.specForm.resetFields()
      }

    },
    // 购买项目提交
    submit() {
      this.$refs.form.validate(async (valid) => {
        if (!valid) return
        const delivery_area = this.formData.delivery_area.join(',')
        const params = this.addedProducts.map(({subTotal, uniPrice, specs_value, product_stock, ...el}) => ({order_money: this.order_money, original_cost: this.original_cost, ...this.formData, ...el, delivery_area}))

        try {
          this.isLoadSub = true
          await addOrder(params)
          this.showForm = false
          this.addedProducts = []
          this.$message({message: this.$t('common.success'),type: 'success'})
          this.getList('OfflineGoods')
        } catch (error) {

        } finally {
          this.isLoadSub = false
        }
      })
    }
  }
}
</script>

<style>
.line {
  margin-left: 5px;
  text-decoration: line-through;
  font-size: 12px;
  color: red;
}
.c-btn {
  font-size:20px;
  color:#42B983;
  cursor: pointer;
}
</style>
