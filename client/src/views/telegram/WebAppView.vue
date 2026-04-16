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
        <div class="shop-actions">
          <button class="search-toggle" @click="toggleSearch">
            {{ showSearch ? '✕' : '🔍' }}
          </button>
          <div class="cart-icon" @click="setView('cart')">
            🛒 <span class="cart-count" v-if="cartTotalItems">{{ cartTotalItems }}</span>
          </div>
        </div>
      </div>

      <div v-if="showSearch" class="header-search">
        <input 
          ref="searchInput"
          type="text" 
          v-model="searchQuery" 
          placeholder="Поиск товаров..."
          @input="debouncedSearch"
        >
      </div>

      <div class="category-chips">
        <button
          class="chip"
          :class="{ active: selectedCategory === '' }"
          @click="selectCategory('')"
        >
          Весь магазин
        </button>
        <button
          v-for="cat in categories"
          :key="cat.id"
          class="chip"
          :class="{ active: selectedCategory === String(cat.id) }"
          @click="selectCategory(String(cat.id))"
        >
          {{ cat.name }}
        </button>
      </div>

      <div v-if="sliderProducts.length" class="hero-slider">
        <div class="hero-slide" @click="addToCart(sliderProducts[currentSlideIndex])">
          <img
            v-if="sliderProducts[currentSlideIndex]?.image"
            :src="sliderProducts[currentSlideIndex]?.image"
            :alt="sliderProducts[currentSlideIndex]?.name"
          >
          <div class="hero-slide-overlay">
            <h2>{{ sliderProducts[currentSlideIndex]?.name }}</h2>
            <p>{{ sliderProducts[currentSlideIndex]?.price }} ₽</p>
          </div>
        </div>
        <div class="hero-dots">
          <button
            v-for="(slide, idx) in sliderProducts"
            :key="slide.id"
            class="hero-dot"
            :class="{ active: currentSlideIndex === idx }"
            @click="goToSlide(idx)"
          />
        </div>
      </div>

      <!-- Каталог товаров -->
      <div class="products-grid" v-if="products.length">
        <div v-for="(product, index) in products" :key="product.id" class="product-card" :style="{ '--delay': `${index * 60}ms` }">
          <button class="fav-btn" :class="{ active: isFavorite(product.id) }" @click="toggleFavorite(product)">
            {{ isFavorite(product.id) ? '♥' : '♡' }}
          </button>
          <div class="product-image" v-if="product.image">
            <img :src="product.image" :alt="product.name">
          </div>
          <div class="product-info">
            <h3>{{ product.name }}</h3>
            <p class="price">{{ product.price }} ₽</p>
            <div class="product-description-wrapper">
              <p 
                class="description" 
                :class="{ expanded: isDescriptionExpanded(product.id) }"
                v-if="product.description"
              >
                {{ product.description }}
              </p>
              <button 
                v-if="product.description"
                @click="toggleDescription(product.id)"
                class="description-toggle-btn"
                :class="{ expanded: isDescriptionExpanded(product.id) }"
              >
                {{ isDescriptionExpanded(product.id) ? 'Скрыть' : 'Подробнее' }} ▼
              </button>
            </div>
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

    <!-- Страница избранного -->
    <div v-else-if="currentView === 'favorites'" class="content panel-shell">
      <div class="cart-header">
        <h2>Избранное</h2>
      </div>
      <div v-if="favoriteItems.length" class="products-grid">
        <div v-for="(product, index) in favoriteItems" :key="`fav-${product.id}`" class="product-card" :style="{ '--delay': `${index * 60}ms` }">
          <button class="fav-btn active" @click="toggleFavorite(product)">♥</button>
          <div class="product-image" v-if="product.image">
            <img :src="product.image" :alt="product.name">
          </div>
          <div class="product-info">
            <h3>{{ product.name }}</h3>
            <p class="price">{{ product.price }} ₽</p>
            <div class="product-description-wrapper">
              <p 
                class="description" 
                :class="{ expanded: isDescriptionExpanded(product.id) }"
                v-if="product.description"
              >
                {{ product.description }}
              </p>
              <button 
                v-if="product.description"
                @click="toggleDescription(product.id)"
                class="description-toggle-btn"
                :class="{ expanded: isDescriptionExpanded(product.id) }"
              >
                {{ isDescriptionExpanded(product.id) ? 'Скрыть' : 'Подробнее' }} ▼
              </button>
            </div>
            <button class="add-to-cart" @click="addToCart(product)">В корзину</button>
          </div>
        </div>
      </div>
      <div v-else class="empty-state">
        <p>В избранном пока пусто</p>
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

    <!-- Профиль -->
    <div v-else-if="currentView === 'profile'" class="content panel-shell">
      <div class="cart-header">
        <h2>Профиль</h2>
      </div>
      <div class="profile-card">
        <img v-if="resolvedProfileAvatar" :src="resolvedProfileAvatar" alt="avatar" class="profile-avatar">
        <div v-else class="profile-avatar profile-avatar-placeholder">👤</div>
        <h3>{{ telegramDisplayName }}</h3>
        <p class="profile-username" v-if="ownerAccountName">{{ ownerAccountName }}</p>
        <p class="profile-username" v-else-if="shop?.owner_profile?.email">{{ shop.owner_profile.email }}</p>
      </div>
      <div class="profile-list">
        <button class="profile-item" @click="setView('catalog')">Вернуться к покупкам</button>
        <button class="profile-item" @click="openSupportChat">Связаться с поддержкой</button>
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

    <div v-if="showBottomNav" class="bottom-nav">
      <button class="tab-btn" :class="{ active: currentView === 'catalog' }" @click="setView('catalog')">Главная</button>
      <button class="tab-btn" :class="{ active: currentView === 'favorites' }" @click="setView('favorites')">Избранное</button>
      <button class="tab-btn" :class="{ active: currentView === 'cart' }" @click="setView('cart')">
        Корзина
        <span v-if="cartTotalItems" class="tab-badge">{{ cartTotalItems }}</span>
      </button>
      <button class="tab-btn tab-profile" :class="{ active: currentView === 'profile' }" @click="setView('profile')">
        <img v-if="resolvedProfileAvatar" :src="resolvedProfileAvatar" alt="profile" class="tab-avatar">
        <span v-else>Профиль</span>
      </button>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue'
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
    const showSearch = ref(false)
    const searchInput = ref(null)
    const ownerProfile = ref(null)
    const currentView = ref('catalog') // 'catalog', 'favorites', 'cart', 'profile', 'checkout'
    const descriptionExpandedState = ref({}) // To track expanded descriptions

    const normalizeShopId = (id) => String(id ?? '').trim()
    const normalizedShopId = normalizeShopId(shopId)
    const cartStorageKey = computed(() => `webapp_cart_shop_${normalizedShopId}`)
    const favoritesStorageKey = computed(() => `webapp_favorites_shop_${normalizedShopId}`)

    const readCartFromStorage = () => {
      const key = cartStorageKey.value
      const raw = localStorage.getItem(key)
      if (!raw) return {}
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

    const readFavoritesFromStorage = () => {
      const key = favoritesStorageKey.value
      const raw = localStorage.getItem(key)
      if (!raw) return {}
      try {
        const parsed = JSON.parse(raw)
        return parsed && typeof parsed === 'object' ? parsed : {}
      } catch {
        return {}
      }
    }

    const persistFavorites = () => {
      const key = favoritesStorageKey.value
      localStorage.setItem(key, JSON.stringify(favorites.value))
    }

    const cart = ref({})
    const favorites = ref({})
    const currentSlideIndex = ref(0)
    let sliderTimer = null

    const orderForm = reactive({
      name: '',
      phone: ''
    })
    const orderLoading = ref(false)
    const orderSuccess = ref(false)
    const orderNumber = ref(null)
    const orderError = ref('')
    const orderTotalForManager = ref(0)

    const cartItems = computed(() => Object.values(cart.value))
    const favoriteItems = computed(() => Object.values(favorites.value))

    const sliderProducts = computed(() => {
      const preferred = products.value.filter((item) => item.show_in_slider)
      return preferred.length > 0 ? preferred : products.value.slice(0, 5)
    })

    const showBottomNav = computed(() => !loading.value && !error.value)
    const telegramUser = computed(() => window.Telegram?.WebApp?.initDataUnsafe?.user || {})

    const telegramDisplayName = computed(() => {
      if (ownerTelegramUsername.value) return ownerTelegramUsername.value
      if (telegramUser.value?.username) return `@${telegramUser.value.username}`
      const ownerName = ownerProfile.value?.name
      if (ownerName) return ownerName
      const firstName = telegramUser.value?.first_name || ''
      const lastName = telegramUser.value?.last_name || ''
      return `${firstName} ${lastName}`.trim() || 'Пользователь'
    })

    const resolvedProfileAvatar = computed(() => {
      return ownerProfile.value?.avatar_url || ownerProfile.value?.telegram_avatar_url || telegramUser.value?.photo_url || ''
    })

    const ownerTelegramUsername = computed(() => {
      const raw = String(ownerProfile.value?.telegram_username || '').trim()
      if (!raw) return ''
      return raw.startsWith('@') ? raw : `@${raw}`
    })

    const ownerAccountName = computed(() => {
      const ownerName = String(ownerProfile.value?.name || '').trim()
      if (!ownerName) return ''
      if (ownerTelegramUsername.value && ownerName === ownerTelegramUsername.value) return ''
      return ownerName
    })

    const cartTotalItems = computed(() => cartItems.value.reduce((sum, item) => sum + item.quantity, 0))
    const cartSubtotal = computed(() => {
      return cartItems.value.reduce((sum, item) => {
        const price = parseFloat(item.price) || 0
        return sum + (price * item.quantity)
      }, 0)
    })
    const cartTotal = computed(() => cartSubtotal.value + (parseFloat(shop.value?.delivery_price) || 0))

    const resolveCategoryName = (category) => {
      if (!category) return null
      if (typeof category === 'string') return category
      if (typeof category === 'object' && category.name) return category.name
      return null
    }

    // Methods for description expansion
    const isDescriptionExpanded = (productId) => descriptionExpandedState.value[productId] === true
    const toggleDescription = (productId) => {
      descriptionExpandedState.value[productId] = !isDescriptionExpanded(productId)
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
      favorites.value = readFavoritesFromStorage()
      try {
        await Promise.all([loadShop(), loadProducts()])
      } finally {
        loading.value = false
      }
    })

    onUnmounted(() => {
      if (sliderTimer) clearInterval(sliderTimer)
    })

    const loadShop = async () => {
      try {
        const response = await axios.get(`/api/shops/${shopId}/public`)
        shop.value = response.data.shop
        ownerProfile.value = response.data.shop?.owner_profile || null
      } catch (err) {
        error.value = 'Ошибка загрузки магазина'
      }
    }

    const loadProducts = async () => {
      try {
        const response = await axios.get(`/api/shops/${shopId}/products/public`, {
          params: { search: searchQuery.value, category: selectedCategory.value }
        })
        products.value = (response.data.products || []).map(product => ({
          ...product,
          category_name: resolveCategoryName(product.category)
        }))
        categories.value = response.data.categories || []
        currentSlideIndex.value = 0
        if (sliderTimer) clearInterval(sliderTimer)
        if (sliderProducts.value.length > 1) {
          sliderTimer = setInterval(() => {
            currentSlideIndex.value = (currentSlideIndex.value + 1) % sliderProducts.value.length
          }, 4000)
        }
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
        cart.value[product.id] = { id: product.id, name: product.name, price: product.price, quantity: 1 }
      }
      persistCart()
      if (window.Telegram?.WebApp) window.Telegram.WebApp.showAlert(`"${product.name}" в корзине`)
    }

    const updateQuantity = (productId, delta) => {
      if (cart.value[productId]) {
        const newQty = cart.value[productId].quantity + delta
        if (newQty <= 0) delete cart.value[productId]
        else cart.value[productId].quantity = newQty
        persistCart()
      }
    }

    const removeFromCart = (productId) => {
      delete cart.value[productId]
      persistCart()
    }

    const setView = (view) => { currentView.value = view }
    const selectCategory = (categoryId) => { selectedCategory.value = categoryId; loadProducts() }
    const goToSlide = (idx) => { currentSlideIndex.value = idx }
    const isFavorite = (productId) => Boolean(favorites.value[productId])

    const toggleFavorite = (product) => {
      if (isFavorite(product.id)) delete favorites.value[product.id]
      else favorites.value[product.id] = { ...product }
      persistFavorites()
    }

    const toggleSearch = async () => {
      showSearch.value = !showSearch.value
      if (showSearch.value) { await nextTick(); searchInput.value?.focus() }
    }

    const goToCheckout = () => {
      if (cartItems.value.length === 0) {
        if (window.Telegram?.WebApp) window.Telegram.WebApp.showAlert('Корзина пуста')
        return
      }
      currentView.value = 'checkout'
    }

    const managerUsername = computed(() => String(shop.value?.manager_telegram_username || '').trim().replace(/^@+/, ''))
    const hasManagerContact = computed(() => Boolean(managerUsername.value))
    const managerBaseUrl = computed(() => hasManagerContact.value ? `https://t.me/${managerUsername.value}` : '')

    const openManagerContact = () => {
      if (!managerBaseUrl.value) return
      const text = encodeURIComponent(`Здравствуйте! Заказ #${orderNumber.value}
Магазин: ${shop.value?.name}
Сумма: ${orderTotalForManager.value} ₽`)
      const url = `${managerBaseUrl.value}?text=${text}`
      if (window.Telegram?.WebApp?.openLink) window.Telegram.WebApp.openLink(url)
      else window.open(url, '_blank')
    }

    const openSupportChat = () => {
      const url = managerBaseUrl.value || 'https://t.me/tgshopCLO_bot'
      if (window.Telegram?.WebApp?.openLink) window.Telegram.WebApp.openLink(url)
      else window.open(url, '_blank')
    }

    const submitOrder = async () => {
      if (!orderForm.name || !orderForm.phone) return
      orderLoading.value = true
      try {
        const payload = {
          shop_id: parseInt(shopId),
          customer_name: orderForm.name,
          phone: orderForm.phone,
          items: cartItems.value.map(item => ({ id: item.id, quantity: item.quantity })),
          create_payment: false
        }
        const response = await axios.post('/api/orders', payload, {
          headers: { 'X-Telegram-Init-Data': window.Telegram?.WebApp?.initData || '' }
        })
        if (response.data.success) {
          orderNumber.value = response.data.order.id
          orderSuccess.value = true
          orderTotalForManager.value = response.data.amounts.total
          cart.value = {}
          localStorage.removeItem(cartStorageKey.value)
        }
      } catch (error) {
        orderError.value = error?.response?.data?.message || 'Ошибка'
      } finally {
        orderLoading.value = false
      }
    }

    const resetOrder = () => {
      orderSuccess.value = false; currentView.value = 'catalog'
    }

    return {
      loading, error, shop, products, categories, searchQuery, selectedCategory, showSearch, 
      searchInput, currentView, sliderProducts, currentSlideIndex, favoriteItems, showBottomNav,
      telegramUser, telegramDisplayName, resolvedProfileAvatar, ownerTelegramUsername, ownerAccountName,
      cartItems, cartTotalItems, cartSubtotal, cartTotal, orderForm, orderLoading, orderSuccess, 
      orderNumber, orderError, hasManagerContact, loadProducts, debouncedSearch, addToCart, 
      updateQuantity, removeFromCart, setView, selectCategory, goToSlide, toggleSearch, 
      toggleFavorite, isFavorite, openSupportChat, goToCheckout, openManagerContact, 
      submitOrder, resetOrder,
      // Description expansion
      descriptionExpandedState, isDescriptionExpanded, toggleDescription
    }
  }
}
</script>

