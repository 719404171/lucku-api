<template>
  <div>
    <img v-if="qrcodeDataUrl" :src="qrcodeDataUrl" :width="size" :height="size" />
  </div>
</template>

<script>
import qrcode from 'qrcode'
export default {
  name: 'ProQrcode',
  props: {
    value: {
      type: [String, Number],
      default: ''
    },
    size: {
      type: Number,
      default: 128
    }
  },
  data() {
    return {
      qrcodeDataUrl: ''
    }
  },
  watch: {
    value: {
      immediate: true,
      handler(newVal) {
        this.generateQrcode(newVal+'')
      }
    }
  },
  methods: {
    generateQrcode(text) {
      if (!text) {
        this.qrcodeDataUrl = ''
        return
      }
      qrcode.toDataURL(
        text,
        {
          width: this.size,
          margin: 1
        },
        (err, url) => {
          if (err) {
            console.error('Failed to generate QR code:', err)
            this.qrcodeDataUrl = ''
            return
          }
          this.qrcodeDataUrl = url
        }
      )
    }
  }
}
</script>