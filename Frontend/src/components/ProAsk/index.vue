<template>
  <div>
    <el-dialog :title="$t('baidu.askMe')" :close-on-click-modal="false" :width="device==='desktop' ? '1000px' : '100%'" :visible.sync="dialogShow" @open="toBott" v-if="pop">
      <div class="screen" :style="{height}" ref="screen">
        <div class="item" :class="{me: item.type==1, he: item.type==2}" v-for="(item, index) in list" :key="index">
          <div class="head"><el-avatar :size="40" shape="square" v-bind="item.type==1 && avatar ? {src: avatar} : {icon: 'el-icon-user-solid'}"></el-avatar></div>
          <div class="bubble">{{item.content}}</div>
          <div class="loading"><i :class="`el-icon-${item.status}`" v-if="item.status !== 'success'"></i></div>
        </div>
      </div>
      
      <el-input type="textarea" :placeholder="`${$t('baidu.enter')}...`" v-model="content" @keyup.enter.native="send"></el-input>
      <span slot="footer" class="dialog-footer">
        <el-button @click="dialogShow = false">{{$t('common.cancel')}}</el-button>
        <el-button type="primary" :disabled="loading" @click.native.prevent="send">{{$t(loading ? 'baidu.thinking' : 'purchase.send')}}</el-button>
      </span>
    </el-dialog>
    <el-card v-else>
      <div class="screen" :style="{height}" ref="screen">
        <div class="item" :class="{me: item.type==1, he: item.type==2}" v-for="(item, index) in list" :key="index">
          <div class="head"><el-avatar :size="40" shape="square" v-bind="item.type==1 && avatar ? {src: avatar} : {icon: 'el-icon-user-solid'}"></el-avatar></div>
          <div class="bubble">{{item.content}}</div>
          <div class="loading"><i :class="`el-icon-${item.status}`" v-if="item.status !== 'success'"></i></div>
        </div>
      </div>
      
      <div class="df">
        <el-input class="f1 mr20" type="textarea" :placeholder="`${$t('baidu.enter')}...`" v-model="content" @keyup.enter.native="send"></el-input>
        <el-button type="primary" :disabled="loading" @click.native.prevent="send">{{$t(loading ? 'baidu.thinking' : 'purchase.send')}}</el-button>
      </div>
    </el-card>
  </div>
</template>

<script>
import {mapGetters} from 'vuex'
import {ask} from '@/api'
export default {
  props: {
    show: Boolean,
    pop: {
      type: Boolean,
      default: false
    },
    height: {
      type: String,
      default: '600px'
    }
  },
  data() {
    return {
      loading: false,
      content: '',
      list: []
    }
  },
  computed: {
    ...mapGetters(['avatar']),
    dialogShow: {
      get() {
        return this.show
      },
      set(val) {
        this.$emit('update:show', val)
      }
    }
  },
  methods: {
    toBott() {
      this.$nextTick(()=>{
        this.$refs.screen.scrollTop = this.$refs.screen.scrollHeight
      })
    },
    async send() {
      if (!this.content) return this.$message({message: this.$t('baidu.enter'), type: 'warning'})

      try {
        this.list.push({type: 1, content: this.content, status: 'loading'})
        this.toBott()
        this.loading = true
        const {results:{result}} = await ask({message: this.content})
        this.list.push({type: 2, content: result, status: 'success'})
        this.$set(this.list[this.list.length-2], 'status', 'success')
        this.content = ''
        this.toBott()
      } catch (error) {
        this.$set(this.list[this.list.length-1], 'status', 'warning')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style lang="scss">
  .screen {
    display: flex;
    flex-direction: column;
    height: 500px;
    padding: 20px;
    margin-bottom: 20px;
    border: 2px solid black;
    overflow-y: scroll;
    .el-scrollbar {
      height: 100%;
      .scrollbar-wrapper {
        overflow-x: hidden !important;
      }
    }
    .item + .item {
      margin-top: 15px;
    }
    .item {
      display: flex;
      .head {
        flex: 0 0 40px;
        height: 40px;
        border-radius: 50%;
      }
      .loading {
        display: flex;
        align-items: center;
        margin: 0 10px;
      }
      .bubble {
        position: relative;
        max-width: 680px;
        padding: 10px;
        background-color: #42b983;
        border-radius: 10px;
        font-size: 16px;
        color: #ffffff;
        &::after {
          position: absolute;
          top: 15px;
          content: " ";
          display: block;
          border-width: 8px;
          border-style: solid;
          border-color: transparent #42b983 transparent transparent;
        }
      }
      &.he {
        .head {
          margin-right: 20px;
        }
        .bubble {
          &::after {
            left: -15px;
          }
        }
      }
      &.me {
        flex-direction: row-reverse;
        .head {
          margin-left: 20px;
        }
        .bubble {
          &::after {
            right: -15px;
            border-color: transparent transparent transparent #42b983;
          }
        }
      }
    }
  }
</style>