<style scoped>
:root {
  --bg-0: #070b18; --bg-1: #0d1326; --ink-0: #eff6ff; --ink-1: #a3b5db;
  --line: rgba(138, 178, 255, 0.24); --surface: rgba(14, 24, 46, 0.78);
  --surface-soft: rgba(17, 29, 55, 0.65); --accent: #38e8ff; --accent-2: #41ffbf;
}

.webapp-container {
  position: relative; min-height: 100vh; overflow-x: hidden;
  background: linear-gradient(165deg, var(--bg-0) 0%, var(--bg-1) 100%);
  font-family: sans-serif; color: var(--ink-0);
}

.bg-grid {
  position: absolute; inset: 0; opacity: 0.1; pointer-events: none;
  background-image: linear-gradient(var(--line) 1px, transparent 1px), linear-gradient(90deg, var(--line) 1px, transparent 1px);
  background-size: 30px 30px;
}

.content {
  position: relative; z-index: 1; padding: 0.5rem; padding-bottom: 80px;
}

.panel-shell {
  background: var(--surface-soft); border: 1px solid var(--line);
  border-radius: 20px; margin: 6px; backdrop-filter: blur(16px);
}

.shop-header {
  display: flex; flex-direction: column; align-items: center; text-align: center;
  margin-bottom: 1.2rem; gap: 0.8rem; padding-top: 0.5rem;
}

