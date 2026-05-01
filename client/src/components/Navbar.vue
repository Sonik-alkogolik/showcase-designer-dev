<template>
  <nav class="navbar">
    <div class="navbar-container">
      <router-link :to="isAuthenticated ? '/shops' : '/'" class="navbar-brand">
        <h1>t-go</h1>
      </router-link>
      
      <button
        class="burger-btn"
        type="button"
        :aria-expanded="isMenuOpen ? 'true' : 'false'"
        aria-label="Открыть меню"
        @click="toggleMenu"
      >
        <span />
        <span />
        <span />
      </button>

      <div class="navbar-menu" :class="{ open: isMenuOpen }">
        <router-link to="/shops" class="nav-link" v-if="isAuthenticated" @click="closeMenu">
          Главная
        </router-link>

        <router-link to="/dashboard" class="nav-link" v-if="isAuthenticated" @click="closeMenu">
          Панель
        </router-link>

        <router-link to="/create-shop" class="nav-link" v-if="isAuthenticated" @click="closeMenu">
          Новый магазин
        </router-link>
        
        <router-link to="/plans" class="nav-link" v-if="isAuthenticated" @click="closeMenu">
          Тарифы
        </router-link>
        
        <router-link to="/profile" class="nav-link" v-if="isAuthenticated" @click="closeMenu">
          Профиль
        </router-link>

        <router-link to="/dashboard/help" class="nav-link" v-if="isAuthenticated" @click="closeMenu">
          Как начать?
        </router-link>
        
        <div class="auth-buttons" v-if="!isAuthenticated">
          <router-link to="/login" class="btn btn-outline" @click="closeMenu">
            Войти
          </router-link>
          <router-link to="/register" class="btn btn-primary" @click="closeMenu">
            Регистрация
          </router-link>
        </div>
        
        <button v-if="isAuthenticated" @click="handleLogout" class="btn btn-outline">
          Выйти
        </button>
        
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const { token, logout } = useAuth()
const router = useRouter()
const route = useRoute()
const isMenuOpen = ref(false)

const isAuthenticated = computed(() => !!token.value)
const toggleMenu = () => { isMenuOpen.value = !isMenuOpen.value }
const closeMenu = () => { isMenuOpen.value = false }

const handleLogout = async () => {
  if (confirm('Вы уверены, что хотите выйти?')) {
    closeMenu()
    await logout()
    router.push('/login')
  }
}

watch(() => route.fullPath, () => {
  closeMenu()
})
</script>

<style scoped>
.navbar {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: linear-gradient(180deg, rgba(9, 12, 22, 0.9), rgba(9, 12, 22, 0.72));
  backdrop-filter: blur(8px);
  border-bottom: 1px solid rgba(171, 183, 255, 0.15);
  animation: nav-enter 500ms ease both;
}

@keyframes nav-enter {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.navbar a {
  text-decoration: none;
}

.navbar-brand {
  color: #f1f4ff;
  display: inline-flex;
  align-items: center;
}

.navbar-brand h1 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  letter-spacing: 0.03em;
}

.navbar-container {
  width: min(1200px, 100% - 2rem);
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 64px;
}

.navbar-menu {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.burger-btn {
  display: none;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  border: 1px solid rgba(196, 204, 255, 0.34);
  background: rgba(255, 255, 255, 0.04);
  cursor: pointer;
  align-items: center;
  justify-content: center;
  gap: 4px;
  flex-direction: column;
}

.burger-btn span {
  width: 16px;
  height: 2px;
  background: #dee3ff;
  border-radius: 999px;
}

.nav-link {
  color: #cfd6ee;
  padding: 0.5rem 0.8rem;
  border-radius: 999px;
  transition: background-color 220ms ease, color 220ms ease;
}

.nav-link:hover,
.nav-link.router-link-active {
  color: #ecf1ff;
  background: rgba(118, 148, 255, 0.16);
}

.auth-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn {
  border-radius: 999px;
  min-height: 38px;
  padding: 0 1rem;
  font-size: 0.94rem;
  text-decoration: none;
  cursor: pointer;
  font-weight: 600;
  transition: transform 220ms ease, border-color 220ms ease, background-color 220ms ease;
  border: 1px solid transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn:hover {
  transform: translateY(-1px);
}

.btn-outline {
  background: rgba(255, 255, 255, 0.02);
  border-color: rgba(196, 204, 255, 0.34);
  color: #dee3ff;
}

.btn-outline:hover {
  border-color: rgba(196, 204, 255, 0.6);
  background: rgba(124, 149, 255, 0.12);
}

.btn-primary {
  color: #f5f8ff;
  background: linear-gradient(120deg, #4f63ff, #33c5ff);
  box-shadow: 0 8px 24px rgba(72, 116, 255, 0.36);
}

.btn-primary:hover {
  box-shadow: 0 14px 30px rgba(72, 116, 255, 0.42);
}

@media (max-width: 820px) {
  .navbar-container {
    width: calc(100% - 1rem);
    position: relative;
  }

  .burger-btn {
    display: inline-flex;
  }

  .navbar-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: min(280px, calc(100vw - 1rem));
    display: none;
    flex-direction: column;
    align-items: stretch;
    gap: 0.45rem;
    padding: 0.6rem;
    border: 1px solid rgba(171, 183, 255, 0.2);
    border-radius: 12px;
    background: rgba(9, 12, 22, 0.96);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.35);
    backdrop-filter: blur(8px);
  }

  .navbar-menu.open {
    display: flex;
  }

  .nav-link,
  .btn {
    width: 100%;
    justify-content: center;
  }

  .auth-buttons {
    display: grid;
    gap: 0.45rem;
  }
}

/* Legacy rules kept for easier merge tracking */
.navbar {
  position: sticky;
}
</style>
