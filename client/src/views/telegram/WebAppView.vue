<template>
  <div class="webapp-container">
    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>
    <div class="bg-grid"></div>

    <!-- Главная страница каталога -->
    <div v-if="currentView === 'catalog'" class="content panel-shell">
      <!-- Шапка магазина -->
      <div class="shop-header">
        <div>
          <p class="kicker">WEB APP STORE</p>
          <h1>{{ shop?.name || 'Магазин' }}</h1>
        </div>
        <div class="cart-icon" @click="goToCart">
          🛒 <span class="cart-count" v-if="cartTotalItems">{{ cartTotalItems }}</span>
        </div>
      </div>
      
      <p class="delivery">
        Доставка: {{ shop?.delivery_name || 'Не указана' }} - {{ Number(shop?.delivery_price || 0) }} ₽
      </p>

      <!-- Поиск и фильтры -->
      <div class="filters">
        <input 
          type="text" 
          v-model="searchQuery" 
          placeholder="Поиск товаров..."
          @input="debouncedSearch"
        >
        <select v-model="selectedCategory" @change="loadProducts">
          <option value="">Категории</option>
          <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">{{ cat.name }}</option>
        </select>
      </div>

      <!-- Каталог товаров -->
      <div class="products-grid" v-if="products.length">
        <div v-for="(product, index) in products" :key="product.id" class="product-card" :style="{ '--delay': `${index * 60}ms` }">
          <div class="product-image" v-if="product.image">
            <img :src="product.image" :alt="product.name">
          </div>
          <div class="product-info">
            <h3>{{ product.name }}</h3>
            <p class="price">{{ product.price }} ₽</p>
            <p class="description">{{ product.description }}</p>
            <p class="category">{{ product.category_name || 'Без категории' }}</p>
            <button 
              class="add-to-cart" 
              @click="addToCart(product)"
              :disabled="!product.in_stock"
            >
              {{ product.in_stock ? 'В корзину' : 'Нет в наличии' }}
            </button>
          </div>
        </div>
      </div>

      <div v-else-if="!loading" class="empty-state">
        <p>Товаров не найдено</p>
      </div>
    </div>

    <!-- Страница корзины -->
    <div v-else-if="currentView === 'cart'" class="content panel-shell cart-view">
      <div class="cart-header">
        <button class="back-btn" @click="currentView = 'catalog'">← Назад</button>
        <h2>Корзина</h2>
        <div class="cart-icon" @click="goToCart">
          🛒 <span class="cart-count" v-if="cartTotalItems">{{ cartTotalItems }}</span>
        </div>
      </div>

      <div v-if="cartItems.length" class="cart-items">
        <div v-for="(item, index) in cartItems" :key="item.id" class="cart-item" :style="{ '--delay': `${index * 60}ms` }">
          <div class="item-info">
            <h3>{{ item.name }}</h3>
            <p class="item-price">{{ item.price }} ₽</p>
          </div>
          <div class="item-quantity">
            <button @click="updateQuantity(item.id, -1)" class="qty-btn">−</button>
            <span class="qty">{{ item.quantity }}</span>
            <button @click="updateQuantity(item.id, 1)" class="qty-btn">+</button>
            <button @click="removeFromCart(item.id)" class="remove-btn">🗑️</button>
          </div>
        </div>

        <div class="cart-total">
          <div class="total-row">
            <span>Товары:</span>
            <span>{{ cartSubtotal }} ₽</span>
          </div>
          <div class="total-row">
            <span>Доставка:</span>
            <span>{{ shop.delivery_price }} ₽</span>
          </div>
          <div class="total-row grand-total">
            <span>Итого:</span>
            <span>{{ cartTotal }} ₽</span>
          </div>
        </div>

        <button class="checkout-btn" @click="goToCheckout">Оформить заказ</button>
      </div>

      <div v-else class="empty-state">
        <p>Корзина пуста</p>
        <button class="continue-shopping" @click="currentView = 'catalog'">Продолжить покупки</button>
      </div>
    </div>

    <!-- Страница оформления заказа -->
    <div v-else-if="currentView === 'checkout'" class="content panel-shell checkout-view">
      <div class="checkout-header">
        <button class="back-btn" @click="currentView = 'cart'">← Назад</button>
        <h2>Оформление заказа</h2>
      </div>

      <form @submit.prevent="submitOrder" class="checkout-form">
        <div class="form-group">
          <label>Ваше имя *</label>
          <input 
            type="text" 
            v-model="orderForm.name" 
            required
            placeholder="Иван Петров"
          >
        </div>

        <div class="form-group">
          <label>Телефон *</label>
          <input 
            type="tel" 
            v-model="orderForm.phone" 
            required
            placeholder="+7 999 123-45-67"
          >
        </div>

        <div class="form-group">
          <label>Способ доставки</label>
          <input
            type="text"
            :value="`${shop?.delivery_name || 'Доставка'} (${Number(shop?.delivery_price || 0)} ₽)`"
            readonly
          >
        </div>

        <div class="manager-hint" v-if="hasManagerContact">
          После оформления вы сможете написать менеджеру в Telegram для подтверждения и оплаты.
        </div>

        <div class="order-summary">
          <h3>Ваш заказ</h3>
          <div v-for="item in cartItems" :key="item.id" class="summary-item">
            <span>{{ item.name }} x{{ item.quantity }}</span>
            <span>{{ item.price * item.quantity }} ₽</span>
          </div>
          <div class="summary-total">
            <span>Доставка:</span>
            <span>{{ shop.delivery_price }} ₽</span>
          </div>
          <div class="summary-total grand-total">
            <span>Итого заказа:</span>
            <span>{{ cartTotal }} ₽</span>
          </div>
        </div>

        <button type="submit" class="submit-order" :disabled="orderLoading">
          {{ orderLoading ? 'Сохраняем...' : 'Подтвердить заказ' }}
        </button>
      </form>

      <p v-if="orderError" class="order-error">{{ orderError }}</p>

      <!-- Сообщение об успехе -->
      <div v-if="orderSuccess" class="success-message">
        <h3>✅ Заказ оформлен!</h3>
        <p>Заказ №{{ orderNumber }} создан.</p>
        <p>Менеджер свяжется с вами для подтверждения и оплаты заказа.</p>
        <button
          v-if="hasManagerContact"
          class="continue-shopping"
          @click="openManagerContact"
        >
          Написать менеджеру
        </button>
        <button class="continue-shopping" @click="resetOrder">Вернуться в магазин</button>
      </div>
    </div>

    <!-- Состояние загрузки -->
    <div v-if="loading" class="loading">
      Загрузка магазина...
    </div>
    
    <div v-else-if="error" class="error">
      {{ error }}
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import debounce from 'lodash/debounce'

