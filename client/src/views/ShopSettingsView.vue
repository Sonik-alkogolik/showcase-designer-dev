<template>
  <div class="create-shop-container">
    <h1>Настройки магазина</h1>

    <form @submit.prevent="handleSubmit" class="shop-form">
      <div class="form-group">
        <label for="name">Название магазина *</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          :class="{ error: errors.name }"
          @blur="validateField('name')"
        >
        <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
      </div>

      <div class="form-group">
        <label for="bot_token">Токен Telegram бота</label>
        <div class="token-input-wrap">
          <input
            id="bot_token"
            v-model="form.bot_token"
            :type="showBotToken ? 'text' : 'password'"
            :class="{ error: errors.bot_token }"
            @blur="validateField('bot_token')"
            placeholder="Оставьте пустым, чтобы не менять"
            autocomplete="new-password"
          >
          <button
            type="button"
            class="btn-toggle-token"
            @click="toggleBotTokenVisibility"
          >
            {{ showBotToken ? 'Скрыть' : 'Показать' }}
          </button>
        </div>
        <span v-if="errors.bot_token" class="error-message">{{ errors.bot_token }}</span>
        <span v-else-if="hasBotToken" class="token-state token-state-ok">Токен сохранен в магазине</span>
        <span v-else class="token-state">Токен пока не задан</span>
      </div>

      <div class="bot-setup-card" v-if="hasBotToken">
        <div class="bot-setup-head">
          <h3>Подключение витринного бота</h3>
          <span :class="botSetup?.ok ? 'setup-ok' : 'setup-pending'">
            {{ botSetup?.ok ? 'Бот готов' : 'Требуется настройка' }}
          </span>
        </div>
        <p class="bot-setup-message">{{ botSetup?.message || 'Проверяем состояние бота...' }}</p>
        <p v-if="botSetup?.bot_username" class="bot-setup-meta">
          Бот: {{ botSetup.bot_username }}
        </p>
        <p v-if="botSetup?.webapp_url" class="bot-setup-meta">
          URL витрины: <code>{{ botSetup.webapp_url }}</code>
        </p>
        <div v-if="manualSetupSteps.length" class="manual-setup">
          <p class="manual-setup-title">Быстрый запуск (как у TGShop):</p>
          <ol class="manual-setup-list">
            <li v-for="(step, idx) in manualSetupSteps" :key="`setup-step-${idx}`">{{ step }}</li>
          </ol>
          <div v-if="botSetup?.webapp_url" class="manual-setup-actions">
            <button type="button" class="btn-secondary" @click="copyWebAppUrl">
              Копировать URL mini-app
            </button>
          </div>
        </div>
        <div class="bot-setup-actions">
          <button type="button" class="btn-primary" :disabled="botSetupLoading" @click="connectBot">
            {{ botSetupLoading ? 'Подключаю...' : 'Подключить бота' }}
          </button>
          <button type="button" class="btn-secondary" :disabled="botSetupLoading" @click="loadBotStatus">
            Проверить
          </button>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="delivery_name">Название доставки *</label>
          <input
            id="delivery_name"
            v-model="form.delivery_name"
            type="text"
            :class="{ error: errors.delivery_name }"
            @blur="validateField('delivery_name')"
          >
          <span v-if="errors.delivery_name" class="error-message">{{ errors.delivery_name }}</span>
        </div>

        <div class="form-group">
          <label for="delivery_price">Стоимость доставки *</label>
          <input
            id="delivery_price"
            v-model.number="form.delivery_price"
            type="number"
            min="0"
            step="0.01"
            :class="{ error: errors.delivery_price }"
            @blur="validateField('delivery_price')"
          >
          <span v-if="errors.delivery_price" class="error-message">{{ errors.delivery_price }}</span>
        </div>
      </div>

      <div class="form-group">
        <label for="notification_chat_id">ID чата для уведомлений</label>
        <input
          id="notification_chat_id"
          v-model="form.notification_chat_id"
          type="text"
          :class="{ error: errors.notification_chat_id }"
          @blur="validateField('notification_chat_id')"
        >
        <span v-if="errors.notification_chat_id" class="error-message">{{ errors.notification_chat_id }}</span>
      </div>

      <div class="form-group">
        <label for="notification_username">Telegram менеджера (контакт для клиента)</label>
        <input
          id="notification_username"
          v-model="form.notification_username"
          type="text"
          placeholder="@manager_username"
          :class="{ error: errors.notification_username }"
          @blur="validateField('notification_username')"
        >
        <span v-if="errors.notification_username" class="error-message">{{ errors.notification_username }}</span>
      </div>

      <div class="form-group">
        <label for="webhook_url">Webhook URL</label>
        <input
          id="webhook_url"
          v-model="form.webhook_url"
          type="url"
          placeholder="https://example.com/webhooks/order"
          :class="{ error: errors.webhook_url }"
          @blur="validateField('webhook_url')"
        >
        <span v-if="errors.webhook_url" class="error-message">{{ errors.webhook_url }}</span>
      </div>

      <div class="theme-card">
        <div class="theme-card-head">
          <h3>Тема mini-app</h3>
          <button type="button" class="btn-secondary" @click="resetTheme">Сбросить по умолчанию</button>
        </div>
        <p class="theme-note">Настройте фон главного контейнера, цвет текста и dots слайдера.</p>
        <div class="theme-grid">
          <label class="color-field">
            <span>Фон (начало)</span>
            <input v-model="form.theme_settings.background_start" type="color">
          </label>
          <label class="color-field">
            <span>Фон (конец)</span>
            <input v-model="form.theme_settings.background_end" type="color">
          </label>
          <label class="color-field">
            <span>Текст</span>
            <input v-model="form.theme_settings.text_color" type="color">
          </label>
          <label class="color-field">
            <span>Dots слайдера</span>
            <input v-model="form.theme_settings.dots_color" type="color">
          </label>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-primary" :disabled="loading || !isFormValid">
          {{ loading ? 'Сохранение...' : 'Сохранить' }}
        </button>
        <button type="button" class="btn-secondary" @click="$router.push('/shops')">Назад</button>
      </div>
    </form>

    <div v-if="success" class="alert alert-success">
      <p>Настройки магазина сохранены</p>
    </div>
    <div v-if="error" class="alert alert-error">
      <p>{{ error }}</p>
    </div>
  </div>
