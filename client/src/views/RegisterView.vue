<template>
  <section class="auth-page">
    <div class="auth-card">
      <p class="kicker">Создание аккаунта</p>
      <h1>Запустим ваш магазин</h1>
      <p class="hint">Регистрация займёт меньше минуты.</p>

      <form class="auth-form" @submit.prevent="handleRegister">
        <label>
          Имя
          <input v-model="name" placeholder="Ваше имя" required />
        </label>

        <label>
          Email
          <input v-model="email" placeholder="you@example.com" type="email" required />
        </label>

        <label>
          Пароль
          <input v-model="password" placeholder="Минимум 8 символов" type="password" required />
        </label>

        <label>
          Подтверждение пароля
          <input v-model="passwordConfirmation" placeholder="Повторите пароль" type="password" required />
        </label>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Создаём аккаунт...' : 'Зарегистрироваться' }}
        </button>
      </form>

      <p v-if="error" class="error">{{ error }}</p>
      <p class="alt-action">
        Уже зарегистрированы? <router-link to="/login">Войти</router-link>
      </p>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const loading = ref(false)

const { register } = useAuth()

const handleRegister = async () => {
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Пароли не совпадают'
    return
  }

  loading.value = true
  error.value = ''

  const result = await register(name.value, email.value, password.value, passwordConfirmation.value)

  loading.value = false

  if (!result.success) {
    error.value = result.error
    return
  }

  router.push('/shops')
}
</script>

<style scoped>
.auth-page {
  min-height: calc(100vh - 140px);
  display: grid;
  place-items: center;
}

.auth-card {
  width: min(460px, 100%);
  border-radius: 20px;
  padding: 1.3rem 1.2rem;
  border: 1px solid rgba(174, 189, 255, 0.2);
  background: linear-gradient(170deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
  backdrop-filter: blur(8px);
  animation: card-in 520ms cubic-bezier(.2,.8,.2,1) both;
}

.kicker {
  color: var(--color-muted);
  text-transform: uppercase;
  letter-spacing: 0.2em;
  font-size: 0.75rem;
}

.auth-card h1 {
  margin-top: 0.35rem;
  color: var(--color-heading);
}

.hint {
  margin-top: 0.45rem;
  color: #adb6d4;
  font-size: 0.95rem;
}

.auth-form {
  margin-top: 1rem;
  display: grid;
  gap: 0.9rem;
}

label {
  display: grid;
  gap: 0.35rem;
  color: #d7def4;
  font-size: 0.9rem;
}

input {
  border: 1px solid rgba(170, 184, 255, 0.28);
  background: rgba(5, 8, 16, 0.5);
  color: #e8ecff;
  border-radius: 10px;
  min-height: 42px;
  padding: 0 0.8rem;
}

input:focus {
  outline: none;
  border-color: rgba(102, 160, 255, 0.9);
  box-shadow: 0 0 0 3px rgba(80, 137, 255, 0.2);
}

button {
  min-height: 44px;
  border: 0;
  border-radius: 10px;
  color: #f7f9ff;
  font-weight: 700;
  cursor: pointer;
  background: linear-gradient(120deg, #4f63ff, #31beff);
  transition: transform 200ms ease, box-shadow 200ms ease, opacity 200ms ease;
}

button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 12px 26px rgba(70, 130, 255, 0.34);
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error {
  margin-top: 0.8rem;
  color: #ff8f9d;
}

.alt-action {
  margin-top: 0.6rem;
  font-size: 0.9rem;
  color: #aeb5cf;
}

.alt-action a {
  color: #8dc7ff;
}

@keyframes card-in {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