export default {
  name: 'WebAppView',
  setup() {
    const route = useRoute()
    const shopId = route.query.shop || route.query.shopId
    
    const loading = ref(true)
    const error = ref(null)
    const shop = ref(null)
    const products = ref([])
    const categories = ref([])
    const searchQuery = ref('')
    const selectedCategory = ref('')
    const currentView = ref('catalog') // 'catalog', 'cart', 'checkout'
    
    const normalizeShopId = (id) => String(id ?? '').trim()
    const normalizedShopId = normalizeShopId(shopId)
    const cartStorageKey = computed(() => `webapp_cart_shop_${normalizedShopId}`)

    const readCartFromStorage = () => {
      const key = cartStorageKey.value
      const raw = localStorage.getItem(key)

      if (!raw) {
        return {}
      }

      try {
        const parsed = JSON.parse(raw)
        return parsed && typeof parsed === 'object' ? parsed : {}
      } catch {
        return {}
      }
    }

    const persistCart = () => {
      const key = cartStorageKey.value
      localStorage.setItem(key, JSON.stringify(cart.value))
    }

    // Корзина в localStorage (изолирована по магазину)
    const cart = ref({})

    // Форма заказа
    const orderForm = reactive({
      name: '',
      phone: ''
    })
    const orderLoading = ref(false)
    const orderSuccess = ref(false)
    const orderNumber = ref(null)
    const orderError = ref('')
    const orderTotalForManager = ref(0)

    const cartItems = computed(() => {
      return Object.values(cart.value)
    })

    const cartTotalItems = computed(() => {
      return cartItems.value.reduce((sum, item) => sum + item.quantity, 0)
    })

    const cartSubtotal = computed(() => {
      return cartItems.value.reduce((sum, item) => {
        const price = parseFloat(item.price) || 0
        return sum + (price * item.quantity)
      }, 0)
    })
   const cartTotal = computed(() => {
      const subtotal = cartSubtotal.value
      const delivery = parseFloat(shop.value?.delivery_price) || 0
      return subtotal + delivery
    })

    const resolveCategoryName = (category) => {
      if (!category) return null
      if (typeof category === 'string') return category
      if (typeof category === 'object' && category.name) return category.name
      return null
    }

    onMounted(async () => {
      if (window.Telegram?.WebApp) {
        window.Telegram.WebApp.ready()
        window.Telegram.WebApp.expand()
      }

      if (!shopId) {
        error.value = 'Не указан ID магазина'
        loading.value = false
        return
      }

      cart.value = readCartFromStorage()

      try {
        await Promise.all([loadShop(), loadProducts()])
      } finally {
        loading.value = false
      }
    })

    const loadShop = async () => {
      try {
        const response = await axios.get(`/api/shops/${shopId}/public`)
        shop.value = response.data.shop
      } catch (err) {
        error.value = 'Ошибка загрузки магазина'
        console.error(err)
      }
    }

    const loadProducts = async () => {
      try {
        const response = await axios.get(`/api/shops/${shopId}/products/public`, {
          params: {
            search: searchQuery.value,
            category: selectedCategory.value
          }
        })
        products.value = (response.data.products || []).map(product => ({
          ...product,
          category_name: resolveCategoryName(product.category)
        }))
        categories.value = response.data.categories || []
      } catch (err) {
        console.error('Ошибка загрузки товаров:', err)
      }
    }

    const debouncedSearch = debounce(loadProducts, 300)

    const addToCart = (product) => {
      if (!product.in_stock) return
      
      if (cart.value[product.id]) {
        cart.value[product.id].quantity++
      } else {
        cart.value[product.id] = {
          id: product.id,
          name: product.name,
          price: product.price,
          quantity: 1
        }
      }
      
      persistCart()
      
      if (window.Telegram?.WebApp) {
        window.Telegram.WebApp.showAlert(`Товар "${product.name}" добавлен в корзину`)
      }
    }

    const updateQuantity = (productId, delta) => {
      if (cart.value[productId]) {
        const newQty = cart.value[productId].quantity + delta
        if (newQty <= 0) {
          delete cart.value[productId]
        } else {
          cart.value[productId].quantity = newQty
        }
        persistCart()
      }
    }

    const removeFromCart = (productId) => {
      delete cart.value[productId]
      persistCart()
    }

    const goToCart = () => {
      currentView.value = 'cart'
    }

    const goToCheckout = () => {
      if (cartItems.value.length === 0) {
        if (window.Telegram?.WebApp) {
          window.Telegram.WebApp.showAlert('Корзина пуста')
        }
        return
      }
      currentView.value = 'checkout'
    }

    const managerUsername = computed(() => {
      const raw = shop.value?.manager_telegram_username || ''
      const normalized = String(raw).trim().replace(/^@+/, '')
      return normalized
    })

    const hasManagerContact = computed(() => Boolean(managerUsername.value))

    const managerBaseUrl = computed(() => {
      return hasManagerContact.value ? `https://t.me/${managerUsername.value}` : ''
    })

    const managerMessageText = computed(() => {
      const shopName = shop.value?.name || 'Магазин'
      const amount = Number(orderTotalForManager.value || 0)
      const amountLabel = Number.isFinite(amount) ? `${amount} ₽` : 'уточняется'
      return [
        `Здравствуйте! Заказ #${orderNumber.value || ''}`.trim(),
        `Магазин: ${shopName}`,
        `Имя: ${orderForm.name || '-'}`,
        `Телефон: ${orderForm.phone || '-'}`,
        `Сумма заказа: ${amountLabel}`,
      ].join('\n')
    })

    const managerContactUrl = computed(() => {
      if (!managerBaseUrl.value) {
        return ''
      }
      return `${managerBaseUrl.value}?text=${encodeURIComponent(managerMessageText.value)}`
    })

    const openManagerContact = () => {
      if (!managerContactUrl.value) {
        return
      }

      if (window.Telegram?.WebApp?.openLink) {
        window.Telegram.WebApp.openLink(managerContactUrl.value)
      } else {
        window.open(managerContactUrl.value, '_blank')
      }
    }

    const handleOrderSuccess = (data) => {
      orderNumber.value = data?.order?.id || null
      orderSuccess.value = true
      orderTotalForManager.value = Number(data?.amounts?.total || 0)

      cart.value = {}
      localStorage.removeItem(cartStorageKey.value)
    }

    const submitOrder = async () => {
      if (!orderForm.name || !orderForm.phone) {
        if (window.Telegram?.WebApp) {
          window.Telegram.WebApp.showAlert('Заполните все поля')
        }
        return
      }

      orderLoading.value = true
      orderError.value = ''
      const telegramInitData = window.Telegram?.WebApp?.initData || ''
      const requestConfig = telegramInitData
        ? { headers: { 'X-Telegram-Init-Data': telegramInitData } }
        : {}

      try {
        const payload = {
          shop_id: parseInt(shopId),
          customer_name: orderForm.name,
          phone: orderForm.phone,
          items: cartItems.value.map(item => ({
            id: item.id,
            quantity: item.quantity
          })),
          create_payment: false
        }

        const response = await axios.post('/api/orders', payload, requestConfig)

        if (response.data.success) {
          handleOrderSuccess(response.data)

          if (window.Telegram?.WebApp) {
            const message = hasManagerContact.value
              ? `Заказ #${orderNumber.value} создан. Напишите менеджеру для подтверждения.`
              : `Заказ #${orderNumber.value} создан.`
            window.Telegram.WebApp.showAlert(message)
          }
        }
      } catch (error) {
        console.error('Ошибка при создании заказа:', error)

        orderError.value = error?.response?.data?.message || 'Ошибка при создании заказа'
        if (window.Telegram?.WebApp) {
          window.Telegram.WebApp.showAlert(orderError.value)
        }
      } finally {
        orderLoading.value = false
      }
    }

    const resetOrder = () => {
      orderSuccess.value = false
      orderNumber.value = null
      orderTotalForManager.value = 0
      orderError.value = ''
      orderForm.name = ''
      orderForm.phone = ''
      currentView.value = 'catalog'
    }

    return {
      loading,
      error,
      shop,
      products,
      categories,
      searchQuery,
      selectedCategory,
      currentView,
      cartItems,
      cartTotalItems,
      cartSubtotal,
      cartTotal,
      orderForm,
      orderLoading,
      orderSuccess,
      orderNumber,
      orderError,
      hasManagerContact,
      loadProducts,
      debouncedSearch,
      addToCart,
      updateQuantity,
      removeFromCart,
      goToCart,
      goToCheckout,
      openManagerContact,
      submitOrder,
      resetOrder
    }
  }
}
</script>

