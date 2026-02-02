<template>
  <div id="app">
    <Navbar />
    <div class="main-content">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import Navbar from './components/Navbar.vue'
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
</style>