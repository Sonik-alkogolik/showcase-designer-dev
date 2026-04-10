<template>
  <div class="profile-container">
    <h1>Личный кабинет</h1>
    
    <!-- Данные пользователя -->
    <div class="user-info">
      <h2>Мой профиль</h2>
      <div class="info-row">
        <span class="label">Имя:</span>
        <span class="value">{{ user?.name || 'Загрузка...' }}</span>
      </div>
      <div class="info-row">
        <span class="label">Email:</span>
        <span class="value">{{ user?.email || 'Загрузка...' }}</span>
      </div>
      <div class="danger-zone">
        <button
          class="btn-danger-outline"
          :disabled="deletingAccount"
          @click="deleteMyAccount"
        >
          {{ deletingAccount ? 'Удаляю аккаунт...' : 'Удалить аккаунт' }}
        </button>
      </div>
    </div>

        <!-- Информация о подписке -->
    <div class="subscription-section" v-if="user">
      <h2>Моя подписка</h2>
      <div v-if="user.subscription" class="subscription-info">
        <div class="info-row">
          <span class="label">Тариф:</span>
          <span class="value">{{ user.subscription.plan_name }}</span>
        </div>
        <div class="info-row">
          <span class="label">Статус:</span>
          <span class="value" :class="{'status-active': user.subscription.status === 'active'}">
            {{ user.subscription.status === 'active' ? 'Активна' : 'Неактивна' }}
          </span>
        </div>
        <div class="info-row" v-if="user.subscription.expires_at">
          <span class="label">Действует до:</span>
          <span class="value">{{ new Date(user.subscription.expires_at).toLocaleDateString() }}</span>
        </div>
      </div>
      <div v-else class="no-subscription">
        <p>У вас пока нет активной подписки</p>
        <router-link to="/plans" class="btn-primary">Выбрать тариф</router-link>
      </div>
    </div>
    
    <!-- Привязка Telegram -->
    <div class="telegram-section">
      <h2>Привязка Telegram</h2>
      
      <div v-if="!user?.telegram_linked" class="telegram-not-linked">
        <p>Подключите ваш аккаунт Telegram для получения уведомлений</p>
        
        <div class="telegram-actions">
          <button 
            @click="generateLinkToken" 
            :disabled="generatingToken"
            class="btn-primary"
          >
            {{ generatingToken ? 'Генерация...' : 'Подключить Telegram' }}
          </button>
          <button
            @click="checkTelegramLink()"
            :disabled="checkingLink"
            class="btn-secondary-inline"
          >
            {{ checkingLink ? 'Проверяю...' : 'Проверить привязку' }}
          </button>
          <button
            v-if="botLink"
            @click="copyBotLink"
            class="btn-ghost-inline"
            type="button"
          >
            Скопировать ссылку
          </button>
        </div>
        <p v-if="autoCheckActive" class="auto-check-note">
          Проверяем привязку автоматически каждые 2-3 секунды...
        </p>
        
        <div v-if="botLink" class="bot-link-section">
          <div class="alert alert-info">
            <p><strong>Инструкция:</strong></p>
            <ol>
              <li>Перейдите в Telegram по ссылке ниже</li>
              <li>Нажмите "Запустить" в боте или отправьте команду ниже вручную</li>
              <li>Вернитесь сюда и нажмите "Проверить привязку" (или подождите авто-проверку)</li>
            </ol>
          </div>

          <div v-if="startCommand" class="start-command-section">
            <p class="start-command-title">Если бот не подставил токен автоматически, отправьте:</p>
            <code class="start-command-code">{{ startCommand }}</code>
            <button class="btn-ghost-inline" type="button" @click="copyStartCommand">
              Скопировать команду
            </button>
          </div>
          
          <a :href="botLink" target="_blank" class="bot-link">
            <svg class="telegram-icon" viewBox="0 0 24 24">
              <path fill="currentColor" d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
            </svg>
            Открыть @constructor_app_bot
          </a>
          
          <p class="token-expiry">
            ⏰ Ссылка действительна: <strong>{{ tokenExpiryMinutes }} мин</strong>
          </p>
        </div>
      </div>
      
      <div v-else class="telegram-linked">
        <div v-if="resolvedTelegramAvatar" class="telegram-avatar-wrap">
          <img :src="resolvedTelegramAvatar" alt="Telegram avatar" class="telegram-avatar">
        </div>

        <div class="linked-status">
          <svg class="check-icon" viewBox="0 0 24 24">
            <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
          </svg>
          <span>Telegram аккаунт привязан</span>
        </div>
        
        <p class="telegram-username">
          @{{ user.telegram_username }}
        </p>

        <div v-if="user.telegram_id" class="telegram-chat-id">
          <span>chat_id: <code>{{ user.telegram_id }}</code></span>
          <button class="btn-copy" @click="copyTelegramId">Копировать chat_id</button>
        </div>
        
        <button 
          @click="unlinkTelegram" 
          :disabled="unlinking"
          class="btn-danger"
        >
          {{ unlinking ? 'Отвязываю...' : 'Отвязать Telegram' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { user, loadProfile, generateTelegramLinkToken, unlinkTelegram: unlinkApi, deleteAccount } = useAuth()
const generatingToken = ref(false)
const checkingLink = ref(false)
const unlinking = ref(false)
const deletingAccount = ref(false)
const botLink = ref(null)
const tokenExpiry = ref(0)
const autoCheckActive = ref(false)
let autoCheckTimer = null

const copyTelegramId = async () => {
  const chatId = user.value?.telegram_id
  if (!chatId) {
    alert('chat_id не найден')
    return
  }

  try {
    await navigator.clipboard.writeText(String(chatId))
    alert('chat_id скопирован')
  } catch (error) {
    console.error('Ошибка копирования chat_id:', error)
    alert(`Скопируй вручную: ${chatId}`)
  }
}

// Вычисляем оставшееся время
const tokenExpiryMinutes = computed(() => {
  if (!tokenExpiry.value) return 0
  const remaining = Math.ceil((tokenExpiry.value - Date.now()) / 60000)
  return Math.max(0, remaining)
})

const resolvedTelegramAvatar = computed(() => {
  return user.value?.telegram_avatar_url || user.value?.avatar_url || ''
})

const startCommand = computed(() => {
  if (!botLink.value) return ''
  try {
    const link = new URL(botLink.value)
    const token = link.searchParams.get('start')
    if (!token) return ''
    return `/start ${token}`
  } catch (error) {
    return ''
  }
})

// Загрузка данных профиля
const loadUserData = async () => {
  await loadProfile()
}

const stopAutoCheck = () => {
  if (autoCheckTimer) {
    clearInterval(autoCheckTimer)
    autoCheckTimer = null
  }
  autoCheckActive.value = false
}

const checkTelegramLink = async (options = {}) => {
  const { silent = false } = options
  checkingLink.value = true

  try {
    await loadProfile()
    if (user.value?.telegram_linked) {
      stopAutoCheck()
      if (!silent) {
        alert('Telegram успешно привязан')
      }
      return true
    }
    if (!silent) {
      alert('Telegram пока не привязан. Нажмите "Запустить" в боте и попробуйте снова.')
    }
    return false
  } catch (error) {
    console.error('Ошибка проверки привязки Telegram:', error)
    if (!silent) {
      alert('Не удалось проверить привязку. Попробуйте снова.')
    }
    return false
  } finally {
    checkingLink.value = false
  }
}

const startAutoCheck = () => {
  stopAutoCheck()
  autoCheckActive.value = true
  autoCheckTimer = setInterval(async () => {
    const linked = await checkTelegramLink({ silent: true })
    if (linked || tokenExpiryMinutes.value <= 0) {
      stopAutoCheck()
    }
  }, 2500)
}

const copyBotLink = async () => {
  if (!botLink.value) return
  try {
    await navigator.clipboard.writeText(botLink.value)
    alert('Ссылка скопирована')
  } catch (error) {
    console.error('Ошибка копирования ссылки:', error)
    alert(`Скопируйте ссылку вручную:\n${botLink.value}`)
  }
}

const copyStartCommand = async () => {
  if (!startCommand.value) return
  try {
    await navigator.clipboard.writeText(startCommand.value)
    alert('Команда скопирована')
  } catch (error) {
    console.error('Ошибка копирования команды:', error)
    alert(`Скопируйте вручную:\n${startCommand.value}`)
  }
}

// Генерация токена для привязки
const generateLinkToken = async () => {
  generatingToken.value = true
  
  try {
    const result = await generateTelegramLinkToken()
    
    if (result.success) {
      botLink.value = result.data.bot_link
      tokenExpiry.value = Date.now() + (result.data.expires_in * 1000)
      
      // Автоматически открываем ссылку в новом окне
      window.open(result.data.bot_link, '_blank')
      startAutoCheck()
    } else {
      alert('Не удалось сгенерировать ссылку. Попробуйте позже.')
    }
  } catch (error) {
    console.error('Ошибка генерации токена:', error)
    alert('Не удалось сгенерировать ссылку. Попробуйте позже.')
  } finally {
    generatingToken.value = false
  }
}

// Отвязка Telegram
const unlinkTelegram = async () => {
  if (!confirm('Вы уверены, что хотите отвязать Telegram аккаунт?')) {
    return
  }
  
  unlinking.value = true
  
  try {
    const result = await unlinkApi()
    
    if (result.success) {
      // Очищаем ссылку
      botLink.value = null
      tokenExpiry.value = 0
      stopAutoCheck()
      
      alert('Telegram аккаунт успешно отвязан')
    } else {
      alert('Не удалось отвязать Telegram аккаунт')
    }
  } catch (error) {
    console.error('Ошибка отвязки:', error)
    alert('Не удалось отвязать Telegram аккаунт')
  } finally {
    unlinking.value = false
  }
}

const deleteMyAccount = async () => {
  if (!confirm('Удалить аккаунт? Это действие нельзя отменить.')) {
    return
  }

  deletingAccount.value = true
  try {
    const result = await deleteAccount()
    if (result.success) {
      alert('Аккаунт удален')
      router.push('/register')
      return
    }
    alert(result.error || 'Не удалось удалить аккаунт')
  } catch (error) {
    console.error('Ошибка удаления аккаунта:', error)
    alert('Не удалось удалить аккаунт')
  } finally {
    deletingAccount.value = false
  }
}

onMounted(() => {
  loadUserData()
})

onBeforeUnmount(() => {
  stopAutoCheck()
})
</script>

<style scoped>
.profile-container {
  max-width: 800px;
  margin: 2rem auto;
  padding: 2rem;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

h1 {
  margin-bottom: 2rem;
  color: #333;
  font-size: 2rem;
}

.user-info, .telegram-section {
  margin: 2rem 0;
  padding: 1.5rem;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  background: #f9f9f9;
}

.user-info h2, .telegram-section h2 {
  margin-top: 0;
  margin-bottom: 1rem;
  color: #333;
  font-size: 1.5rem;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e0e0e0;
}

.info-row:last-child {
  border-bottom: none;
}

.label {
  font-weight: 600;
  color: #555;
}

.value {
  color: #333;
  font-weight: 500;
}

.telegram-not-linked {
  text-align: center;
}

.telegram-not-linked p {
  margin-bottom: 1.5rem;
  color: #666;
  font-size: 1.1rem;
}

.telegram-actions {
  display: flex;
  gap: 0.7rem;
  justify-content: center;
  flex-wrap: wrap;
}

.auto-check-note {
  margin-top: 0.75rem;
  color: #475569;
  font-size: 0.92rem;
}

.btn-primary {
  background: #0088cc;
  color: white;
  border: none;
  padding: 0.85rem 2rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 600;
  transition: background 0.3s;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-primary:disabled {
  background: #ccc;
  cursor: not-allowed;
  opacity: 0.6;
}

.btn-primary:hover:not(:disabled) {
  background: #006699;
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-secondary-inline {
  background: #fff;
  color: #0f172a;
  border: 1px solid #cbd5e1;
  padding: 0.85rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.95rem;
  font-weight: 600;
}

.btn-secondary-inline:hover:not(:disabled) {
  background: #f8fafc;
}

.btn-secondary-inline:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-ghost-inline {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
  padding: 0.85rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.95rem;
  font-weight: 600;
}

.btn-ghost-inline:hover {
  background: #dbeafe;
}

.bot-link {
  display: inline-block;
  margin: 1.5rem 0;
  padding: 1rem 2rem;
  background: #0088cc;
  color: white;
  text-decoration: none;
  border-radius: 4px;
  font-weight: 600;
  font-size: 1.1rem;
  transition: background 0.3s;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.bot-link:hover {
  background: #006699;
  text-decoration: none;
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.telegram-icon {
  width: 24px;
  height: 24px;
}

.token-expiry {
  margin-top: 1rem;
  color: #666;
  font-size: 0.95rem;
}

.start-command-section {
  margin: 1rem 0 1.25rem;
  padding: 0.9rem;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  border-radius: 8px;
}

.start-command-title {
  margin: 0 0 0.5rem;
  color: #1e3a8a;
  font-size: 0.95rem;
}

.start-command-code {
  display: block;
  margin-bottom: 0.65rem;
  padding: 0.55rem 0.65rem;
  background: #fff;
  border: 1px solid #dbeafe;
  border-radius: 6px;
  color: #0f172a;
  font-size: 0.92rem;
  word-break: break-all;
}

.telegram-linked {
  text-align: center;
}

.telegram-avatar-wrap {
  margin-bottom: 0.8rem;
}

.telegram-avatar {
  width: 84px;
  height: 84px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #7dd3fc;
  box-shadow: 0 8px 22px rgba(2, 132, 199, 0.25);
}

.linked-status {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  color: #28a745;
  font-weight: 600;
  font-size: 1.1rem;
}

.check-icon {
  width: 24px;
  height: 24px;
  color: #28a745;
}

.telegram-username {
  font-size: 1.3rem;
  color: #0088cc;
  margin-bottom: 1.5rem;
  font-weight: 600;
}

.telegram-chat-id {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  margin: 0 0 1.5rem;
  color: #334155;
}

.telegram-chat-id code {
  font-size: 0.95rem;
}

.btn-copy {
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #334155;
  border-radius: 6px;
  padding: 0.45rem 0.75rem;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-copy:hover {
  background: #f1f5f9;
}

.btn-danger {
  background: #dc3545;
  color: white;
  border: none;
  padding: 0.85rem 2rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 600;
  transition: background 0.3s;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-danger:disabled {
  background: #ccc;
  cursor: not-allowed;
  opacity: 0.6;
}

.btn-danger:hover:not(:disabled) {
  background: #c82333;
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.danger-zone {
  margin-top: 1rem;
  display: flex;
  justify-content: flex-end;
}

.btn-danger-outline {
  border: 1px solid #ef4444;
  color: #ef4444;
  background: #fff;
  border-radius: 6px;
  padding: 0.55rem 0.9rem;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-danger-outline:hover:enabled {
  background: #fef2f2;
}

.btn-danger-outline:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.alert {
  background: #e7f3ff;
  border: 1px solid #bee5eb;
  border-radius: 4px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.alert-info {
  background: #e7f3ff;
  border-color: #bee5eb;
}

.alert-info strong {
  color: #0c5460;
}

code {
  background: #f4f4f4;
  padding: 0.2rem 0.4rem;
  border-radius: 3px;
  font-family: monospace;
  font-size: 0.9rem;
}


.subscription-section {
  margin: 2rem 0;
  padding: 1.5rem;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  background: #f9f9f9;
}

.subscription-section h2 {
  margin-top: 0;
  margin-bottom: 1rem;
  color: #333;
  font-size: 1.5rem;
}

.subscription-info {
  margin-top: 1rem;
}

.status-active {
  color: #28a745;
  font-weight: 600;
}

.no-subscription {
  text-align: center;
  padding: 1rem;
}

.no-subscription p {
  margin-bottom: 1rem;
  color: #666;
}
</style>