<style scoped>
:root {
  --bg-0: #070b18;
  --bg-1: #0d1326;
  --ink-0: #eff6ff;
  --ink-1: #a3b5db;
  --line: rgba(138, 178, 255, 0.24);
  --surface: rgba(14, 24, 46, 0.78);
  --surface-soft: rgba(17, 29, 55, 0.65);
  --accent: #38e8ff;
  --accent-2: #41ffbf;
  --danger: #ff7383;
}

.webapp-container {
  position: relative;
  min-height: 100vh;
  overflow: hidden;
  background:
    radial-gradient(circle at 15% 20%, rgba(56, 232, 255, 0.16), transparent 44%),
    radial-gradient(circle at 85% 8%, rgba(65, 255, 191, 0.12), transparent 36%),
    linear-gradient(165deg, var(--bg-0) 0%, var(--bg-1) 55%, #090f22 100%);
  font-family: "Space Grotesk", "Segoe UI", sans-serif;
  color: var(--ink-0);
}

.bg-orb {
  position: absolute;
  border-radius: 999px;
  filter: blur(44px);
  pointer-events: none;
}

.orb-1 {
  width: 320px;
  height: 320px;
  background: rgba(56, 232, 255, 0.14);
  left: -80px;
  top: -40px;
  animation: driftA 14s ease-in-out infinite;
}

.orb-2 {
  width: 360px;
  height: 360px;
  background: rgba(65, 255, 191, 0.13);
  right: -140px;
  top: 36%;
  animation: driftB 18s ease-in-out infinite;
}

.bg-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(138, 178, 255, 0.09) 1px, transparent 1px),
    linear-gradient(90deg, rgba(138, 178, 255, 0.09) 1px, transparent 1px);
  background-size: 28px 28px;
  mask-image: radial-gradient(circle at 50% 40%, black 34%, transparent 100%);
  opacity: 0.42;
  pointer-events: none;
}

