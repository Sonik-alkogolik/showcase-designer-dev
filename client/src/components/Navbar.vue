<template>
  <nav class="navbar">
    <div class="navbar-container">
      <div class="navbar-brand">
        <h1>Constructor</h1>
      </div>
      
         <div class="navbar-menu">
        <router-link to="/" class="nav-link" v-if="isAuthenticated">
          Главная
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
  background: #2c3e50;
  color: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.navbar-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 60px;
}

.navbar-brand h1 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
}

.navbar-menu {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.nav-link {
  color: white;
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  transition: background 0.3s;
}

.nav-link:hover, .nav-link.router-link-active {
  background: rgba(255,255,255,0.1);
}

.auth-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 4px;
  text-decoration: none;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s;
  border: none;
  display: inline-block;
}

.btn-outline {
  background: transparent;
  border: 2px solid white;
  color: white;
}

.btn-outline:hover {
  background: white;
  color: #2c3e50;
}

.btn-primary {
  background: #3498db;
  color: white;
  border: 2px solid #3498db;
}

.btn-primary:hover {
  background: #2980b9;
  border-color: #2980b9;
}

.user-info {
  color: white;
  font-weight: 500;
}

.user-info span {
  padding: 0.5rem;
}
</style>