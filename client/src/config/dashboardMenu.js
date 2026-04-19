export const dashboardMenuGroups = [
  {
    key: 'operations',
    title: 'Операции',
    items: [
      { key: 'orders', label: 'Заказы', to: '/dashboard/orders', icon: '🏷️' },
      { key: 'analytics', label: 'Аналитика', to: '/dashboard/analytics', icon: '📊' },
    ],
  },
  {
    key: 'catalog',
    title: 'Каталог',
    items: [
      { key: 'products', label: 'Товары', to: '/dashboard/products', icon: '👜' },
      { key: 'marketing', label: 'Маркетинг', to: '/dashboard/marketing', icon: '📣' },
    ],
  },
  {
    key: 'system',
    title: 'Система',
    items: [
      { key: 'settings', label: 'Настройки', to: '/dashboard/settings', icon: '⚙️' },
      { key: 'help', label: 'Помощь', to: '/dashboard/help', icon: '❔' },
      { key: 'language', label: 'Язык', to: '/dashboard/language', icon: '🌐' },
      { key: 'profile', label: 'Профиль', to: '/dashboard/profile', icon: '👤' },
    ],
  },
]

export const dashboardMenu = dashboardMenuGroups.flatMap((group) => group.items)

export const dashboardQuickActions = [
  { key: 'qa-product', label: 'Новый товар', to: '/dashboard/products', icon: '+' },
  { key: 'qa-orders', label: 'Новый заказ', to: '/dashboard/orders', icon: '•' },
]