.loading,
.error {
  display: grid;
  place-items: center;
  min-height: 100vh;
  text-align: center;
  padding: 2rem;
  color: var(--ink-0);
}

.error {
  color: #ffd7dc;
}

.order-error {
  margin-top: 0.85rem;
  color: #ffb2bd;
  background: rgba(255, 115, 131, 0.12);
  border: 1px solid rgba(255, 115, 131, 0.4);
  border-radius: 10px;
  padding: 0.7rem 0.8rem;
}

.manager-hint {
  margin-bottom: 0.85rem;
  border-radius: 10px;
  border: 1px solid rgba(65, 255, 191, 0.35);
  background: rgba(13, 34, 33, 0.42);
  color: #b9ffe9;
  font-size: 0.9rem;
  line-height: 1.35;
  padding: 0.62rem 0.7rem;
}

.content {
  position: relative;
  z-index: 1;
  padding: 1rem;
  padding-bottom: 80px;
  animation: panelIn 520ms cubic-bezier(0.17, 0.84, 0.44, 1);
}

.panel-shell {
  background: var(--surface-soft);
  border: 1px solid var(--line);
  border-radius: 24px;
  margin: 10px;
  box-shadow: 0 14px 40px rgba(1, 8, 23, 0.45);
  backdrop-filter: blur(16px);
}

