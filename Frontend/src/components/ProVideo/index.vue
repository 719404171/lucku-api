<template>
<div class="pro-video">
  <el-popover placement="bottom" trigger="click" @show="onShow" @hide="onHide">
    <video id="vid" ref="vid" style="max-width: 300px" :src="src" controls></video>
    <div slot="reference" class="vidbox">
      <video class="vid" :src="src" :controls="false"></video>
      <div class="icon" @click="dialogVisible=true"><i class="el-icon-video-camera"></i></div>
    </div>
  </el-popover>
</div>
</template>

<script>
export default {
  props: {
    src: {
      type: String,
      default: ''
    },
    cover: {
      type: String,
      default: ''
    }
  },
  methods: {
    onShow() {
      this.$refs.vid.play()
    },
    onHide() {
      this.$refs.vid.pause()
    }
  }
}
</script>

<style lang="scss" scoped>
.pro-video {

  .vidbox {
    position: relative;
    width: 100%;
    height: 100%;
    background-color: #000;
    .vid{
      width: 100%;
      height: 100%;
    }
    .icon {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 24px;
      color: #fff;
      cursor: pointer;
    }
  }
}
</style>