</template>

<script>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const BOT_TOKEN_MASK = '********'
const DEFAULT_THEME = {
  background_start: '#070B18',
  background_end: '#0D1326',
  text_color: '#EFF6FF',
  dots_color: '#38E8FF',
}

export default {
  name: 'ShopSettingsView',
  setup() {
    const route = useRoute()
    const shopId = route.params.shopId
    const loading = ref(false)
    const success = ref(false)
    const error = ref('')
    const hasBotToken = ref(false)
    const showBotToken = ref(false)
    const savedBotToken = ref('')
    const botSetupLoading = ref(false)
    const botSetup = ref(null)

    const form = reactive({
      name: '',
      bot_token: '',
      delivery_name: '',
      delivery_price: 0,
      notification_chat_id: '',
      notification_username: '',
      webhook_url: '',
      theme_settings: { ...DEFAULT_THEME },
    })

    const errors = reactive({
      name: '',
      bot_token: '',
      delivery_name: '',
      delivery_price: '',
      notification_chat_id: '',
      notification_username: '',
      webhook_url: ''
    })

    const validateField = (field) => {
      switch (field) {
        case 'name':
          errors.name = !form.name ? 'Название магазина обязательно' : ''
          break
        case 'bot_token':
          if (form.bot_token && form.bot_token !== BOT_TOKEN_MASK && !form.bot_token.match(/^\d+:[\w-]+$/)) {
            errors.bot_token = 'Неверный формат токена'
          } else {
            errors.bot_token = ''
          }
          break
        case 'delivery_name':
          errors.delivery_name = !form.delivery_name ? 'Название доставки обязательно' : ''
          break
        case 'delivery_price':
          if (form.delivery_price === '' || form.delivery_price === null) {
            errors.delivery_price = 'Стоимость доставки обязательна'
          } else if (form.delivery_price < 0) {
            errors.delivery_price = 'Стоимость не может быть отрицательной'
          } else {
            errors.delivery_price = ''
          }
          break
        case 'notification_chat_id':
          if (form.notification_chat_id && !form.notification_chat_id.match(/^-?\d+$/)) {
            errors.notification_chat_id = 'ID чата должен быть числом'
          } else {
            errors.notification_chat_id = ''
          }
          break
        case 'notification_username':
          if (form.notification_username && !form.notification_username.match(/^@?[A-Za-z0-9_]{5,}$/)) {
            errors.notification_username = 'Username должен быть в формате @username'
          } else {
            errors.notification_username = ''
          }
          break
        case 'webhook_url':
          if (form.webhook_url) {
            try {
              const parsed = new URL(form.webhook_url)
              errors.webhook_url = (parsed.protocol === 'http:' || parsed.protocol === 'https:')
                ? ''
                : 'Webhook URL должен начинаться с http:// или https://'
            } catch (e) {
              errors.webhook_url = 'Некорректный URL'
            }
          } else {
            errors.webhook_url = ''
          }
          break
      }
    }

    const isFormValid = computed(() => {
      return form.name &&
        form.delivery_name &&
        form.delivery_price !== '' &&
        form.delivery_price >= 0 &&
        !errors.name &&
        !errors.bot_token &&
        !errors.delivery_name &&
        !errors.delivery_price &&
        !errors.notification_chat_id &&
        !errors.notification_username &&
        !errors.webhook_url
    })

    const manualSetupSteps = computed(() => {
      const steps = botSetup.value?.manual_setup?.steps
      if (!Array.isArray(steps)) {
        return []
      }
      return steps
    })

    const loadShop = async () => {
      loading.value = true
      error.value = ''
      try {
        const { data } = await axios.get(`/api/shops/${shopId}`)
        const shop = data?.shop || {}
        form.name = shop.name || ''
        form.delivery_name = shop.delivery_name || ''
        form.delivery_price = Number(shop.delivery_price || 0)
        form.notification_chat_id = shop.notification_chat_id || ''
        form.notification_username = shop.notification_username || ''
        form.webhook_url = shop.webhook_url || ''
        form.theme_settings = {
          ...DEFAULT_THEME,
          ...(shop.theme_settings || {}),
        }
        hasBotToken.value = Boolean(shop.has_bot_token)
        savedBotToken.value = ''
        form.bot_token = hasBotToken.value ? BOT_TOKEN_MASK : ''
        if (hasBotToken.value) {
          await loadBotStatus()
        } else {
          botSetup.value = null
        }
      } catch (err) {
        error.value = err?.response?.data?.message || 'Не удалось загрузить настройки магазина'
      } finally {
        loading.value = false
      }
    }

    const toggleBotTokenVisibility = async () => {
      if (showBotToken.value) {
        showBotToken.value = false
        form.bot_token = hasBotToken.value ? BOT_TOKEN_MASK : ''
        return
      }

      if (!hasBotToken.value) {
        showBotToken.value = true
        return
      }

      try {
        if (!savedBotToken.value) {
          const { data } = await axios.get(`/api/shops/${shopId}/bot-token`)
          savedBotToken.value = data?.bot_token || ''
        }
        showBotToken.value = true
        form.bot_token = savedBotToken.value
      } catch (err) {
        error.value = err?.response?.data?.message || 'Не удалось получить токен бота'
      }
    }

    const loadBotStatus = async () => {
      if (!hasBotToken.value) return
      botSetupLoading.value = true
      try {
        const { data } = await axios.get(`/api/shops/${shopId}/bot-status`)
        botSetup.value = data?.bot_setup || null
      } catch (err) {
        botSetup.value = {
          ok: false,
          message: err?.response?.data?.message || 'Не удалось получить статус бота',
        }
      } finally {
        botSetupLoading.value = false
      }
    }

    const connectBot = async () => {
      if (!hasBotToken.value) return
      botSetupLoading.value = true
      try {
        const { data } = await axios.post(`/api/shops/${shopId}/bot-connect`)
        botSetup.value = data?.bot_setup || null
      } catch (err) {
        botSetup.value = err?.response?.data?.bot_setup || {
          ok: false,
          message: err?.response?.data?.message || 'Не удалось подключить бота',
        }
      } finally {
        botSetupLoading.value = false
      }
    }

    const copyWebAppUrl = async () => {
      const url = botSetup.value?.webapp_url
      if (!url) return

      try {
        await navigator.clipboard.writeText(url)
      } catch (err) {
        error.value = 'Не удалось скопировать URL mini-app'
      }
    }

    const handleSubmit = async () => {
      Object.keys(form).forEach(validateField)
      if (!isFormValid.value) {
        return
      }

      loading.value = true
      success.value = false
      error.value = ''

      try {
        const payload = { ...form }
        payload.theme_settings = { ...form.theme_settings }
        if (!form.bot_token || form.bot_token === BOT_TOKEN_MASK) {
          delete payload.bot_token
        }
        const { data } = await axios.patch(`/api/shops/${shopId}`, payload)
        if (data?.success) {
          success.value = true
          hasBotToken.value = Boolean(data?.shop?.has_bot_token)
          botSetup.value = data?.bot_setup || botSetup.value
          savedBotToken.value = form.bot_token && form.bot_token !== BOT_TOKEN_MASK ? form.bot_token : savedBotToken.value
          showBotToken.value = false
          form.bot_token = hasBotToken.value ? BOT_TOKEN_MASK : ''
        }
      } catch (err) {
        if (err?.response?.data?.errors) {
          const serverErrors = err.response.data.errors
          Object.keys(serverErrors).forEach((key) => {
            if (Object.prototype.hasOwnProperty.call(errors, key)) {
              errors[key] = serverErrors[key][0]
            }
          })
          error.value = 'Проверьте правильность заполнения полей'
        } else {
          error.value = err?.response?.data?.message || 'Не удалось сохранить настройки'
        }
      } finally {
        loading.value = false
      }
    }

    const resetTheme = () => {
      form.theme_settings = { ...DEFAULT_THEME }
    }

    onMounted(loadShop)

    return {
      form,
      errors,
      loading,
      success,
      error,
      hasBotToken,
      showBotToken,
      toggleBotTokenVisibility,
      botSetupLoading,
      botSetup,
      connectBot,
      loadBotStatus,
      manualSetupSteps,
      copyWebAppUrl,
      resetTheme,
      isFormValid,
      validateField,
      handleSubmit
    }
  }
}
</script>

