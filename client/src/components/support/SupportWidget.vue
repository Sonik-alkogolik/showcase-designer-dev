<template>
  <div v-if="isAuthenticated && !isWebApp" class="support-widget">
    <button class="support-fab" type="button" aria-label="Открыть поддержку" @click="togglePanel">
      ?
    </button>

    <section v-if="isOpen" class="support-panel">
      <header class="support-head">
        <div>
          <p>Поддержка e-TGO</p>
          <h3>Чем помочь?</h3>
        </div>
        <button type="button" class="icon-btn" aria-label="Закрыть" @click="isOpen = false">×</button>
      </header>

      <form class="support-form" @submit.prevent="submitTicket">
        <label>
          Категория
          <select v-model="form.category" required>
            <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
          </select>
        </label>

        <label>
          Быстрая тема
          <select v-model="form.preset" @change="applyPreset">
            <option value="">Своя тема</option>
            <option v-for="preset in presets" :key="preset" :value="preset">{{ preset }}</option>
          </select>
        </label>

        <label>
          Тема
          <input v-model.trim="form.subject" type="text" maxlength="255" required placeholder="Например: не сохраняется токен бота">
        </label>

        <label>
          Сообщение
          <textarea v-model.trim="form.message" rows="4" required placeholder="Опишите, что произошло и на каком шаге"></textarea>
        </label>

        <label>
          Скриншот
          <input type="file" accept="image/*" @change="handleFile">
        </label>

        <p v-if="error" class="support-error">{{ error }}</p>
        <p v-if="successMessage" class="support-success">{{ successMessage }}</p>

        <button class="submit-btn" type="submit" :disabled="sending">
          {{ sending ? 'Отправляем...' : 'Создать тикет' }}
        </button>
      </form>

      <div v-if="tickets.length" class="ticket-list">
        <p class="ticket-list-title">Последние обращения</p>
        <button v-for="ticket in tickets" :key="ticket.id" type="button" class="ticket-row" @click="openTicket(ticket)">
          <span>#{{ ticket.id }} {{ ticket.subject }}</span>
          <small>{{ ticket.status_label }}</small>
        </button>
      </div>

      <div v-if="selectedTicket" class="ticket-history">
        <div class="ticket-history-head">
          <strong>#{{ selectedTicket.id }} {{ selectedTicket.subject }}</strong>
          <button type="button" class="icon-btn" @click="selectedTicket = null">×</button>
        </div>
        <div class="messages">
          <article v-for="message in selectedTicket.messages" :key="message.id" :class="['message', message.sender_type]">
            <span>{{ message.sender_name || (message.sender_type === 'admin' ? 'Администратор' : 'Вы') }}</span>
            <p>{{ message.body }}</p>
          </article>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useAuth } from '../../composables/useAuth'

const route = useRoute()
const { isAuthenticated } = useAuth()
const isOpen = ref(false)
const sending = ref(false)
const error = ref('')
const successMessage = ref('')
const categories = ref({
  bot_problem: 'Проблема с ботом',
  products_problem: 'Проблема с товарами',
  bug: 'Ошибка',
  question: 'Вопрос',
})
const presets = ref([
  'Не могу авторизоваться',
  'Не получается прикрепить бота',
  'Не получается прикрепить токен бота',
  'Не получается создать магазин',
])
const tickets = ref([])
const selectedTicket = ref(null)
const screenshot = ref(null)

const form = reactive({
  category: 'question',
  preset: '',
  subject: '',
  message: '',
})

const isWebApp = computed(() => route.path.startsWith('/app'))

const togglePanel = async () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    await loadTickets()
  }
}

const loadMeta = async () => {
  try {
    const response = await axios.get('/api/support/meta')
    categories.value = response.data.categories || categories.value
    presets.value = response.data.presets || presets.value
  } catch (e) {
    console.warn('Support meta load failed:', e)
  }
}

const loadTickets = async () => {
  try {
    const response = await axios.get('/api/support/tickets')
    tickets.value = response.data.tickets || []
  } catch (e) {
    console.warn('Support tickets load failed:', e)
  }
}

const applyPreset = () => {
  if (!form.preset) return
  form.subject = form.preset
  if (!form.message) {
    form.message = `${form.preset}. Опишите, пожалуйста, что вы уже пробовали и на каком шаге остановились.`
  }
}

const handleFile = (event) => {
  screenshot.value = event.target.files?.[0] || null
}

const resetForm = () => {
  form.category = 'question'
  form.preset = ''
  form.subject = ''
  form.message = ''
  screenshot.value = null
}

