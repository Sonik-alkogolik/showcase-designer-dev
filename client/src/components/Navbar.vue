<template>
  <nav class="navbar">
    <div class="navbar-container">
      <router-link :to="isAuthenticated ? '/shops' : '/'" class="navbar-brand">
        <h1>Showcase Designer</h1>
      </router-link>
      
      <div class="navbar-menu">
        <router-link to="/shops" class="nav-link" v-if="isAuthenticated">
          Главная
        </router-link>

        <router-link to="/create-shop" class="nav-link" v-if="isAuthenticated">
          Новый магазин
        </router-link>
        
        <router-link to="/plans" class="nav-link" v-if="isAuthenticated">
          Тарифы
        </router-link>
        
        <router-link to="/profile" class="nav-link" v-if="isAuthenticated">
          Профиль
        </router-link>
        
        <div class="auth-buttons" v-if="!isAuthenticated">
          <router-link to="/login" class="btn btn-outline">
            Войти
          </router-link>
          <router-link to="/register" class="btn btn-primary">
            Регистрация
          </router-link>
        </div>
        
        <button v-if="isAuthenticated" @click="handleLogout" class="btn btn-outline">
          Выйти
        </button>
        
        <div v-if="isAuthenticated" class="user-info">
          <span>{{ user?.name || 'Пользователь' }}</span>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const { token, user, logout } = useAuth()
const router = useRouter()

const isAuthenticated = computed(() => !!token.value)

const handleLogout = async () => {
  if (confirm('Вы уверены, что хотите выйти?')) {
    await logout()
    router.push('/login')
  }
}
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

.user-info {
  color: #aab4db;
  font-size: 0.9rem;
  padding-left: 0.4rem;
}

@media (max-width: 820px) {
  .navbar-container {
    width: calc(100% - 1rem);
  }

  .navbar-menu {
    gap: 0.4rem;
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .nav-link {
    font-size: 0.9rem;
    padding: 0.44rem 0.68rem;
  }
}

/* Legacy rules kept for easier merge tracking */
.navbar {
  position: sticky;
}
</style>