.kicker { font-size: 0.7rem; color: var(--accent); letter-spacing: 0.1em; margin: 0; }
h1 { font-size: 1.5rem; margin: 0.2rem 0; }

.shop-actions { display: flex; justify-content: center; gap: 1rem; width: 100%; }

.cart-icon {
  position: relative; font-size: 1.2rem; padding: 8px; border-radius: 12px;
  background: rgba(65, 255, 191, 0.1); border: 1px solid var(--accent-2);
}

.cart-count {
  position: absolute; top: -5px; right: -5px; background: var(--accent-2);
  color: #000; font-size: 0.7rem; font-weight: bold; padding: 2px 6px; border-radius: 10px;
}

.category-chips {
  display: flex; gap: 0.5rem; overflow-x: auto; padding: 0.5rem 0; margin-bottom: 1rem;
}
.category-chips::-webkit-scrollbar { display: none; }
.chip {
  padding: 0.5rem 1rem; border-radius: 20px; border: 1px solid var(--line);
  background: rgba(255,255,255,0.05); color: #fff; white-space: nowrap;
}
.chip.active { background: var(--accent); color: #000; border-color: var(--accent); }

.products-grid {
  display: grid; grid-template-columns: 1fr; gap: 1rem; justify-items: center;
}

.product-card {
  display: flex; flex-direction: column; align-items: center; text-align: center;
  width: 100%; max-width: 320px; padding: 1.2rem; border-radius: 20px;
  background: var(--surface); border: 1px solid var(--line); box-sizing: border-box;
}

.product-image {
  width: 160px; height: 160px; border-radius: 15px; overflow: hidden; margin-bottom: 1rem;
}
.product-image img { width: 100%; height: 100%; object-fit: cover; }

.product-info h3 { font-size: 1.1rem; margin: 0 0 0.5rem; }
.price { font-size: 1.2rem; font-weight: bold; color: var(--accent-2); margin-bottom: 0.5rem; }

.product-description-wrapper {
  margin-bottom: 1rem;
  width: 100%;
  text-align: left;
  font-size: 0.9rem;
  color: var(--ink-1);
  line-height: 1.4;
}

.description {
  overflow: hidden;
  transition: max-height 0.3s ease-out;
  max-height: 0; /* Collapsed by default */
  margin-bottom: 0.5rem; /* Space for toggle button */
}
.description.expanded {
  max-height: 200px; /* Or a more dynamic height if needed */
}

.description-toggle-btn {
  background: none;
  border: none;
  color: var(--accent);
  cursor: pointer;
  font-size: 0.8rem;
  padding: 0.2rem 0;
  display: flex;
  align-items: center;
  gap: 0.3rem;
  margin-top: 0.5rem;
}
.description-toggle-btn::after {
  content: '▼'; /* Down arrow */
  display: inline-block;
  transition: transform 0.3s ease;
}
.description-toggle-btn.expanded::after {
  transform: rotate(180deg);
}

.category { color: var(--ink-1); font-size: 0.8rem; margin-bottom: 1rem; }

.add-to-cart {
  width: 100%; padding: 0.8rem; border-radius: 12px; border: none;
  background: linear-gradient(90deg, var(--accent), var(--accent-2));
  color: #000; font-weight: bold; cursor: pointer;
}

.bottom-nav {
  position: fixed; bottom: 0; left: 0; right: 0; display: grid; grid-template-columns: repeat(4, 1fr);
  background: rgba(10, 15, 30, 0.95); border-top: 1px solid var(--line); padding: 10px; backdrop-filter: blur(10px);
}
.tab-btn { background: none; border: none; color: #888; font-size: 0.8rem; padding: 5px; }
.tab-btn.active { color: var(--accent); }

@media (min-width: 600px) {
  .products-grid { grid-template-columns: 1fr 1fr; }
}
</style>
