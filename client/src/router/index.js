import { createRouter, createWebHistory } from 'vue-router';
import CreateShopView from '../views/CreateShopView.vue';
import ShopSettingsView from '../views/ShopSettingsView.vue';
import LoginView from '../views/LoginView.vue';
import RegisterView from '../views/RegisterView.vue';
import ForgotPasswordView from '../views/ForgotPasswordView.vue';
import PasswordResetView from '../views/PasswordResetView.vue';
import ForcePasswordChangeView from '../views/ForcePasswordChangeView.vue';
import ProfileView from '../views/ProfileView.vue';
import PlansView from '../views/PlansView.vue'; 
import PrivacyPolicyView from '../views/PrivacyPolicyView.vue';
import PublicLandingView from '../views/PublicLandingView.vue';
import DashboardLayout from '../views/dashboard/DashboardLayout.vue';
import { useAuth } from '../composables/useAuth';

const routes = [
  {
    path: '/',
    name: 'Landing',
    component: PublicLandingView,
    meta: { requiresGuest: true }
  },
  {
    path: '/dashboard',
    component: DashboardLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/dashboard/products',
      },
      {
        path: 'products',
        name: 'DashboardProducts',
        component: () => import('../views/dashboard/DashboardProductsView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'orders',
        name: 'DashboardOrders',
        component: () => import('../views/dashboard/DashboardOrdersView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'marketing',
        name: 'DashboardMarketing',
        component: () => import('../views/dashboard/DashboardMarketingView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'analytics',
        name: 'DashboardAnalytics',
        component: () => import('../views/dashboard/DashboardAnalyticsView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'settings',
        name: 'DashboardSettings',
        component: () => import('../views/dashboard/DashboardSettingsView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'theme',
        name: 'DashboardTheme',
        component: () => import('../views/dashboard/DashboardThemeView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'help',
        name: 'DashboardHelp',
        component: () => import('../views/dashboard/DashboardHelpView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'language',
        name: 'DashboardLanguage',
        component: () => import('../views/dashboard/DashboardLanguageView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'profile',
        name: 'DashboardProfile',
        component: () => import('../views/dashboard/DashboardProfileView.vue'),
        meta: { requiresAuth: true },
      },
    ],
  },
  {
    path: '/create-shop',
    name: 'CreateShop',
    component: CreateShopView,
    meta: { requiresAuth: true }
  },
  {
    path: '/shops',
    name: 'Shops',
    component: () => import('../views/ShopsView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/shops/:shopId/settings',
    name: 'ShopSettings',
    component: ShopSettingsView,
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginView,
    meta: { requiresGuest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: RegisterView,
    meta: { requiresGuest: true }
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: ForgotPasswordView,
    meta: { requiresGuest: true }
  },
  {
    path: '/password-reset/:token',
    name: 'PasswordReset',
    component: PasswordResetView,
    meta: { requiresGuest: true }
  },
  {
    path: '/force-password-change',
    name: 'ForcePasswordChange',
    component: ForcePasswordChangeView,
    meta: { requiresAuth: true }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: ProfileView,
    meta: { requiresAuth: true }
  },
  { 
    path: '/plans',
    name: 'Plans',
    component: PlansView,
    meta: { requiresAuth: true }
  },
  {
    path: '/privacy',
    name: 'PrivacyPolicy',
    component: PrivacyPolicyView,
    meta: { requiresAuth: false }
  },
      {
    path: '/shops/:shopId/products',
    name: 'Products',
    component: () => import('../views/shop/ProductsView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/app',
    name: 'WebApp',
    component: () => import('../views/telegram/WebAppView.vue'),
    meta: { requiresAuth: false }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Навигационный гвард для защиты маршрутов
router.beforeEach(async (to, from, next) => {
  const { token, user, loadProfile } = useAuth();

  if (token.value && !user.value) {
    await loadProfile();
  }

  const requiresPasswordChange = Boolean(user.value?.requires_password_change);
  
  // Если маршрут требует авторизации
  if (to.meta.requiresAuth && !token.value) {
    next('/login');
  }
  else if (requiresPasswordChange && to.path !== '/force-password-change') {
    next('/force-password-change');
  }
  else if (!requiresPasswordChange && to.path === '/force-password-change' && token.value) {
    next('/shops');
  }
  // Если маршрут требует гостя (не авторизованного)
  else if (to.meta.requiresGuest && token.value) {
    if (requiresPasswordChange) {
      next('/force-password-change');
    } else {
      next('/shops');
    }
  }
  // Все остальные случаи
  else {
    next();
  }
});

export default router;
