<template>
  <section class="auth-page">
    <div class="auth-card">
      <p class="kicker">Восстановление пароля</p>
      <h1>Сброс доступа</h1>
      <p class="hint">Выберите способ восстановления: по email или через Telegram.</p>

      <div class="tabs">
        <button
          type="button"
          :class="{ active: mode === 'email' }"
          @click="mode = 'email'"
        >
          Email
        </button>
        <button
          type="button"
          :class="{ active: mode === 'telegram' }"
          @click="mode = 'telegram'"
        >
          Telegram
        </button>
      </div>

      <form class="auth-form" @submit.prevent="handleSubmit">
        <label>
          Email
          <input v-model="email" placeholder="you@example.com" type="email" required />
        </label>

        <label v-if="mode === 'telegram'">
          Одноразовый код из Telegram
          <input
            v-model="telegramToken"
            placeholder="Например: A1B2C3D4"
            type="text"
            autocomplete="one-time-code"
          />
        </label>

        <button v-if="mode === 'email'" type="submit">Отправить письмо</button>
        <button v-else type="button" @click="requestTelegramToken">Отправить код в Telegram</button>
        <button v-if="mode === 'telegram'" type="submit">Подтвердить код и получить временный пароль</button>
      </form>

      <p v-if="successMessage" class="success">{{ successMessage }}</p>
      <p v-if="error" class="error">{{ error }}</p>
      <p class="alt-action">
        <router-link to="/login">Вернуться ко входу</router-link>
      </p>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useAuth } from '@/composables/useAuth'

const { forgotPasswordByEmail, forgotPasswordByTelegram, resetPasswordByTelegram } = useAuth()

const mode = ref('email')
const email = ref('')
const telegramToken = ref('')
const error = ref('')
const successMessage = ref('')

const clearMessages = () => {
  error.value = ''
  successMessage.value = ''
}

const requestTelegramToken = async () => {
  clearMessages()
  const result = await forgotPasswordByTelegram(email.value)
  if (!result.success) {
    error.value = result.error
    return
  }
  successMessage.value = result.message
}

const handleSubmit = async () => {
  clearMessages()

  if (mode.value === 'email') {
    const result = await forgotPasswordByEmail(email.value)
    if (!result.success) {
      error.value = result.error
      return
    }
    successMessage.value = result.message
    return
  }

  if (!telegramToken.value.trim()) {
    error.value = 'Введите код из Telegram'
    return
  }

  const result = await resetPasswordByTelegram(email.value, telegramToken.value)
  if (!result.success) {
    error.value = result.error
    return
  }

  telegramToken.value = ''
  successMessage.value = result.message
}
</script>

<style scoped>
.auth-page {
  min-height: calc(100vh - 140px);
  display: grid;
  place-items: center;
}

.auth-card {
  width: min(520px, 100%);
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

.tabs {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}

.tabs button {
  min-height: 38px;
  border-radius: 10px;
  border: 1px solid rgba(170, 184, 255, 0.28);
  background: rgba(5, 8, 16, 0.5);
  color: #d7def4;
  padding: 0 0.75rem;
}

.tabs button.active {
  border-color: rgba(102, 160, 255, 0.9);
  color: #f7f9ff;
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
  font-size: 0.9rem;
}

.alt-action a {
  color: #8dc7ff;
}
</style>