.kicker {
  margin: 0 0 0.35rem;
  font-size: 0.68rem;
  color: var(--accent);
  letter-spacing: 0.2em;
  font-weight: 700;
}

.shop-header,
.cart-header,
.checkout-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  gap: 0.8rem;
}

.cart-header,
.checkout-header {
  justify-content: flex-start;
}

.back-btn {
  background: rgba(56, 232, 255, 0.08);
  border: 1px solid rgba(56, 232, 255, 0.32);
  color: var(--ink-0);
  font-size: 0.92rem;
  padding: 0.52rem 0.78rem;
  border-radius: 10px;
  cursor: pointer;
  transition: transform 140ms ease, background 140ms ease;
}

.back-btn:active {
  transform: translateY(1px);
}

h1,
h2 {
  margin: 0;
  color: var(--ink-0);
  line-height: 1.15;
}

h1 {
  font-size: clamp(1.3rem, 4vw, 1.8rem);
}

h2 {
  font-size: clamp(1.15rem, 3.5vw, 1.45rem);
}

.cart-icon {
  position: relative;
  font-size: 1.28rem;
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  cursor: pointer;
  border: 1px solid rgba(65, 255, 191, 0.4);
  background: rgba(65, 255, 191, 0.09);
  transition: transform 160ms ease, box-shadow 180ms ease;
}

