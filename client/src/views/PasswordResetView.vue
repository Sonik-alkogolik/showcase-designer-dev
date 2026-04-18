<template>
  <section class="auth-page">
    <div class="auth-card">
      <p class="kicker">Новый пароль</p>
      <h1>Завершение восстановления</h1>
      <p class="hint">Введите новый пароль для входа в аккаунт.</p>

      <form class="auth-form" @submit.prevent="handleReset">
        <label>
          Email
          <input v-model="email" placeholder="you@example.com" type="email" required />
        </label>

        <label>
          Новый пароль
          <input v-model="password" type="password" placeholder="Минимум 8 символов" required />
        </label>

        <label>
          Повторите пароль
          <input v-model="passwordConfirmation" type="password" placeholder="Повтор пароля" required />
        </label>

        <button type="submit">Сохранить новый пароль</button>
      </form>

      <p v-if="successMessage" class="success">{{ successMessage }}</p>
      <p v-if="error" class="error">{{ error }}</p>
      <p class="alt-action">
        <router-link to="/login">Ко входу</router-link>
      </p>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const route = useRoute()
const { resetPasswordByEmail } = useAuth()

const email = ref(String(route.query.email || ''))
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const successMessage = ref('')

const handleReset = async () => {
  error.value = ''
  successMessage.value = ''

  const token = String(route.params.token || '')
  if (!token) {
    error.value = 'Невалидная ссылка восстановления'
    return
  }

  const result = await resetPasswordByEmail({
    token,
    email: email.value,
    password: password.value,
    password_confirmation: passwordConfirmation.value,
  })

  if (!result.success) {
    error.value = result.error
    return
  }

  password.value = ''
  passwordConfirmation.value = ''
  successMessage.value = 'Пароль обновлён. Теперь можно войти.'
}
</script>

<style scoped>
.auth-page {
  min-height: calc(100vh - 140px);
  display: grid;
  place-items: center;
}

.auth-card {
  width: min(500px, 100%);
  border-radius: 20px;
  padding: 1.3rem 1.2rem;
  border: 1px solid rgba(174, 189, 255, 0.2);
  background: linear-gradient(170deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
  backdrop-filter: blur(8px);
}

.kicker {
  color: var(--color-muted);
  text-transform: uppercase;
  letter-spacing: 0.2em;
  font-size: 0.75rem;
}

.hint {
  margin-top: 0.45rem;
  color: #adb6d4;
  font-size: 0.95rem;
}

.auth-form {
  margin-top: 1rem;
  display: grid;
  gap: 0.75rem;
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

button {
  min-height: 44px;
  border: 0;
  border-radius: 10px;
  color: #f7f9ff;
  font-weight: 700;
  cursor: pointer;
  background: linear-gradient(120deg, #4f63ff, #31beff);
}

.success {
  margin-top: 0.8rem;
  color: #66e2a3;
}

.error {
  margin-top: 0.8rem;
  color: #ff8f9d;
}

.alt-action {
  margin-top: 0.6rem;
}

.alt-action a {
  color: #8dc7ff;
}
</style>

