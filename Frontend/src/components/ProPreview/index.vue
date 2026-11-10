<template>
  <div class="phone" :style="{width: device==='desktop' ? '384px' : '100%'}">
    <div class="phone-screen" :style="{width: device==='desktop' ? '344px' : '90%'}">
      <el-scrollbar wrap-class="scrollbar-wrapper"><div v-html="content"></div></el-scrollbar>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    content: String
  }
}
</script>

<style lang="scss">
.phone {
  display: flex;
  justify-content: center;
  height: 734px;
  padding-top: 50px;
  border: 1px solid #DDDDDD;
  border-radius: 30px;
  .phone-screen {
    height: 644px;
    border: 1px solid #DDDDDD;
    .el-scrollbar {
      height: 100%;
      .scrollbar-wrapper {
        overflow-x: hidden !important;
      }
    }
  }
}
</style>