import { createRouter, createWebHistory } from 'vue-router';
import CreateShopView from '../views/CreateShopView.vue';
import LoginView from '../views/LoginView.vue';
import RegisterView from '../views/RegisterView.vue';
import ProfileView from '../views/ProfileView.vue';
import PlansView from '../views/PlansView.vue'; 
import { useAuth } from '../composables/useAuth';

const routes = [
  {
    path: '/',
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
    path: '/shops/:shopId/products',
    name: 'Products',
    component: () => import('../views/shop/ProductsView.vue'),
    meta: { requiresAuth: true }
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Навигационный гвард для защиты маршрутов
router.beforeEach((to, from, next) => {
  const { token } = useAuth();
  
  // Если маршрут требует авторизации
  if (to.meta.requiresAuth && !token.value) {
    next('/login');
  }
  // Если маршрут требует гостя (не авторизованного)
  else if (to.meta.requiresGuest && token.value) {
    next('/');
  }
  // Все остальные случаи
  else {
    next();
  }
});

export default router;