<style scoped>
.create-shop-container {
  max-width: 800px;
  margin: 2rem auto;
  padding: 2rem;
  background: linear-gradient(170deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.02));
  border-radius: 16px;
  border: 1px solid rgba(173, 186, 255, 0.2);
}

h1 {
  margin-bottom: 1.5rem;
  color: var(--color-heading);
}

.shop-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

input {
  padding: 0.75rem 0.9rem;
  border-radius: 10px;
  border: 1px solid rgba(173, 186, 255, 0.3);
  background: rgba(10, 16, 35, 0.35);
  color: #fff;
}

.token-input-wrap {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0.6rem;
  align-items: center;
}

input.error {
  border-color: rgba(255, 105, 126, 0.7);
}

.error-message {
  color: #ff9aa7;
  font-size: 0.86rem;
}

.token-state {
  color: #c3d2ff;
  font-size: 0.86rem;
}

.token-state-ok {
  color: #98f5c7;
}

.bot-setup-card {
  border: 1px solid rgba(173, 186, 255, 0.28);
  border-radius: 12px;
  padding: 0.9rem;
  background: rgba(11, 18, 38, 0.38);
}

.bot-setup-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.6rem;
}

.bot-setup-head h3 {
  margin: 0;
  color: #deebff;
  font-size: 1rem;
}

