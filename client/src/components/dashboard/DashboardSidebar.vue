<template>
  <aside class="sidebar" :class="{ collapsed }">
    <button class="collapse-btn" type="button" @click="$emit('toggle-collapse')">
      {{ collapsed ? '›' : '‹' }}
    </button>

    <nav class="menu">
      <section
        v-for="group in groups"
        :key="group.key"
        class="menu-group"
      >
        <p v-if="!collapsed" class="group-title">{{ group.title }}</p>

        <router-link
          v-for="item in group.items"
          :key="item.key"
          :to="item.to"
          class="menu-item"
          :title="item.label"
        >
          <span class="menu-icon">{{ item.icon }}</span>
          <span v-if="!collapsed" class="menu-label">{{ item.label }}</span>
        </router-link>
      </section>
    </nav>

    <section v-if="!collapsed && selectedShopId" class="theme-box">
      <div class="theme-box-head">
        <h4>Тема mini-app</h4>
        <button type="button" class="reset-btn" @click="resetTheme">Сброс</button>
      </div>
      <p class="theme-hint">Фон, текст и dots для выбранного магазина.</p>
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
          <span>Dots</span>
          <input v-model="theme.dots_color" type="color">
        </label>
      </div>
      <button class="save-btn" type="button" :disabled="savingTheme" @click="saveTheme">
        {{ savingTheme ? 'Сохраняю...' : 'Сохранить тему' }}
      </button>
      <p v-if="themeMessage" class="theme-message">{{ themeMessage }}</p>
    </section>
  </aside>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import axios from 'axios'

const DEFAULT_THEME = {
  background_start: '#070B18',
  background_end: '#0D1326',
  text_color: '#EFF6FF',
  dots_color: '#38E8FF',
}

const props = defineProps({
  groups: {
    type: Array,
    default: () => [],
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
  selectedShopId: {
    type: [String, Number],
    default: '',
  },
})

defineEmits(['toggle-collapse'])

const theme = reactive({ ...DEFAULT_THEME })
const savingTheme = ref(false)
const themeMessage = ref('')

const fillTheme = (themeSettings) => {
  Object.assign(theme, DEFAULT_THEME, themeSettings || {})
}

const loadTheme = async (shopId) => {
  if (!shopId) return
  themeMessage.value = ''
  try {
    const response = await axios.get(`/api/shops/${shopId}`)
    fillTheme(response.data?.shop?.theme_settings)
  } catch (error) {
    console.error('Failed to load shop theme in sidebar:', error)
  }
}

const saveTheme = async () => {
  if (!props.selectedShopId) return
  savingTheme.value = true
  themeMessage.value = ''
  try {
    await axios.patch(`/api/shops/${props.selectedShopId}`, {
      theme_settings: {
        background_start: theme.background_start,
        background_end: theme.background_end,
        text_color: theme.text_color,
        dots_color: theme.dots_color,
      },
    })
    themeMessage.value = 'Тема сохранена'
  } catch (error) {
    console.error('Failed to save shop theme in sidebar:', error)
    themeMessage.value = 'Ошибка сохранения темы'
  } finally {
    savingTheme.value = false
  }
}

const resetTheme = () => {
  fillTheme(DEFAULT_THEME)
}

watch(
  () => props.selectedShopId,
  (shopId) => {
    if (shopId) {
      loadTheme(shopId)
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.sidebar {
  position: sticky;
  top: 0.9rem;
  height: calc(100vh - 1.8rem);
  width: 240px;
  padding: 0.8rem;
  border-radius: 16px;
  border: 1px solid rgba(151, 166, 207, 0.25);
  background: #f4f6fc;
  transition: width 180ms ease;
  overflow-y: auto;
}

.sidebar.collapsed {
  width: 86px;
}

.collapse-btn {
  width: 30px;
  height: 30px;
  margin: 0 auto 0.8rem;
  display: grid;
  place-items: center;
  border: 1px solid #d2daec;
  border-radius: 999px;
  background: #fff;
  color: #334155;
  cursor: pointer;
}

.menu {
  display: grid;
  gap: 0.7rem;
}

.theme-box {
  margin-top: 0.9rem;
  border: 1px solid #d1ddf3;
  border-radius: 12px;
  background: #fff;
  padding: 0.62rem;
}

.theme-box-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.4rem;
}

.theme-box-head h4 {
  margin: 0;
  color: #1f3e71;
  font-size: 0.92rem;
}

.reset-btn {
  border: 1px solid #c9d8f2;
  border-radius: 8px;
  background: #f2f7ff;
  color: #2f4f83;
  padding: 0.24rem 0.5rem;
  cursor: pointer;
  font-size: 0.8rem;
}

.theme-hint {
  margin: 0.45rem 0;
  color: #5c7193;
  font-size: 0.78rem;
}

.theme-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.45rem;
}

.theme-grid label {
  display: grid;
  gap: 0.2rem;
}

.theme-grid span {
  color: #33517f;
  font-size: 0.76rem;
}

.theme-grid input[type='color'] {
  width: 100%;
  height: 30px;
  border: 1px solid #c8d7f2;
  border-radius: 8px;
  padding: 0.1rem;
  background: #fff;
}

.save-btn {
  margin-top: 0.55rem;
  width: 100%;
  border: 0;
  border-radius: 9px;
  background: #2563eb;
  color: #fff;
  padding: 0.48rem 0.6rem;
  cursor: pointer;
  font-size: 0.84rem;
}

.save-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.theme-message {
  margin: 0.4rem 0 0;
  color: #356a2f;
  font-size: 0.78rem;
}

.menu-group {
  display: grid;
  gap: 0.32rem;
}

.group-title {
  margin: 0 0 0.18rem;
  padding: 0 0.45rem;
  color: #6b7c97;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.68rem;
  font-weight: 700;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.7rem 0.65rem;
  color: #344256;
  border-radius: 10px;
  text-decoration: none;
}

.menu-item:hover {
  background: #e6ecf9;
}

.menu-item.router-link-active {
  background: #dbe8ff;
  color: #132952;
  font-weight: 600;
}

.menu-icon {
  width: 26px;
  height: 26px;
  display: grid;
  place-items: center;
  font-size: 1.05rem;
}

.menu-label {
  font-size: 1rem;
}

@media (max-width: 980px) {
  .sidebar {
    position: static;
    width: 100%;
    height: auto;
  }

  .sidebar.collapsed {
    width: 100%;
  }

  .collapse-btn {
    display: none;
  }

  .menu {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.45rem;
  }

  .theme-box {
    display: none;
  }

  .menu-group {
    display: contents;
  }

  .group-title {
    display: none;
  }

  .menu-label {
    font-size: 1rem;
  }
}
</style>
