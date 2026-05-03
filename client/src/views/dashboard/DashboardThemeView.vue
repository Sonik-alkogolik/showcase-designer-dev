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

        <div class="theme-layout">
          <div class="theme-controls">
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
              <label>
                <span>Название магазина</span>
                <input v-model="theme.shop_name_color" type="color">
              </label>
              <label>
                <span>Поиск</span>
                <input v-model="theme.search_color" type="color">
              </label>
              <label>
                <span>Категории</span>
                <input v-model="theme.categories_color" type="color">
              </label>
              <label>
                <span>Футер (текст)</span>
                <input v-model="theme.footer_text_color" type="color">
              </label>
              <label>
                <span>Футер (фон)</span>
                <input v-model="theme.footer_bg_color" type="color">
              </label>
              <label>
                <span>Карточка (фон)</span>
                <input v-model="theme.card_bg_color" type="color">
              </label>
              <label>
                <span>Карточка (заголовок)</span>
                <input v-model="theme.card_title_color" type="color">
              </label>
              <label>
                <span>Карточка (цена)</span>
                <input v-model="theme.card_price_color" type="color">
              </label>
              <label>
                <span>Кнопка (фон)</span>
                <input v-model="theme.card_button_bg_color" type="color">
              </label>
              <label>
                <span>Кнопка (текст)</span>
                <input v-model="theme.card_button_text_color" type="color">
              </label>
              <label>
                <span>Popup: Отправить (текст)</span>
                <input v-model="theme.manager_send_button_text_color" type="color">
              </label>
            </div>
          </div>

          <div class="preview" :style="previewStyle">
            <div class="preview-header">
              <div class="preview-title-wrap">
                <span class="preview-kicker">T-GO SHOP</span>
                <span class="preview-shop-name">Название магазина</span>
              </div>
              <button type="button" class="preview-search">🔍</button>
            </div>

            <div class="preview-category">
              <span>Весь магазин</span>
              <span>⌄</span>
            </div>

            <div class="preview-hero">
              <div class="preview-hero-overlay">
                <p class="preview-hero-name">Слайдер товара</p>
                <p class="preview-hero-price">12 000 ₽</p>
              </div>
            </div>

            <div class="preview-dots">
              <span class="preview-dot" />
              <span class="preview-dot active" />
              <span class="preview-dot" />
              <span class="preview-dot" />
            </div>

            <div class="card">
              <p class="title">Карточка товара</p>
              <p class="price">1 290 ₽</p>
              <button type="button" class="preview-add-btn">В корзину</button>
            </div>

            <button type="button" class="preview-send-btn">Отправить</button>

            <div class="preview-footer">
              <span>Главная</span>
              <span>Профиль</span>
            </div>
          </div>
        </div>

        <div class="actions">
          <button class="btn-primary" type="submit" :disabled="saving">
            {{ saving ? 'Сохраняю...' : 'Сохранить тему' }}
          </button>
          <button class="btn-reset" type="button" :disabled="saving" @click="resetAndSaveTheme">
            Сбросить тему
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
  shop_name_color: '#EFF6FF',
  search_color: '#EFF6FF',
  categories_color: '#FFFFFF',
  footer_text_color: '#9FB0D3',
  footer_bg_color: '#0A0F1E',
  card_bg_color: '#050B1D',
  card_title_color: '#EEF4FF',
  card_price_color: '#4CAF50',
  card_button_bg_color: '#38E8FF',
  card_button_text_color: '#00151A',
  manager_send_button_text_color: '#FFFFFF',
}

