<template>
  <div>
    <template v-if="src">
      <div v-if="expand" class="expand">
        <div :style="{width: size+'px', height: size+'px', 'border-radius': radius+'px'}" class="img-cont" v-for="(src, index) in srcList" :key="index">
          <el-image 
            :style="{width: size+'px', height: size+'px', 'border-radius': radius+'px'}"
            :fit="fit"
            v-if="!src.includes('.mp4')"
            :src="src"
            :preview-src-list="imgSrcList">
          </el-image>
          <ProVideo :style="{width: size+'px', height: size+'px', 'border-radius': radius+'px'}" :src="src" v-else></ProVideo>
          <div class="del" v-if="delable" @click="$emit('del', index)"><i class="el-icon-delete"></i></div>
        </div>
      </div>
      <div v-else>
        <el-image
          :style="{width: size+'px', height: size+'px', 'border-radius': radius+'px'}"
          class="img"
          :fit="fit"
          :src="imgSrcList[0]" 
          :preview-src-list="imgSrcList">
        </el-image>
      </div>
    </template>
    <div :style="{width: size+'px', height: size+'px', 'border-radius': radius+'px'}" class="img-err" v-else>
      <i class="el-icon-warning-outline" style="font-size: 18px"></i>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProImage',
  props: {
    delable: Boolean,
    fit: {
      type: String,
      default: 'contain'
    },
    src: [String, Array],
    size: {
      type: [String, Number],
      default: 60
    },
    radius: {
      type: [String, Number],
      default: 0
    },
    expand: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      srcList: [],
    }
  },
  watch: {
    src: {
      handler(val) {
        this.srcList = Array.isArray(val) ? val : val.split(',')
      },
      immediate: true
    }
  },
  computed: {
    imgSrcList() {
      return this.srcList.filter(item => !item.includes('mp4'))
    },
    videoSrcList() {
      return this.srcList.filter(item => item.includes('mp4'))
    }
  }
}
</script>

<style lang="scss" scoped>
.expand {
  display: flex;
  flex-wrap: wrap;
}
.img-err {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-left: 10px;
  background-color: #F5F7FA;
}
.img-cont {
  position: relative;
  overflow: hidden;
  margin: 10px;
  .del {
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: 0;
    right: 0;
    width: 20px;
    height: 20px;
    background-color: transparent;
    color: #F56C6C;
    font-size: 12px;
    cursor: pointer;
  }
  .del:hover {
    background-color: #F56C6C;
    color: #ffffff;
  }
}
</style>