.setup-ok {
  color: #9ef7cb;
  font-weight: 600;
}

.setup-pending {
  color: #ffd49e;
  font-weight: 600;
}

.bot-setup-message {
  margin: 0.45rem 0 0;
  color: #c3d2ff;
}

.bot-setup-meta {
  margin: 0.35rem 0 0;
  color: #a8bbeb;
  font-size: 0.92rem;
}

.domain-hint {
  margin: 0.5rem 0 0;
  color: #ffdca8;
  font-size: 0.9rem;
}

.manual-setup {
  margin-top: 0.65rem;
  border-top: 1px dashed rgba(173, 186, 255, 0.28);
  padding-top: 0.55rem;
}

.manual-setup-title {
  margin: 0;
  color: #deebff;
  font-weight: 600;
}

.manual-setup-list {
  margin: 0.4rem 0 0;
  padding-left: 1.1rem;
  color: #c3d2ff;
  font-size: 0.9rem;
  line-height: 1.35;
}

.manual-setup-actions {
  margin-top: 0.55rem;
}

.bot-setup-actions {
  margin-top: 0.65rem;
  display: flex;
  gap: 0.55rem;
}

.theme-card {
  margin-top: 0.4rem;
  border: 1px solid rgba(173, 186, 255, 0.28);
  border-radius: 12px;
  padding: 0.9rem;
  background: rgba(11, 18, 38, 0.38);
}

