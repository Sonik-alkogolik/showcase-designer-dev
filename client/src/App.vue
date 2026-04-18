<template>
  <div class="app-shell">
    <Navbar v-if="!hideNavbar" />
    <div :class="['main-content', { 'webapp-layout': hideNavbar, 'dashboard-layout': isDashboardRoute }]">
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
const isDashboardRoute = computed(() => route.path.startsWith('/dashboard'))
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
.app-shell {
  min-height: 100vh;
  color: var(--color-text);
  background:
    radial-gradient(1200px 800px at -20% -20%, rgba(88, 102, 255, 0.2), transparent 55%),
    radial-gradient(900px 650px at 120% 0%, rgba(10, 158, 255, 0.16), transparent 52%),
    var(--color-background);
}

.main-content {
  width: 100%;
  margin: 1.5rem auto 2.5rem;
}

.main-content.webapp-layout {
  max-width: none;
  margin: 0;
  padding: 0;
}

.main-content.dashboard-layout {
  width: min(1400px, 100% - 1rem);
  margin-top: 0.8rem;
}
</style>
