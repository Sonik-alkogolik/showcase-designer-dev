import { ref, computed } from 'vue';
import axios from 'axios';

const token = ref(localStorage.getItem('auth_token') || null);
const user = ref(null);

// Устанавливаем токен при инициализации, если он есть
if (token.value) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
}

// Функция для установки токена
const setToken = (newToken) => {
  token.value = newToken;
  localStorage.setItem('auth_token', newToken);
  axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
};

// Функция для удаления токена
const removeToken = () => {
  token.value = null;
  localStorage.removeItem('auth_token');
  delete axios.defaults.headers.common['Authorization'];
};

export const useAuth = () => {
  const login = async (email, password) => {
    try {
      const response = await axios.post('/api/login', { email, password });
      const newToken = response.data.token;
      
      // Устанавливаем токен
      setToken(newToken);
      
      // Загружаем данные пользователя после входа
      await loadProfile();
      
      return { success: true };
    } catch (error) {
      return { success: false, error: error.response?.data?.message || 'Ошибка входа' };
    }
  };

  const register = async (name, email, password, password_confirmation) => {
    try {
      const response = await axios.post('/api/register', {
        name,
        email,
        password,
        password_confirmation,
      });
      const newToken = response.data.token;
      
      // Устанавливаем токен
      setToken(newToken);
      
      // Загружаем данные пользователя после регистрации
      await loadProfile();
      
      return { success: true };
    } catch (error) {
      return { success: false, error: error.response?.data?.message || 'Ошибка регистрации' };
    }
  };

  const logout = async () => {
    try {
      await axios.post('/api/logout');
    } catch (e) {
      console.warn('Logout error:', e);
    }
    removeToken();
    user.value = null;
  };

  // Загрузка данных профиля
   const loadProfile = async () => {
    if (!token.value) return null;
    
    try {
      const response = await axios.get('/api/profile');
      user.value = response.data;
      
      // Загружаем информацию о подписке
      try {
        const subscriptionResponse = await axios.get('/api/subscription/plans');
        if (subscriptionResponse.data.current_subscription) {
          user.value.subscription = subscriptionResponse.data.current_subscription;
          
          // Добавляем название тарифа для отображения
          const planKey = user.value.subscription.plan;
          const plans = subscriptionResponse.data.plans;
          if (plans && plans[planKey]) {
            user.value.subscription.plan_name = plans[planKey].name;
          }
        }
      } catch (subError) {
        console.warn('Error loading subscription:', subError);
      }
      
      return response.data;
    } catch (error) {
      console.error('Error loading profile:', error);
      // Если ошибка авторизации, очищаем токен
      if (error.response?.status === 401) {
        removeToken();
        user.value = null;
      }
      return null;
    }
  };
  // Генерация токена для привязки Telegram
  const generateTelegramLinkToken = async () => {
    try {
      const response = await axios.post('/api/profile/telegram/generate-token');
      return { success: true, data: response.data };
    } catch (error) {
      return { success: false, error: error.response?.data?.message || 'Ошибка генерации токена' };
    }
  };

  // Отвязка Telegram
  const unlinkTelegram = async () => {
    try {
      await axios.delete('/api/profile/telegram/unlink');
      
      // Обновляем данные пользователя
      if (user.value) {
        user.value.telegram_linked = false;
        user.value.telegram_id = null;
        user.value.telegram_username = null;
        user.value.telegram_avatar_url = null;
        user.value.telegram_linked_at = null;
      }
      
      return { success: true };
    } catch (error) {
      return { success: false, error: error.response?.data?.message || 'Ошибка отвязки Telegram' };
    }
  };

  const deleteAccount = async () => {
    try {
      await axios.delete('/api/profile');
      removeToken();
      user.value = null;
      return { success: true };
    } catch (error) {
      return { success: false, error: error.response?.data?.message || 'Ошибка удаления аккаунта' };
    }
  };

  // Проверка авторизации
  const isAuthenticated = computed(() => !!token.value);

  return { 
    token, 
    user,
    isAuthenticated,
    login, 
    register, 
    logout,
    loadProfile,
    generateTelegramLinkToken,
    unlinkTelegram,
    deleteAccount
  };
};