.cart-icon:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(65, 255, 191, 0.25);
}

.cart-count {
  position: absolute;
  top: -7px;
  right: -8px;
  background: linear-gradient(120deg, var(--accent), var(--accent-2));
  color: #06131a;
  font-size: 0.72rem;
  font-weight: 800;
  min-width: 18px;
  height: 18px;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
}

.delivery {
  margin: 0 0 1rem;
  padding: 0.8rem 0.9rem;
  border-radius: 12px;
  border: 1px solid rgba(56, 232, 255, 0.22);
  background: rgba(10, 19, 37, 0.55);
  color: var(--ink-1);
}

.filters {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.55rem;
  margin-bottom: 1.2rem;
}

.filters input,
.filters select {
  width: 100%;
  padding: 0.7rem 0.8rem;
  border-radius: 12px;
  border: 1px solid rgba(138, 178, 255, 0.35);
  background: rgba(10, 18, 37, 0.74);
  color: var(--ink-0);
  font-size: 0.92rem;
  outline: none;
  transition: border-color 150ms ease, box-shadow 150ms ease;
}

.filters input::placeholder {
  color: #9bb2db;
}

.filters input:focus,
.filters select:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 2px rgba(56, 232, 255, 0.16);
}

.filters select {
  max-width: 136px;
}

.products-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.85rem;
}

.product-card {
  --delay: 0ms;
  display: flex;
  gap: 0.9rem;
  border-radius: 16px;
  padding: 0.85rem;
  border: 1px solid rgba(138, 178, 255, 0.25);
  background: var(--surface);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03), 0 10px 30px rgba(3, 10, 26, 0.38);
  opacity: 0;
  transform: translateY(12px);
  animation: revealItem 440ms ease forwards;
  animation-delay: var(--delay);
}