const PRESETS = {
  ocean: { background_start: '#070B18', background_end: '#0D3A66', text_color: '#EAF6FF', dots_color: '#46D8FF', shop_name_color: '#EAF6FF', search_color: '#EAF6FF', categories_color: '#F1F8FF', footer_text_color: '#BBD7F6', footer_bg_color: '#0A1633', card_bg_color: '#071225', card_title_color: '#EAF6FF', card_price_color: '#67E8F9', card_button_bg_color: '#46D8FF', card_button_text_color: '#041320' },
  forest: { background_start: '#0D1A12', background_end: '#1E4528', text_color: '#ECFFF0', dots_color: '#6DFF92', shop_name_color: '#ECFFF0', search_color: '#ECFFF0', categories_color: '#E8FFE9', footer_text_color: '#B3DAB8', footer_bg_color: '#0D1F16', card_bg_color: '#102418', card_title_color: '#ECFFF0', card_price_color: '#7BFFA6', card_button_bg_color: '#6DFF92', card_button_text_color: '#0A1B11' },
  sunset: { background_start: '#2A0E15', background_end: '#7A2E2E', text_color: '#FFF2E8', dots_color: '#FFB067', shop_name_color: '#FFF2E8', search_color: '#FFF2E8', categories_color: '#FFEBDD', footer_text_color: '#F0C6AF', footer_bg_color: '#35151D', card_bg_color: '#3A1A22', card_title_color: '#FFF2E8', card_price_color: '#FFC08A', card_button_bg_color: '#FFB067', card_button_text_color: '#2D120B' },
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
const resetAndSaveTheme = async () => {
  fillTheme(DEFAULT_THEME)
  await saveTheme()
}

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
  '--shop-name-color': theme.shop_name_color,
  '--search-color': theme.search_color,
  '--categories-color': theme.categories_color,
  '--footer-text-color': theme.footer_text_color,
  '--footer-bg-color': theme.footer_bg_color,
  '--card-bg-color': theme.card_bg_color,
  '--card-title-color': theme.card_title_color,
  '--card-price-color': theme.card_price_color,
  '--card-button-bg-color': theme.card_button_bg_color,
  '--card-button-text-color': theme.card_button_text_color,
  '--manager-send-button-text-color': theme.manager_send_button_text_color,
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
.theme-layout { display: grid; grid-template-columns: 1fr 320px; gap: 0.8rem; align-items: start; }
.theme-controls { min-width: 0; }
.theme-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.7rem; }
.theme-grid label { display: grid; gap: 0.2rem; }
.theme-grid span { color: #33517f; font-size: 0.86rem; }
.theme-grid input[type='color'] { width: 100%; height: 36px; border: 1px solid #c8d7f2; border-radius: 8px; padding: 0.1rem; background: #fff; }
.preview { border-radius: 10px; padding: 0.55rem; background: linear-gradient(140deg, var(--bg-start), var(--bg-end)); color: var(--text-color); border: 1px solid #d2dff6; }
.preview-header { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem; }
.preview-title-wrap { min-width: 0; display: grid; gap: 0.1rem; }
.preview-kicker { font-size: 0.63rem; letter-spacing: 0.08em; color: #7ed7ff; text-transform: uppercase; }
.preview-shop-name { font-size: 0.95rem; font-weight: 700; color: var(--shop-name-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.preview-search { width: 34px; height: 34px; border-radius: 10px; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.08); color: var(--search-color); cursor: default; }
.preview-category { height: 36px; border-radius: 10px; border: 1px solid rgba(255,255,255,.2); background: rgba(255,255,255,.08); color: var(--categories-color); display: flex; align-items: center; justify-content: space-between; padding: 0 0.55rem; font-size: 0.84rem; }
.preview-hero { margin-top: 0.5rem; border-radius: 10px; min-height: 110px; border: 1px solid rgba(255,255,255,.16); background: linear-gradient(145deg, rgba(255,255,255,.22), rgba(255,255,255,.05)); position: relative; overflow: hidden; }
.preview-hero-overlay { position: absolute; inset: auto 0 0; padding: 0.5rem; background: linear-gradient(180deg, rgba(0,0,0,0), rgba(0,0,0,.55)); }
.preview-hero-name { margin: 0; font-size: 0.83rem; font-weight: 700; }
.preview-hero-price { margin: 0.1rem 0 0; font-size: 0.86rem; font-weight: 700; color: #8dffb4; }
.preview-dots { display: flex; justify-content: center; gap: 0.3rem; margin-top: 0.45rem; }
.preview-dot { width: 8px; height: 8px; border-radius: 999px; background: rgba(255,255,255,.3); }
.preview-dot.active { background: var(--dot-color); }
.card { margin-top: 0.5rem; padding: 0.45rem; border-radius: 8px; background: var(--card-bg-color); border: 1px solid rgba(255,255,255,.1); }
.title,.price { margin: 0; }
.title { color: var(--card-title-color); }
.price { margin-top: 0.22rem; font-weight: 700; color: var(--card-price-color); }
.preview-add-btn { margin-top: 0.45rem; width: 100%; border: 0; border-radius: 8px; min-height: 32px; background: var(--card-button-bg-color); color: var(--card-button-text-color); font-weight: 700; }
.preview-send-btn { margin-top: 0.45rem; width: 100%; border: 0; border-radius: 8px; min-height: 32px; background: var(--card-button-bg-color); color: var(--manager-send-button-text-color); font-weight: 700; }
.preview-footer { margin-top: 0.55rem; border-top: 1px solid rgba(255,255,255,.14); background: var(--footer-bg-color); color: var(--footer-text-color); border-radius: 8px; height: 34px; display: flex; align-items: center; justify-content: space-around; font-size: 0.76rem; font-weight: 600; }
.actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.btn-primary { border: 0; border-radius: 10px; padding: 0.55rem 0.75rem; background: #2563eb; color: #fff; cursor: pointer; }
.btn-reset { border: 1px solid #f2c5cb; border-radius: 10px; padding: 0.55rem 0.75rem; background: #fff4f5; color: #a03545; cursor: pointer; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-reset:disabled { opacity: 0.6; cursor: not-allowed; }
.message { margin: 0; color: #166534; }
.empty-box { border: 1px dashed #c8d3ea; border-radius: 12px; background: #fff; padding: 1rem; color: #546480; }
@media (max-width: 980px) {
  .theme-layout { grid-template-columns: 1fr; }
}
@media (max-width: 760px) { .theme-grid { grid-template-columns: 1fr; } }
</style>
