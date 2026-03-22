<template>
  <div>
    <Navbar v-if="!hideNavbar" />
    <div :class="['main-content', { 'webapp-layout': hideNavbar }]">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Navbar from './components/Navbar.vue'

const route = useRoute()

const hasWindow = typeof window !== 'undefined'
const initialParams = hasWindow ? new URLSearchParams(window.location.search || '') : new URLSearchParams('')
const initialIsWebAppRoute = hasWindow ? window.location.pathname.startsWith('/app') : false
const initialHasShopQuery = hasWindow ? initialParams.has('shop') || initialParams.has('shopId') : false
const initialIsTelegramWebApp = hasWindow ? Boolean(window.Telegram?.WebApp) : false
const initialIsTelegramClientUa = hasWindow ? /Telegram/i.test(window.navigator?.userAgent || '') : false
const initialHasTelegramQueryHints = hasWindow
  ? ['tgWebAppData', 'tgWebAppVersion', 'tgWebAppPlatform', 'startapp'].some((key) => initialParams.has(key))
  : false

const isWebAppRoute = computed(() => route.path.startsWith('/app'))
const hasShopQuery = computed(() => Boolean(route.query?.shop || route.query?.shopId))
const hideNavbar = computed(() =>
  initialIsWebAppRoute ||
  initialHasShopQuery ||
  initialIsTelegramWebApp ||
  initialIsTelegramClientUa ||
  initialHasTelegramQueryHints ||
  isWebAppRoute.value ||
  hasShopQuery.value
)
</script>

<style>
#app {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background-color: #f9f9f9;
  min-height: 100vh;
}

.main-content {
  max-width: 1200px;
  margin: 2rem auto;
  padding: 0 1rem;
}

.main-content.webapp-layout {
  max-width: none;
  margin: 0;
  padding: 0;
}
</style>
