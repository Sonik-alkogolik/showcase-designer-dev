<template>
  <section class="page">
    <header class="page-head">
      <h1>Настройки</h1>
      <p>Быстрое редактирование ключевых параметров выбранного магазина.</p>
    </header>

    <div v-if="!selectedShopId" class="empty-box">Выберите магазин в верхнем селекторе.</div>

    <template v-else>
      <div v-if="loading" class="empty-box">Загрузка настроек...</div>
      <form v-else class="form" @submit.prevent="save">
        <label>
          Название магазина
          <input v-model="form.name" type="text" required>
        </label>
        <label>
          Доставка (название)
          <input v-model="form.delivery_name" type="text" required>
        </label>
        <label>
          Доставка (цена)
          <input v-model.number="form.delivery_price" type="number" min="0" step="0.01" required>
        </label>
        <label>
          Telegram username для уведомлений
          <input v-model="form.notification_username" type="text" placeholder="@example">
        </label>
        <label>
          Webhook URL
          <input v-model="form.webhook_url" type="url" placeholder="https://example.com/webhook">
        </label>
        <label>
          notification_chat_id
          <input v-model="form.notification_chat_id" type="text" placeholder="123456789">
        </label>

        <div class="actions">
          <button class="btn-primary" type="submit" :disabled="saving">
            {{ saving ? 'Сохраняю...' : 'Сохранить' }}
          </button>
          <router-link class="btn-ghost" :to="`/shops/${selectedShopId}/settings`">Открыть полный экран настроек</router-link>
        </div>
        <p v-if="message" class="message">{{ message }}</p>
      </form>
    </template>
  </section>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import axios from 'axios'
import { useDashboardContext } from '../../composables/useDashboardContext'

const { selectedShopId } = useDashboardContext()

const loading = ref(false)
const saving = ref(false)
const message = ref('')
const form = reactive({
  name: '',
  delivery_name: '',
  delivery_price: 0,
  notification_username: '',
  webhook_url: '',
  notification_chat_id: '',
})

const fillFromShop = (shop) => {
  form.name = shop?.name || ''
  form.delivery_name = shop?.delivery_name || ''
  form.delivery_price = Number(shop?.delivery_price || 0)
  form.notification_username = shop?.notification_username || ''
  form.webhook_url = shop?.webhook_url || ''
  form.notification_chat_id = shop?.notification_chat_id || ''
}

const loadSettings = async () => {
  message.value = ''
  if (!selectedShopId.value) {
    fillFromShop(null)
    return
  }

  loading.value = true
  try {
    const response = await axios.get(`/api/shops/${selectedShopId.value}`)
    fillFromShop(response.data?.shop || null)
  } catch (error) {
    console.error('Failed to load shop settings:', error)
  } finally {
    loading.value = false
  }
}

const save = async () => {
  if (!selectedShopId.value) return
  saving.value = true
  message.value = ''
  try {
    await axios.put(`/api/shops/${selectedShopId.value}`, {
      name: form.name,
      delivery_name: form.delivery_name,
      delivery_price: Number(form.delivery_price || 0),
      notification_username: form.notification_username || null,
      webhook_url: form.webhook_url || null,
      notification_chat_id: form.notification_chat_id || null,
    })
    message.value = 'Сохранено'
  } catch (error) {
    console.error('Failed to save shop settings:', error)
    message.value = 'Ошибка сохранения'
  } finally {
    saving.value = false
  }
}

watch(selectedShopId, loadSettings, { immediate: true })
</script>

<style scoped>
.page {
  display: grid;
  gap: 0.9rem;
}

.page-head h1 {
  margin: 0;
  color: #0f2a52;
}

.page-head p {
  margin: 0.2rem 0 0;
  color: #4b5d79;
}

.form {
  border: 1px solid #d6dff1;
  border-radius: 12px;
  background: #fff;
  padding: 0.9rem;
  display: grid;
  gap: 0.7rem;
  max-width: 760px;
}

label {
  display: grid;
  gap: 0.25rem;
  color: #3e5170;
}

input {
  border: 1px solid #cbd7ee;
  border-radius: 9px;
  padding: 0.52rem 0.6rem;
}

.actions {
  display: flex;
  gap: 0.6rem;
  align-items: center;
  flex-wrap: wrap;
}

.btn-primary {
  border: 0;
  border-radius: 10px;
  padding: 0.54rem 0.74rem;
  background: #2563eb;
  color: #fff;
  cursor: pointer;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-ghost {
  text-decoration: none;
  border: 1px solid #c5d5f2;
  border-radius: 10px;
  padding: 0.5rem 0.68rem;
  color: #223a67;
  background: #f5f9ff;
}

.message {
  margin: 0;
  color: #166534;
}

.empty-box {
  border: 1px dashed #c8d3ea;
  border-radius: 12px;
  background: #fff;
  padding: 1rem;
  color: #546480;
}
</style>
