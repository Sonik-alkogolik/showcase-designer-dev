<template>
  <section class="page">
    <header class="page-head">
      <h1>Сообщение менеджеру</h1>
      <p>Настройка шаблона сообщения из mini-app. Можно использовать маркер <code>{items}</code> для списка товаров.</p>
    </header>

    <div v-if="!selectedShopId" class="empty-box">Выберите магазин в верхнем селекторе.</div>

    <template v-else>
      <div v-if="loading" class="empty-box">Загрузка...</div>
      <form v-else class="form" @submit.prevent="save">
        <label>
          Шаблон сообщения
          <textarea
            v-model="templateText"
            rows="8"
            placeholder="Добрый день! Хотел бы приобрести товар или товары."
          />
        </label>
        <p class="hint">Если поле пустое, используется шаблон по умолчанию.</p>
        <div class="actions">
          <button class="btn-primary" type="submit" :disabled="saving">
            {{ saving ? 'Сохраняю...' : 'Сохранить' }}
          </button>
        </div>
        <p v-if="message" class="message">{{ message }}</p>
      </form>
    </template>
  </section>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { useDashboardContext } from '../../composables/useDashboardContext'

const { selectedShopId } = useDashboardContext()

const loading = ref(false)
const saving = ref(false)
const message = ref('')
const templateText = ref('')

const load = async () => {
  message.value = ''
  if (!selectedShopId.value) {
    templateText.value = ''
    return
  }

  loading.value = true
  try {
    const response = await axios.get(`/api/shops/${selectedShopId.value}`)
    templateText.value = String(response.data?.shop?.manager_message_template || '')
  } catch (error) {
    console.error('Failed to load manager message template:', error)
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
      manager_message_template: templateText.value || null,
    })
    message.value = 'Сохранено'
  } catch (error) {
    console.error('Failed to save manager message template:', error)
    message.value = 'Ошибка сохранения'
  } finally {
    saving.value = false
  }
}

watch(selectedShopId, load, { immediate: true })
</script>

<style scoped>
.page { display: grid; gap: 0.9rem; }
.page-head h1 { margin: 0; color: #0f2a52; }
.page-head p { margin: 0.2rem 0 0; color: #4b5d79; }
.form { border: 1px solid #d6dff1; border-radius: 12px; background: #fff; padding: 0.9rem; display: grid; gap: 0.7rem; max-width: 760px; }
label { display: grid; gap: 0.25rem; color: #3e5170; }
textarea { border: 1px solid #cbd7ee; border-radius: 9px; padding: 0.52rem 0.6rem; resize: vertical; }
.hint { margin: 0; color: #6b7c97; font-size: 0.92rem; }
.actions { display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap; }
.btn-primary { border: 0; border-radius: 10px; padding: 0.54rem 0.74rem; background: #2563eb; color: #fff; cursor: pointer; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.message { margin: 0; color: #166534; }
.empty-box { border: 1px dashed #c8d3ea; border-radius: 12px; background: #fff; padding: 1rem; color: #546480; }
</style>