.theme-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.6rem;
}

.theme-card-head h3 {
  margin: 0;
  color: #deebff;
  font-size: 1rem;
}

.theme-note {
  margin: 0.45rem 0 0.7rem;
  color: #c3d2ff;
  font-size: 0.9rem;
}

.theme-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.color-field {
  display: grid;
  gap: 0.35rem;
  color: #deebff;
  font-size: 0.9rem;
}

.color-field input[type="color"] {
  width: 100%;
  height: 40px;
  border: 1px solid rgba(173, 186, 255, 0.35);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.06);
  padding: 0.2rem;
}

.form-actions {
  display: flex;
  gap: 0.8rem;
}

.btn-primary,
.btn-secondary {
  border: none;
  border-radius: 8px;
  padding: 0.65rem 1rem;
  cursor: pointer;
}

.btn-toggle-token {
  border: 1px solid rgba(173, 186, 255, 0.35);
  border-radius: 8px;
  background: rgba(147, 169, 222, 0.15);
  color: #deebff;
  padding: 0.55rem 0.8rem;
  cursor: pointer;
  white-space: nowrap;
}

.btn-primary {
  background: linear-gradient(120deg, #4f63ff, #33c5ff);
  color: #f5f8ff;
}

.btn-secondary {
  background: rgba(147, 169, 222, 0.2);
  color: #deebff;
}

.alert {
  margin-top: 1rem;
  border-radius: 8px;
  padding: 0.75rem 0.9rem;
}

.alert-success {
  background: rgba(73, 184, 126, 0.18);
  color: #c9ffe2;
}

.alert-error {
  background: rgba(255, 115, 131, 0.12);
  color: #ffd7dc;
}

@media (max-width: 720px) {
  .form-row {
    grid-template-columns: 1fr;
  }

  .theme-grid {
    grid-template-columns: 1fr;
  }
}
</style>
