<template>
  <div class="create-shop-container">
    <h1>Создание магазина</h1>
    
    <!-- Информация о лимитах -->
    <div v-if="limits" class="limits-info" :class="{ 'limit-warning': limits.remaining === 0 }">
      <p>
        <strong>Лимит магазинов:</strong> {{ limits.remaining }} из {{ limits.total }} доступно
      </p>
      <p v-if="limits.remaining === 0" class="text-danger">
        Вы достигли лимита магазинов для вашего тарифа
      </p>
    </div>

    <form @submit.prevent="handleSubmit" class="shop-form" v-if="limits?.remaining > 0">
      <!-- Название магазина -->
      <div class="form-group">
        <label for="name">Название магазина *</label>
        <input
          type="text"
          id="name"
          v-model="form.name"
          @blur="validateField('name')"
          :class="{ 'error': errors.name }"
          placeholder="Введите название магазина"
        >
        <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
      </div>

      <!-- Токен Telegram бота -->
      <div class="form-group">
        <label for="bot_token">Токен Telegram бота</label>
        <input
          type="password"
          id="bot_token"
          v-model="form.bot_token"
          @blur="validateField('bot_token')"
          :class="{ 'error': errors.bot_token }"
          placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz"
        >
        <span v-if="errors.bot_token" class="error-message">{{ errors.bot_token }}</span>
        <small class="help-text">
          Получите токен у <a href="https://t.me/BotFather" target="_blank">@BotFather</a>
        </small>
      </div>

      <!-- Способ доставки -->
      <div class="form-row">
        <div class="form-group">
          <label for="delivery_name">Название доставки *</label>
          <input
            type="text"
            id="delivery_name"
            v-model="form.delivery_name"
            @blur="validateField('delivery_name')"
            :class="{ 'error': errors.delivery_name }"
            placeholder="Например: Самовывоз, Курьером"
          >
          <span v-if="errors.delivery_name" class="error-message">{{ errors.delivery_name }}</span>
        </div>

        <div class="form-group">
          <label for="delivery_price">Стоимость доставки *</label>
          <input
            type="number"
            id="delivery_price"
            v-model.number="form.delivery_price"
            @blur="validateField('delivery_price')"
            :class="{ 'error': errors.delivery_price }"
            placeholder="0"
            min="0"
            step="0.01"
          >
          <span v-if="errors.delivery_price" class="error-message">{{ errors.delivery_price }}</span>
        </div>
      </div>

      <!-- ID чата для уведомлений -->
      <div class="form-group">
        <label for="notification_chat_id">ID чата для уведомлений</label>
        <input
          type="text"
          id="notification_chat_id"
          v-model="form.notification_chat_id"
          @blur="validateField('notification_chat_id')"
          :class="{ 'error': errors.notification_chat_id }"
          placeholder="Например: -1001234567890"
        >
        <span v-if="errors.notification_chat_id" class="error-message">{{ errors.notification_chat_id }}</span>
        <small class="help-text">
          ID группы или канала для уведомлений о заказах
        </small>
      </div>

      <div class="form-group">
        <label for="notification_username">Username для уведомлений (альтернатива chat_id)</label>
        <input
          type="text"
          id="notification_username"
          v-model="form.notification_username"
          @blur="validateField('notification_username')"
          :class="{ 'error': errors.notification_username }"
          placeholder="@username"
        >
        <span v-if="errors.notification_username" class="error-message">{{ errors.notification_username }}</span>
      </div>

      <div class="form-group">
        <label for="webhook_url">Webhook URL для внешней системы</label>
        <input
          type="url"
          id="webhook_url"
          v-model="form.webhook_url"
          @blur="validateField('webhook_url')"
          :class="{ 'error': errors.webhook_url }"
          placeholder="https://example.com/webhooks/order"
        >
        <span v-if="errors.webhook_url" class="error-message">{{ errors.webhook_url }}</span>
      </div>

      <!-- Кнопки -->
      <div class="form-actions">
        <button type="submit" class="btn-primary" :disabled="loading || !isFormValid">
          <span v-if="loading">Создание...</span>
          <span v-else>Создать магазин</span>
        </button>
        <button type="button" class="btn-secondary" @click="$router.push('/shops')">
          Отмена
        </button>
      </div>
    </form>

    <!-- Сообщение об успехе -->
    <div v-if="success" class="alert alert-success">
      <p>Магазин успешно создан!</p>
      <p v-if="botSetup" class="bot-setup-line" :class="botSetup.ok ? 'bot-setup-ok' : 'bot-setup-warn'">
        {{ botSetup.message }}
      </p>
      <p v-if="botSetup?.domain_hint_required" class="bot-setup-line bot-setup-warn">
        Для этого бота один раз задайте домен <strong>e-tgo.ru</strong> в @BotFather через <code>/setdomain</code>.
      </p>
      <router-link to="/shops" class="btn-primary">Перейти к магазинам</router-link>
    </div>

    <!-- Сообщение об ошибке -->
    <div v-if="error" class="alert alert-error">
      <p>{{ error }}</p>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'CreateShopView',
  setup() {
    const router = useRouter()
    const loading = ref(false)
    const success = ref(false)
    const error = ref('')
    const limits = ref(null)
    const botSetup = ref(null)

    const form = reactive({
      name: '',
      bot_token: '',
      delivery_name: '',
      delivery_price: 0,
      notification_chat_id: '',
      notification_username: '',
      webhook_url: ''
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

    // Загрузка лимитов при монтировании
    onMounted(async () => {
      await loadLimits()
    })

    const loadLimits = async () => {
      try {
        // Получаем список магазинов и информацию о лимитах
        const response = await axios.get('/api/user')
        const shopsResponse = await axios.get('/api/shops')
        const subscriptionResponse = await axios.get('/api/subscription/plans')
        
        const totalShops = shopsResponse.data.shops?.length || 0
        const subscription = subscriptionResponse.data.current_subscription
        
        if (subscription) {
          const limitsMap = {
            'starter': 1,
            'business': 5,
            'premium': 10
          }
          const total = limitsMap[subscription.plan] || 0
          limits.value = {
            total,
            remaining: Math.max(0, total - totalShops)
          }
        } else {
          limits.value = {
            total: 0,
            remaining: 0
          }
        }
      } catch (err) {
        console.error('Ошибка загрузки лимитов:', err)
      }
    }

    const validateField = (field) => {
      switch (field) {
        case 'name':
          errors.name = !form.name ? 'Название магазина обязательно' : ''
          break
        case 'bot_token':
          if (form.bot_token && !form.bot_token.match(/^\d+:[\w-]+$/)) {
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

    const handleSubmit = async () => {
      // Валидация всех полей
      Object.keys(form).forEach(field => validateField(field))
      
      if (!isFormValid.value) {
        return
      }

      loading.value = true
      error.value = ''

      try {
        const response = await axios.post('/api/shops', form)
        
        if (response.data.success) {
          success.value = true
          botSetup.value = response.data.bot_setup || null
          // Очищаем форму
          Object.assign(form, {
            name: '',
            bot_token: '',
            delivery_name: '',
            delivery_price: 0,
            notification_chat_id: '',
            notification_username: '',
            webhook_url: ''
          })
        } else {
          error.value = response.data.message || 'Ошибка при создании магазина'
        }
      } catch (err) {
        if (err.response?.data?.message) {
          error.value = err.response.data.message
        } else if (err.response?.data?.errors) {
          // Обработка ошибок валидации
          const validationErrors = err.response.data.errors
          Object.keys(validationErrors).forEach(key => {
            if (errors.hasOwnProperty(key)) {
              errors[key] = validationErrors[key][0]
            }
          })
          error.value = 'Проверьте правильность заполнения полей'
        } else {
          error.value = 'Произошла ошибка при создании магазина'
        }
        console.error('Error creating shop:', err)
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      errors,
      loading,
      success,
      error,
      botSetup,
      limits,
      validateField,
      isFormValid,
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
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.22);
  animation: page-in 480ms cubic-bezier(.2,.8,.2,1) both;
}

h1 {
  margin-bottom: 2rem;
  color: var(--color-heading);
  font-size: 2rem;
}

.limits-info {
  background: rgba(69, 123, 255, 0.16);
  padding: 1rem;
  border-radius: 10px;
  margin-bottom: 2rem;
  border-left: 4px solid #5092ff;
  color: #cbd7ff;
}

.limit-warning {
  background: rgba(255, 97, 97, 0.15);
  border-left-color: #ff6f7d;
}

.text-danger {
  color: #ff98a5;
  font-weight: 600;
  margin-top: 0.5rem;
}

.shop-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

label {
  font-weight: 600;
  color: #d6def8;
}

input {
  padding: 0.75rem;
  border: 1px solid rgba(170, 184, 255, 0.25);
  border-radius: 10px;
  font-size: 1rem;
  transition: border-color 0.3s, box-shadow 0.3s;
  background: rgba(5, 8, 16, 0.5);
  color: #eef2ff;
}

input:focus {
  outline: none;
  border-color: rgba(102, 160, 255, 0.9);
  box-shadow: 0 0 0 3px rgba(80, 137, 255, 0.2);
}

input.error {
  border-color: #ff7482;
}

.error-message {
  color: #ff94a3;
  font-size: 0.9rem;
}

.help-text {
  color: #a5afcf;
  font-size: 0.9rem;
}

.help-text a {
  color: #84bbff;
  text-decoration: none;
}

.help-text a:hover {
  text-decoration: underline;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

.btn-primary, .btn-secondary {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.25s ease, background 0.3s;
}

.btn-primary:hover:not(:disabled), .btn-secondary:hover {
  transform: translateY(-1px);
}

.btn-primary {
  background: linear-gradient(120deg, #4f63ff, #33c5ff);
  color: #f4f7ff;
  flex: 2;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(120deg, #6376ff, #4ecfff);
}

.btn-primary:disabled {
  background: #4d5572;
  cursor: not-allowed;
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.06);
  color: #dde4ff;
  flex: 1;
  border: 1px solid rgba(186, 198, 255, 0.24);
}

.btn-secondary:hover {
  background: rgba(127, 149, 255, 0.16);
}

.alert {
  padding: 1rem;
  border-radius: 4px;
  margin-top: 1rem;
}

.alert-success {
  background: rgba(78, 214, 153, 0.16);
  color: #bbf8de;
  border: 1px solid rgba(78, 214, 153, 0.4);
}

.alert-error {
  background: rgba(255, 103, 125, 0.14);
  color: #ffc4cf;
  border: 1px solid rgba(255, 103, 125, 0.38);
}

.alert .btn-primary {
  display: inline-block;
  margin-top: 1rem;
  text-decoration: none;
}

.bot-setup-line {
  margin-top: 0.55rem;
}

.bot-setup-ok {
  color: #c9ffe2;
}

.bot-setup-warn {
  color: #ffd4a0;
}

@keyframes page-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 600px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .create-shop-container {
    margin: 1rem;
    padding: 1rem;
  }
}
</style>
