<template>
  <div class="sidebar-logo-container" :class="{'collapse':collapse}">
    <transition name="sidebarLogoFade">
      <div class="sidebar-logo-box">
        <div class="sidebar-logo-bg" :style="{'background-image': `url(${logo})`}"></div>
        <router-link v-if="collapse" key="collapse" class="sidebar-logo-link" to="/">
          <img v-if="logo" :src="logo" class="sidebar-logo">
          <h1 v-else class="sidebar-title"><span class="text">{{ title }}</span></h1>
        </router-link>
        <router-link v-else key="expand" class="sidebar-logo-link" to="/">
          <img v-if="logo" :src="logo" class="sidebar-logo">
          <h1 class="sidebar-title"><span class="text" :title="title">{{ title }}</span></h1>
        </router-link>
      </div>
    </transition>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
export default {
  name: 'SidebarLogo',
  props: {
    collapse: {
      type: Boolean,
      required: true
    }
  },
  computed: {
    ...mapGetters(['logo', 'title'])
  }
}
</script>

<style lang="scss" scoped>
.sidebarLogoFade-enter-active {
  transition: opacity 1.5s;
}

.sidebarLogoFade-enter,
.sidebarLogoFade-leave-to {
  opacity: 0;
}

.sidebar-logo-container {
  position: relative;
  width: 100%;
  height: 50px;
  line-height: 50px;
  background: #ffffff;
  text-align: center;
  overflow: hidden;

  & .sidebar-logo-box {
    position: relative;
    width: 100%;
    height: 50px;
    & .sidebar-logo-bg {
      width: 100%;
      height: 50px;
      background-color: #ffffff;
      background-repeat: no-repeat;
      background-size: cover;
      background-position: center;
      filter: blur(3px);
    }
  }

  & .sidebar-logo-link {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 100%;

    & .sidebar-logo {
      width: 32px;
      height: 32px;
      padding: 3px;
      background-color: #fff;
      border-radius: 5px;
      vertical-align: middle;
      margin-right: 12px;
      box-shadow: 0 0 10px rgba(0, 0, 0, .5);
    }

    & .sidebar-title {
      display: inline-block;
      margin: 0;
      font-weight: 600;
      line-height: 50px;
      font-size: 14px;
      font-family: Avenir, Helvetica Neue, Arial, Helvetica, sans-serif;
      vertical-align: middle;

      & .text {
        padding: 2px 6px;
        border-radius: 5px;
        background-color: #fff;
        box-shadow: 0 0 10px rgba(0, 0, 0, .5);
        display: inline-block;
        vertical-align: middle;
        max-width: 140px;
        line-height: 20px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }
  }

  &.collapse {
    .sidebar-logo {
      margin-right: 0px;
    }
  }
}
</style>
