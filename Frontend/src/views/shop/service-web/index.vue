<template>
  <div
    v-loading="isLoading"
    class="app-container"
    :style="{ height: tableHeight + 'px', width: '100%' }"
  >
    <iframe
      v-if="flowSrc"
      id="iframe"
      :src="flowSrc"
      height="100%"
      width="100%"
      frameborder="0"
      allow="camera; microphone"
      sandbox="allow-scripts allow-same-origin allow-popups allow-storage-access-by-user-activation allow-modals"
    />
  </div>
</template>

<script>
import { getStore } from "@/api";
export default {
  components: {
    // VueIframe
  },
  data() {
    return {
      flowSrc: "",
      isLoading: false,
    };
  },
  computed: {
    tableHeight() {
      const height = document.body.clientHeight - 100;
      return height;
    },
  },
  created() {
    this.getWebLink();
  },
  mounted() {},
  methods: {
    async getWebLink() {
      this.isLoading = true;
      try {
        const {results} = await getStore();
        this.flowSrc = results.shop_web;
      } catch (error) {
        this.$message({message: this.$t('store.serviceWebErr'), type: 'error'})
      } finally {
        this.isLoading = false;
      }
    },
  },
};
</script>

<style scoped>
.app-container {
  position: relative;
}

#iframe .mdk-login-hd {
  display: none;
}

.mask-head-chunk {
  position: absolute;
  width: 500px;
  height: 60px;
  top: 20px;
  left: 0;
  background-color: #fff;
  z-index: 100;
}
</style>
