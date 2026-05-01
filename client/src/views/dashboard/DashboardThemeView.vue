<template>
  <section class="page">
    <header class="page-head">
      <h1>Тема mini-app</h1>
      <p>Настройка внешнего вида витрины для выбранного магазина.</p>
    </header>

    <div v-if="!selectedShopId" class="empty-box">Выберите магазин в верхнем селекторе.</div>

    <template v-else>
      <div v-if="loading" class="empty-box">Загрузка темы...</div>
      <form v-else class="theme-form" @submit.prevent="saveTheme">
        <div class="preset-row">
          <button type="button" class="preset-btn" @click="applyPreset('ocean')">Ocean</button>
          <button type="button" class="preset-btn" @click="applyPreset('forest')">Forest</button>
          <button type="button" class="preset-btn" @click="applyPreset('sunset')">Sunset</button>
          <button type="button" class="preset-btn reset" @click="resetTheme">Сброс</button>
        </div>

        <div class="theme-grid">
          <label>
            <span>Фон (начало)</span>
            <input v-model="theme.background_start" type="color">
          </label>
          <label>
            <span>Фон (конец)</span>
            <input v-model="theme.background_end" type="color">
          </label>
          <label>
            <span>Текст</span>
            <input v-model="theme.text_color" type="color">
          </label>
          <label>
            <span>Dots слайдера</span>
            <input v-model="theme.dots_color" type="color">
          </label>
        </div>

        <div class="preview" :style="previewStyle">
          <div class="preview-top">
            <span>mini-app preview</span>
            <div class="dot" />
          </div>
          <div class="card">
            <p class="title">Карточка товара</p>
            <p class="price">1 290 ₽</p>
          </div>
        </div>

        <div class="actions">
          <button class="btn-primary" type="submit" :disabled="saving">
            {{ saving ? 'Сохраняю...' : 'Сохранить тему' }}
          </button>
        </div>
        <p v-if="message" class="message">{{ message }}</p>
      </form>
    </template>
  </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import axios from 'axios'
import { useDashboardContext } from '../../composables/useDashboardContext'

const { selectedShopId } = useDashboardContext()

const DEFAULT_THEME = {
  background_start: '#070B18',
  background_end: '#0D1326',
  text_color: '#EFF6FF',
  dots_color: '#38E8FF',
}

const PRESETS = {
  ocean: { background_start: '#070B18', background_end: '#0D3A66', text_color: '#EAF6FF', dots_color: '#46D8FF' },
  forest: { background_start: '#0D1A12', background_end: '#1E4528', text_color: '#ECFFF0', dots_color: '#6DFF92' },
  sunset: { background_start: '#2A0E15', background_end: '#7A2E2E', text_color: '#FFF2E8', dots_color: '#FFB067' },
}

const loading = ref(false)
const saving = ref(false)
const message = ref('')
const theme = reactive({ ...DEFAULT_THEME })

const fillTheme = (value) => {
  Object.assign(theme, DEFAULT_THEME, value || {})
}

const loadTheme = async () => {
  message.value = ''
  if (!selectedShopId.value) return
  loading.value = true
  try {
    const response = await axios.get(`/api/shops/${selectedShopId.value}`)
    fillTheme(response.data?.shop?.theme_settings)
  } catch (error) {
    console.error('Failed to load theme:', error)
  } finally {
    loading.value = false
  }
}

const applyPreset = (key) => fillTheme(PRESETS[key] || DEFAULT_THEME)
const resetTheme = () => fillTheme(DEFAULT_THEME)

const saveTheme = async () => {
  if (!selectedShopId.value) return
  saving.value = true
  message.value = ''
  try {
    await axios.patch(`/api/shops/${selectedShopId.value}`, {
      theme_settings: { ...theme },
    })
    message.value = 'Тема сохранена'
  } catch (error) {
    console.error('Failed to save theme:', error)
    message.value = 'Ошибка сохранения темы'
  } finally {
    saving.value = false
  }
}

const previewStyle = computed(() => ({
  '--bg-start': theme.background_start,
  '--bg-end': theme.background_end,
  '--text-color': theme.text_color,
  '--dot-color': theme.dots_color,
}))

watch(selectedShopId, loadTheme, { immediate: true })
</script>

<style scoped>
.page { display: grid; gap: 0.9rem; }
.page-head h1 { margin: 0; color: #0f2a52; }
.page-head p { margin: 0.2rem 0 0; color: #4b5d79; }
.theme-form { border: 1px solid #d6dff1; border-radius: 12px; background: #fff; padding: 0.9rem; display: grid; gap: 0.8rem; max-width: 760px; }
.preset-row { display: flex; gap: 0.45rem; flex-wrap: wrap; }
.preset-btn { border: 1px solid #c7d7f2; background: #f2f7ff; color: #2f4f83; border-radius: 8px; padding: 0.28rem 0.58rem; cursor: pointer; }
.preset-btn.reset { background: #fff5f5; border-color: #f4c7cc; color: #a23644; }
.theme-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.7rem; }
.theme-grid label { display: grid; gap: 0.2rem; }
.theme-grid span { color: #33517f; font-size: 0.86rem; }
.theme-grid input[type='color'] { width: 100%; height: 36px; border: 1px solid #c8d7f2; border-radius: 8px; padding: 0.1rem; background: #fff; }
.preview { border-radius: 10px; padding: 0.55rem; background: linear-gradient(140deg, var(--bg-start), var(--bg-end)); color: var(--text-color); border: 1px solid #d2dff6; }
.preview-top { display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; }
.dot { width: 11px; height: 11px; border-radius: 999px; background: var(--dot-color); }
.card { margin-top: 0.45rem; padding: 0.45rem; border-radius: 8px; background: rgba(255,255,255,.1); }
.title,.price { margin: 0; }
.price { margin-top: 0.22rem; font-weight: 700; }
.btn-primary { border: 0; border-radius: 10px; padding: 0.55rem 0.75rem; background: #2563eb; color: #fff; cursor: pointer; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.message { margin: 0; color: #166534; }
.empty-box { border: 1px dashed #c8d3ea; border-radius: 12px; background: #fff; padding: 1rem; color: #546480; }
@media (max-width: 760px) { .theme-grid { grid-template-columns: 1fr; } }
</style>

