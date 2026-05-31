<template>
  <section class="support-page">
    <header class="support-top">
      <div>
        <h1>Техподдержка</h1>
        <p>Все обращения и переписка с администратором в одном месте.</p>
      </div>
      <button type="button" class="refresh-btn" @click="loadTickets">Обновить</button>
    </header>

    <div class="support-workspace">
      <aside class="ticket-list">
        <div class="ticket-list-head">
          <strong>Ваши тикеты</strong>
          <span>{{ tickets.length }}</span>
        </div>

        <div v-if="loading" class="empty-state">Загрузка обращений...</div>
        <div v-else-if="!tickets.length" class="empty-state">
          Пока нет тикетов. Создайте обращение через кнопку «Помощь» справа снизу.
        </div>

        <button
          v-for="ticket in tickets"
          :key="ticket.id"
          type="button"
          :class="['ticket-item', { active: selectedTicket?.id === ticket.id }]"
          @click="selectTicket(ticket)"
        >
          <span class="ticket-subject">#{{ ticket.id }} {{ ticket.subject }}</span>
          <span class="ticket-meta">
            <b>{{ ticket.status_label }}</b>
            <small>{{ ticket.category_label }}</small>
          </span>
        </button>
      </aside>

      <main class="ticket-dialog">
        <div v-if="!selectedTicket" class="empty-dialog">
          Выберите тикет слева, чтобы открыть историю переписки.
        </div>

        <template v-else>
          <header class="dialog-head">
            <div>
              <p>{{ selectedTicket.category_label }}</p>
              <h2>#{{ selectedTicket.id }} {{ selectedTicket.subject }}</h2>
            </div>
            <span :class="['status-pill', selectedTicket.status]">{{ selectedTicket.status_label }}</span>
          </header>

          <div class="context-strip">
            <span v-if="selectedTicket.current_url">URL: {{ selectedTicket.current_url }}</span>
            <a v-if="selectedTicket.screenshot_url" :href="selectedTicket.screenshot_url" target="_blank" rel="noopener noreferrer">
              Открыть скриншот
            </a>
          </div>

          <div class="messages">
            <article
              v-for="message in selectedTicket.messages"
              :key="message.id"
              :class="['message', message.sender_type]"
            >
              <div class="message-author">
                <strong>{{ senderLabel(message) }}</strong>
                <span>{{ formatDate(message.created_at) }}</span>
              </div>
              <p>{{ message.body }}</p>
            </article>
          </div>

          <form class="reply-form" @submit.prevent="sendReply">
            <textarea
              v-model.trim="replyText"
              rows="4"
              :disabled="selectedTicket.status === 'closed' || sendingReply"
              placeholder="Напишите ответ администратору"
            ></textarea>
            <div class="reply-actions">
              <p v-if="replyError" class="reply-error">{{ replyError }}</p>
              <button type="submit" :disabled="!replyText || selectedTicket.status === 'closed' || sendingReply">
                {{ sendingReply ? 'Отправляем...' : 'Ответить' }}
              </button>
            </div>
          </form>
        </template>
      </main>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'

const tickets = ref([])
const selectedTicket = ref(null)
const loading = ref(false)
const sendingReply = ref(false)
const replyText = ref('')
const replyError = ref('')

const loadTickets = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/support/tickets')
    tickets.value = response.data.tickets || []
    if (selectedTicket.value) {
      const stillExists = tickets.value.find((ticket) => ticket.id === selectedTicket.value.id)
      if (stillExists) {
        await selectTicket(stillExists)
      }
    }
  } finally {
    loading.value = false
  }
}

const selectTicket = async (ticket) => {
  replyError.value = ''
  replyText.value = ''
  const response = await axios.get(`/api/support/tickets/${ticket.id}`)
  selectedTicket.value = response.data.ticket
}

const sendReply = async () => {
  if (!selectedTicket.value || !replyText.value) return

  sendingReply.value = true
  replyError.value = ''
  try {
    const response = await axios.post(`/api/support/tickets/${selectedTicket.value.id}/messages`, {
      message: replyText.value,
    })
    selectedTicket.value = response.data.ticket
    replyText.value = ''
    await loadTickets()
  } catch (error) {
    replyError.value = error.response?.data?.message || 'Не удалось отправить ответ'
  } finally {
    sendingReply.value = false
  }
}

const senderLabel = (message) => {
  if (message.sender_type === 'admin') return message.sender_name || 'Администратор'
  return message.sender_name || 'Вы'
}

