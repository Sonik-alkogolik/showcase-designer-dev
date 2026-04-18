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
      const requiresPasswordChange = Boolean(response.data.requires_password_change);
      
      // Устанавливаем токен
      setToken(newToken);
      
      // Загружаем данные пользователя после входа
      await loadProfile();
      
      return { success: true, requiresPasswordChange };
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
      if (error.response?.status === 423 && user.value) {
        user.value.requires_password_change = true;
      }
      // Если ошибка авторизации, очищаем токен
      if (error.response?.status === 401) {
        removeToken();
        user.value = null;
      }
      return null;
    }
  };

  const forgotPasswordByEmail = async (email) => {
    try {
      const response = await axios.post('/api/forgot-password', { email });
      return { success: true, message: response.data?.status || 'Письмо отправлено' };
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.response?.data?.email?.[0] || 'Не удалось отправить письмо',
      };
    }
  };

  const resetPasswordByEmail = async (payload) => {
    try {
      const response = await axios.post('/api/reset-password', payload);
      return { success: true, message: response.data?.status || 'Пароль обновлён' };
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.response?.data?.email?.[0] || 'Не удалось обновить пароль',
      };
    }
  };

  const forgotPasswordByTelegram = async (email) => {
    try {
      const response = await axios.post('/api/forgot-password/telegram', { email });
      return { success: true, message: response.data?.message || 'Код отправлен в Telegram' };
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Не удалось отправить код в Telegram',
      };
    }
  };

  const resetPasswordByTelegram = async (email, tokenValue) => {
    try {
      const response = await axios.post('/api/reset-password/telegram', { email, token: tokenValue });
      return { success: true, message: response.data?.message || 'Временный пароль отправлен в Telegram' };
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.response?.data?.token?.[0] || 'Не удалось восстановить пароль',
      };
    }
  };

  const forceChangePassword = async (currentPassword, password, passwordConfirmation) => {
    try {
      const response = await axios.post('/api/profile/password/force-change', {
        current_password: currentPassword,
        password,
        password_confirmation: passwordConfirmation,
      });

      if (user.value) {
        user.value.requires_password_change = false;
      }

      return { success: true, message: response.data?.message || 'Пароль успешно изменен' };
    } catch (error) {
      return {
        success: false,
        error:
          error.response?.data?.message ||
          error.response?.data?.current_password?.[0] ||
          error.response?.data?.password?.[0] ||
          'Не удалось изменить пароль',
      };
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
    forgotPasswordByEmail,
    resetPasswordByEmail,
    forgotPasswordByTelegram,
    resetPasswordByTelegram,
    forceChangePassword,
    generateTelegramLinkToken,
    unlinkTelegram,
    deleteAccount
  };
};