const submitTicket = async () => {
  error.value = ''
  successMessage.value = ''
  sending.value = true

  try {
    const payload = new FormData()
    payload.append('category', form.category)
    payload.append('preset', form.preset)
    payload.append('subject', form.subject)
    payload.append('message', form.message)
    payload.append('current_url', window.location.href)
    payload.append('browser', window.navigator.userAgent)
    payload.append('reported_at', new Date().toISOString())
    if (screenshot.value) {
      payload.append('screenshot', screenshot.value)
    }

    const response = await axios.post('/api/support/tickets', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    successMessage.value = `Тикет #${response.data.ticket.id} создан`
    selectedTicket.value = response.data.ticket
    resetForm()
    await loadTickets()
  } catch (e) {
    error.value = e.response?.data?.message || 'Не удалось создать тикет'
  } finally {
    sending.value = false
  }
}

const openTicket = async (ticket) => {
  try {
    const response = await axios.get(`/api/support/tickets/${ticket.id}`)
    selectedTicket.value = response.data.ticket
  } catch (e) {
    error.value = 'Не удалось открыть историю тикета'
  }
}

onMounted(() => {
  if (isAuthenticated.value) {
    loadMeta()
  }
})
</script>

<style scoped>
.support-widget {
  position: fixed;
  right: 1rem;
  bottom: 1rem;
  z-index: 1100;
}

.support-fab {
  width: 52px;
  height: 52px;
  border: 0;
  border-radius: 50%;
  background: #2563eb;
  color: #fff;
  font-size: 1.35rem;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 14px 34px rgba(37, 99, 235, 0.35);
}

.support-panel {
  position: absolute;
  right: 0;
  bottom: 64px;
  width: min(420px, calc(100vw - 2rem));
  max-height: min(760px, calc(100vh - 6rem));
  overflow: auto;
  border: 1px solid #d7e0f2;
  border-radius: 14px;
  background: #fff;
  color: #183052;
  box-shadow: 0 20px 60px rgba(16, 37, 70, 0.24);
}

.support-head {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  border-bottom: 1px solid #e0e7f3;
  padding: 1rem;
}

.support-head p {
  margin: 0;
  color: #64748b;
  font-size: 0.78rem;
  text-transform: uppercase;
  font-weight: 700;
}

.support-head h3 {
  margin: 0.2rem 0 0;
}

.icon-btn {
  width: 32px;
  height: 32px;
  border: 1px solid #d3def0;
  border-radius: 8px;
  background: #f7faff;
  color: #294464;
  cursor: pointer;
}

.support-form {
  display: grid;
  gap: 0.75rem;
  padding: 1rem;
}

.support-form label {
  display: grid;
  gap: 0.3rem;
  color: #314969;
  font-size: 0.9rem;
  font-weight: 700;
}

.support-form input,
.support-form select,
.support-form textarea {
  width: 100%;
  border: 1px solid #cfd9eb;
  border-radius: 9px;
  padding: 0.62rem 0.7rem;
  color: #1e2f48;
  background: #fbfdff;
}

.submit-btn {
  border: 0;
  border-radius: 9px;
  padding: 0.72rem 1rem;
  background: #2563eb;
  color: #fff;
  font-weight: 800;
  cursor: pointer;
}

.submit-btn:disabled {
  background: #8aa4d8;
  cursor: wait;
}

.support-error,
.support-success {
  margin: 0;
  border-radius: 9px;
  padding: 0.55rem 0.7rem;
  font-size: 0.9rem;
}

.support-error {
  background: #fff1f2;
  color: #a21d3a;
}

.support-success {
  background: #ecfdf3;
  color: #17613a;
}

.ticket-list,
.ticket-history {
  border-top: 1px solid #e0e7f3;
  padding: 0.85rem 1rem 1rem;
}

.ticket-list-title {
  margin: 0 0 0.45rem;
  color: #52657f;
  font-size: 0.85rem;
  font-weight: 800;
}

.ticket-row {
  width: 100%;
  display: flex;
  justify-content: space-between;
  gap: 0.7rem;
  border: 0;
  border-radius: 8px;
  padding: 0.55rem 0.5rem;
  background: transparent;
  color: #1d3557;
  text-align: left;
  cursor: pointer;
}

.ticket-row:hover {
  background: #f2f6ff;
}

.ticket-row span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ticket-row small {
  color: #64748b;
  white-space: nowrap;
}

.ticket-history-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.7rem;
}

.messages {
  display: grid;
  gap: 0.55rem;
  margin-top: 0.7rem;
}

.message {
  border-radius: 10px;
  padding: 0.65rem 0.75rem;
  background: #f3f6fb;
}

.message.admin {
  background: #eaf2ff;
}

.message span {
  display: block;
  color: #516580;
  font-size: 0.78rem;
  font-weight: 800;
}

.message p {
  margin: 0.25rem 0 0;
  white-space: pre-wrap;
}

@media (max-width: 560px) {
  .support-widget {
    right: 0.75rem;
    bottom: 0.75rem;
  }
}
</style>