const formatDate = (dateValue) => {
  if (!dateValue) return ''
  return new Date(dateValue).toLocaleString('ru-RU')
}

onMounted(loadTickets)
</script>

<style scoped>
.support-page {
  display: grid;
  gap: 1rem;
}

.support-top {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: end;
}

.support-top h1 {
  margin: 0;
  color: #0f2a52;
}

.support-top p {
  margin: 0.25rem 0 0;
  color: #526783;
}

.refresh-btn,
.reply-form button {
  border: 0;
  border-radius: 9px;
  background: #2563eb;
  color: #fff;
  padding: 0.62rem 0.9rem;
  font-weight: 800;
  cursor: pointer;
}

.support-workspace {
  display: grid;
  grid-template-columns: minmax(260px, 360px) minmax(0, 1fr);
  min-height: 560px;
  border: 1px solid #d6dff1;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
}

.ticket-list {
  border-right: 1px solid #dce5f4;
  background: #f7faff;
  overflow: auto;
}

.ticket-list-head {
  display: flex;
  justify-content: space-between;
  padding: 0.9rem;
  border-bottom: 1px solid #dce5f4;
  color: #213a62;
}

.ticket-item {
  width: 100%;
  display: grid;
  gap: 0.45rem;
  border: 0;
  border-bottom: 1px solid #e1e8f5;
  background: transparent;
  padding: 0.85rem 0.9rem;
  text-align: left;
  cursor: pointer;
}

.ticket-item.active,
.ticket-item:hover {
  background: #eaf2ff;
}

.ticket-subject {
  color: #15345f;
  font-weight: 800;
}

.ticket-meta {
  display: flex;
  justify-content: space-between;
  gap: 0.7rem;
  color: #5f718a;
  font-size: 0.82rem;
}

.ticket-dialog {
  display: grid;
  grid-template-rows: auto auto 1fr auto;
  min-width: 0;
}

.dialog-head {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem;
  border-bottom: 1px solid #dce5f4;
}

.dialog-head p {
  margin: 0;
  color: #60748f;
  font-size: 0.8rem;
  font-weight: 800;
  text-transform: uppercase;
}

.dialog-head h2 {
  margin: 0.25rem 0 0;
  color: #102c55;
  font-size: 1.15rem;
}

.status-pill {
  align-self: start;
  border-radius: 999px;
  padding: 0.28rem 0.65rem;
  background: #eef4ff;
  color: #244d87;
  font-size: 0.82rem;
  font-weight: 800;
}

.status-pill.closed {
  background: #f1f5f9;
  color: #64748b;
}

.status-pill.in_progress {
  background: #fff7ed;
  color: #9a4b05;
}

.context-strip {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  padding: 0.7rem 1rem;
  border-bottom: 1px solid #edf1f7;
  color: #64748b;
  font-size: 0.84rem;
}

.context-strip a {
  color: #2563eb;
  font-weight: 800;
}

.messages {
  display: grid;
  align-content: start;
  gap: 0.75rem;
  padding: 1rem;
  overflow: auto;
  background: #fbfdff;
}

.message {
  max-width: min(720px, 92%);
  border-radius: 12px;
  padding: 0.75rem 0.85rem;
  background: #eef3fb;
  color: #1f3554;
}

.message.admin {
  justify-self: end;
  background: #e8f1ff;
}

.message-author {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  color: #60748f;
  font-size: 0.78rem;
}

.message p {
  margin: 0.35rem 0 0;
  white-space: pre-wrap;
}

.reply-form {
  display: grid;
  gap: 0.65rem;
  border-top: 1px solid #dce5f4;
  padding: 1rem;
}

.reply-form textarea {
  width: 100%;
  border: 1px solid #cfd9eb;
  border-radius: 10px;
  padding: 0.75rem;
  resize: vertical;
}

.reply-actions {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
}

.reply-error {
  margin: 0;
  color: #a21d3a;
}

.empty-state,
.empty-dialog {
  padding: 1rem;
  color: #64748b;
}

.empty-dialog {
  align-self: center;
  justify-self: center;
}

@media (max-width: 860px) {
  .support-top,
  .reply-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .support-workspace {
    grid-template-columns: 1fr;
  }

  .ticket-list {
    border-right: 0;
    border-bottom: 1px solid #dce5f4;
    max-height: 320px;
  }
}
</style>