.product-image {
  width: 84px;
  height: 84px;
  flex-shrink: 0;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid rgba(138, 178, 255, 0.2);
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-info {
  min-width: 0;
  flex: 1;
}

.product-info h3 {
  margin: 0 0 0.22rem;
  font-size: 1rem;
  color: var(--ink-0);
}

.price {
  margin: 0 0 0.2rem;
  font-weight: 700;
  color: var(--accent-2);
}

.description {
  margin: 0.2rem 0;
  color: var(--ink-1);
  font-size: 0.86rem;
  line-height: 1.4;
}

.category {
  margin: 0.2rem 0;
  font-size: 0.74rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #86d7ff;
}

.add-to-cart,
.checkout-btn,
.submit-order,
.continue-shopping {
  border: 0;
  border-radius: 12px;
  padding: 0.72rem 0.95rem;
  font-weight: 700;
  cursor: pointer;
  transition: transform 150ms ease, box-shadow 170ms ease, opacity 140ms ease;
}

.add-to-cart {
  width: 100%;
  margin-top: 0.58rem;
  background: linear-gradient(120deg, #2cd6ff 0%, #35f5cf 100%);
  color: #032025;
  box-shadow: 0 10px 20px rgba(49, 224, 241, 0.22);
}

.add-to-cart:disabled {
  background: rgba(138, 178, 255, 0.24);
  color: rgba(239, 246, 255, 0.65);
  box-shadow: none;
  cursor: not-allowed;
}

.add-to-cart:not(:disabled):hover,
.checkout-btn:hover,
.submit-order:hover,
.continue-shopping:hover {
  transform: translateY(-1px);
}

.empty-state {
  text-align: center;
  padding: 3rem 1.2rem;
  color: var(--ink-1);
}

.cart-items {
  margin-top: 0.7rem;
}

.cart-item {
  --delay: 0ms;
  border-radius: 14px;
  padding: 0.9rem;
  margin-bottom: 0.72rem;
  border: 1px solid rgba(138, 178, 255, 0.25);
  background: var(--surface);
  opacity: 0;
  transform: translateY(12px);
  animation: revealItem 430ms ease forwards;
  animation-delay: var(--delay);
}

.item-info {
  margin-bottom: 0.45rem;
}

.item-info h3 {
  margin: 0 0 0.16rem;
  font-size: 0.98rem;
}

.item-price {
  color: var(--accent-2);
  font-weight: 700;
  margin: 0;
}

.item-quantity {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.qty-btn {
  width: 30px;
  height: 30px;
  border-radius: 9px;
  border: 1px solid rgba(138, 178, 255, 0.34);
  background: rgba(10, 20, 38, 0.7);
  color: var(--ink-0);
  cursor: pointer;
}

.qty {
  min-width: 28px;
  text-align: center;
  font-weight: 700;
}

.remove-btn {
  margin-left: auto;
  border: 0;
  background: rgba(255, 115, 131, 0.12);
  color: #ffc7cf;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  cursor: pointer;
}

.cart-total,
.order-summary {
  border-radius: 16px;
  padding: 1rem;
  border: 1px solid rgba(56, 232, 255, 0.28);
  background: rgba(10, 19, 37, 0.7);
  margin-top: 0.95rem;
}

.total-row,
.summary-item,
.summary-total {
  display: flex;
  justify-content: space-between;
  gap: 0.8rem;
  color: var(--ink-1);
  margin-bottom: 0.5rem;
}

.grand-total {
  margin-top: 0.65rem;
  padding-top: 0.6rem;
  border-top: 1px dashed rgba(138, 178, 255, 0.34);
  font-weight: 800;
  color: var(--ink-0);
}

.checkout-btn,
.submit-order {
  width: 100%;
  margin-top: 1rem;
  background: linear-gradient(120deg, #34d8ff 0%, #43ffc4 100%);
  color: #032025;
  box-shadow: 0 11px 24px rgba(52, 232, 255, 0.22);
}

.checkout-form {
  margin-top: 0.8rem;
}

.form-group {
  margin-bottom: 0.92rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.45rem;
  font-size: 0.9rem;
  color: #d8e9ff;
}

.form-group input {
  width: 100%;
  border-radius: 12px;
  border: 1px solid rgba(138, 178, 255, 0.34);
  background: rgba(10, 18, 37, 0.74);
  color: var(--ink-0);
  padding: 0.76rem 0.82rem;
  outline: none;
}

.form-group input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 2px rgba(56, 232, 255, 0.16);
}

.submit-order:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  box-shadow: none;
}

.success-message {
  border-radius: 14px;
  border: 1px solid rgba(65, 255, 191, 0.45);
  background: rgba(13, 29, 36, 0.68);
  padding: 1rem;
  margin-top: 1rem;
  text-align: center;
}

.success-message h3 {
  margin: 0 0 0.45rem;
  color: #8effdf;
}

.continue-shopping {
  margin-top: 0.7rem;
  background: linear-gradient(120deg, #51c4ff 0%, #65ffe6 100%);
  color: #07242b;
}

@keyframes driftA {
  0%, 100% { transform: translate3d(0, 0, 0); }
  50% { transform: translate3d(22px, -10px, 0); }
}

@keyframes driftB {
  0%, 100% { transform: translate3d(0, 0, 0); }
  50% { transform: translate3d(-18px, 16px, 0); }
}

@keyframes panelIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes revealItem {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (min-width: 640px) {
  .content {
    max-width: 740px;
    margin: 0 auto;
    padding: 1.25rem;
    padding-bottom: 92px;
  }

  .products-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 480px) {
  .panel-shell {
    margin: 8px;
    border-radius: 18px;
  }

  .filters {
    grid-template-columns: 1fr;
  }

  .filters select {
    max-width: 100%;
  }

  .product-card {
    padding: 0.72rem;
  }

  .product-image {
    width: 74px;
    height: 74px;
  }
}
</style>
