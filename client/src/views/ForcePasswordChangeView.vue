<template>
  <section class="auth-page">
    <div class="auth-card">
      <p class="kicker">Безопасность</p>
      <h1>Смена временного пароля</h1>
      <p class="hint">
        Вы вошли по временному паролю. Для продолжения работы задайте постоянный пароль.
      </p>

      <form class="auth-form" @submit.prevent="handleChange">
        <label>
          Текущий пароль
          <input v-model="currentPassword" type="password" required />
        </label>

        <label>
          Новый пароль
          <input v-model="password" type="password" placeholder="Минимум 8 символов" required />
        </label>

        <label>
          Повторите новый пароль
          <input v-model="passwordConfirmation" type="password" required />
        </label>

        <button type="submit">Обновить пароль</button>
      </form>

      <p v-if="successMessage" class="success">{{ successMessage }}</p>
      <p v-if="error" class="error">{{ error }}</p>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { forceChangePassword } = useAuth()

const currentPassword = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const successMessage = ref('')

const handleChange = async () => {
  error.value = ''
  successMessage.value = ''

  const result = await forceChangePassword(
    currentPassword.value,
    password.value,
    passwordConfirmation.value
  )

  if (!result.success) {
    error.value = result.error
    return
  }

  successMessage.value = result.message
  currentPassword.value = ''
  password.value = ''
  passwordConfirmation.value = ''
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
</style>

