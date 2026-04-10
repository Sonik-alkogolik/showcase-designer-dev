<template>
  <section class="page">
    <header class="page-head">
      <h1>Маркетинг</h1>
      <p>MVP-план кампаний по выбранному магазину.</p>
    </header>

    <div v-if="!selectedShopId" class="empty-box">Выберите магазин в верхнем селекторе.</div>

    <template v-else>
      <form class="form" @submit.prevent="addCampaign">
        <label>
          Название кампании
          <input v-model="draft.name" type="text" required>
        </label>
        <label>
          Канал
          <select v-model="draft.channel">
            <option value="telegram">Telegram</option>
            <option value="webapp">WebApp</option>
            <option value="manual">Manual</option>
          </select>
        </label>
        <label>
          Оффер
          <input v-model="draft.offer" type="text" placeholder="Скидка 10% на первый заказ">
        </label>
        <button class="btn-primary" type="submit">Добавить кампанию</button>
      </form>

      <div v-if="campaigns.length === 0" class="empty-box">Кампаний пока нет.</div>
      <ul v-else class="campaign-list">
        <li v-for="item in campaigns" :key="item.id" class="campaign-item">
          <div>
            <h3>{{ item.name }}</h3>
            <p>{{ item.channel }} · {{ item.offer || 'без оффера' }}</p>
          </div>
          <button class="btn-ghost" type="button" @click="removeCampaign(item.id)">Удалить</button>
        </li>
      </ul>
    </template>
  </section>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import { useDashboardContext } from '../../composables/useDashboardContext'

const { selectedShopId } = useDashboardContext()

const campaigns = ref([])
const draft = reactive({
  name: '',
  channel: 'telegram',
  offer: '',
})

const storageKey = () => `dashboard_marketing_${selectedShopId.value || 'none'}`

const loadCampaigns = () => {
  if (!selectedShopId.value) {
    campaigns.value = []
    return
  }
  try {
    campaigns.value = JSON.parse(localStorage.getItem(storageKey()) || '[]')
  } catch {
    campaigns.value = []
  }
}

const persist = () => {
  localStorage.setItem(storageKey(), JSON.stringify(campaigns.value))
}

const addCampaign = () => {
  if (!selectedShopId.value || !draft.name.trim()) return
  campaigns.value.unshift({
    id: Date.now(),
    name: draft.name.trim(),
    channel: draft.channel,
    offer: draft.offer.trim(),
  })
  draft.name = ''
  draft.offer = ''
  persist()
}

const removeCampaign = (id) => {
  campaigns.value = campaigns.value.filter((item) => item.id !== id)
  persist()
}

watch(selectedShopId, loadCampaigns, { immediate: true })
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
  gap: 0.65rem;
}

label {
  display: grid;
  gap: 0.25rem;
  color: #3e5170;
}

input,
select {
  border: 1px solid #cbd7ee;
  border-radius: 9px;
  padding: 0.52rem 0.6rem;
}

.btn-primary {
  justify-self: start;
  border: 0;
  border-radius: 10px;
  padding: 0.54rem 0.74rem;
  background: #2563eb;
  color: #fff;
  cursor: pointer;
}

.campaign-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.5rem;
}

.campaign-item {
  border: 1px solid #d6dff1;
  border-radius: 12px;
  background: #fff;
  padding: 0.75rem;
  display: flex;
  justify-content: space-between;
  gap: 0.8rem;
  align-items: center;
}

.campaign-item h3 {
  margin: 0;
  color: #1b3561;
}

.campaign-item p {
  margin: 0.18rem 0 0;
  color: #4f6280;
}

.btn-ghost {
  border: 1px solid #c5d5f2;
  border-radius: 10px;
  padding: 0.45rem 0.65rem;
  color: #223a67;
  background: #f5f9ff;
  cursor: pointer;
}

.empty-box {
  border: 1px dashed #c8d3ea;
  border-radius: 12px;
  background: #fff;
  padding: 1rem;
  color: #546480;
}
</style>
