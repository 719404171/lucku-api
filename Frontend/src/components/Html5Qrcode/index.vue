<template>
	<el-popover
		@show="toggleBtn"
		@hide="onHide"
    placement="bottom"
    trigger="manual"
    v-model="visible">
    <div :id="qrId" style="width: 300px"></div>
    <el-button type="primary" slot="reference" @click="visible = !visible" :icon="icon"><slot></slot></el-button>
  </el-popover>
</template>

<script>
import load from "../Tinymce/dynamicLoadScript";

const hqCDN = "https://unpkg.com/html5-qrcode@2.0.11/dist/html5-qrcode.min.js";

export default {
  props: {
    icon: {
      type: String,
      default: "el-icon-camera"
    },
    qrId: {
      type: String,
      default: "qr-reader"
    }
  },
	data() {
		return {
			visible: false,
			html5QrCode: null
		}
	},
  mounted() {
    load(hqCDN);
  },
  methods: {
    toggleBtn() {
      if (!window.Html5Qrcode) return
      this.html5QrCode = new Html5Qrcode(this.qrId);
      const qrCodeSuccessCallback = (decodedText, decodedResult) => {
				this.$emit('scan', decodedText)
        if (this.$message) {
          this.$message({ message: decodedText, type: 'success' })
        }
				this.visible = false
        this.html5QrCode
          .stop()
          .then((ignore) => {
          })
          .catch((err) => {
          });
      };
      const config = { fps: 10, qrbox: { width: 250, height: 250 } };

      this.html5QrCode.start(
        { facingMode: "environment" },
        config,
        qrCodeSuccessCallback
      );
    },
		onHide() {
			if (this.html5QrCode) {
        this.html5QrCode.stop()
      }
		}
  },
};
</script>
