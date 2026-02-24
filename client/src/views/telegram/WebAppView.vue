<template>
  <div class="webapp-container">
    <!-- Главная страница каталога -->
    <div v-if="currentView === 'catalog'" class="content">
      <!-- Шапка магазина -->
      <div class="shop-header">
        <h1>{{ shop.name }}</h1>
        <div class="cart-icon" @click="goToCart">
          🛒 <span class="cart-count" v-if="cartTotalItems">{{ cartTotalItems }}</span>
        </div>
      </div>
      
      <p class="delivery">
        Доставка: {{ shop.delivery_name }} - {{ shop.delivery_price }} ₽
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
          <option value="">Все категории</option>
          <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
        </select>
      </div>

      <!-- Каталог товаров -->
      <div class="products-grid" v-if="products.length">
        <div v-for="product in products" :key="product.id" class="product-card">
          <div class="product-image" v-if="product.image">
            <img :src="product.image" :alt="product.name">
          </div>
          <div class="product-info">
            <h3>{{ product.name }}</h3>
            <p class="price">{{ product.price }} ₽</p>
            <p class="description">{{ product.description }}</p>
            <p class="category">{{ product.category }}</p>
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
    <div v-else-if="currentView === 'cart'" class="content cart-view">
      <div class="cart-header">
        <button class="back-btn" @click="currentView = 'catalog'">← Назад</button>
        <h2>Корзина</h2>
        <div class="cart-icon" @click="goToCart">
          🛒 <span class="cart-count" v-if="cartTotalItems">{{ cartTotalItems }}</span>
        </div>
      </div>

      <div v-if="cartItems.length" class="cart-items">
        <div v-for="item in cartItems" :key="item.id" class="cart-item">
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
    <div v-else-if="currentView === 'checkout'" class="content checkout-view">
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
            <span>Итого к оплате:</span>
            <span>{{ cartTotal }} ₽</span>
          </div>
        </div>

        <button type="submit" class="submit-order" :disabled="orderLoading">
          {{ orderLoading ? 'Обработка...' : 'Оплатить ' + cartTotal + ' ₽' }}
        </button>
      </form>

      <!-- Сообщение об успехе -->
      <div v-if="orderSuccess" class="success-message">
        <h3>✅ Заказ оформлен!</h3>
        <p>Номер заказа: {{ orderNumber }}</p>
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
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import debounce from 'lodash/debounce'

export default {
  name: 'WebAppView',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const shopId = route.query.shop
    
    const loading = ref(true)
    const error = ref(null)
    const shop = ref(null)
    const products = ref([])
    const categories = ref([])
    const searchQuery = ref('')
    const selectedCategory = ref('')
    const currentView = ref('catalog') // 'catalog', 'cart', 'checkout'
    
    // Корзина в localStorage
    const cart = ref(JSON.parse(localStorage.getItem('cart') || '{}'))

    // Форма заказа
    const orderForm = reactive({
      name: '',
      phone: ''
    })
    const orderLoading = ref(false)
    const orderSuccess = ref(false)
    const orderNumber = ref(null)

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
      const total = subtotal + delivery
      console.log('cartTotal calculation:', { subtotal, delivery, total })
      return total
    })

    onMounted(() => {
      if (window.Telegram?.WebApp) {
        window.Telegram.WebApp.ready()
        window.Telegram.WebApp.expand()
      }

      if (!shopId) {
        error.value = 'Не указан ID магазина'
        loading.value = false
        return
      }

      loadShop()
      loadProducts()
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
        products.value = response.data.products
        
        // Собираем уникальные категории
        const cats = new Set(products.value.map(p => p.category).filter(Boolean))
        categories.value = Array.from(cats)
      } catch (err) {
        console.error('Ошибка загрузки товаров:', err)
      } finally {
        loading.value = false
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
      
      localStorage.setItem('cart', JSON.stringify(cart.value))
      
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
        localStorage.setItem('cart', JSON.stringify(cart.value))
      }
    }

    const removeFromCart = (productId) => {
      delete cart.value[productId]
      localStorage.setItem('cart', JSON.stringify(cart.value))
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

    const submitOrder = async () => {
      if (!orderForm.name || !orderForm.phone) {
        if (window.Telegram?.WebApp) {
          window.Telegram.WebApp.showAlert('Заполните все поля')
        }
        return
      }

      orderLoading.value = true

      try {
        const response = await axios.post('/api/orders', {
          shop_id: parseInt(shopId),
          customer_name: orderForm.name,
          phone: orderForm.phone,
          items: cartItems.value.map(item => ({
            id: item.id,
            name: item.name,
            price: item.price,
            quantity: item.quantity
          }))
        })

        if (response.data.success) {
          orderNumber.value = response.data.order.id
          orderSuccess.value = true
          
          // Очищаем корзину
          cart.value = {}
          localStorage.removeItem('cart')
          
          // Здесь будет интеграция с ЮKassa
          if (response.data.payment_url) {
            // Открываем ссылку на оплату
            window.open(response.data.payment_url, '_blank')
          }
        }
      } catch (error) {
        console.error('Ошибка при создании заказа:', error)
        if (window.Telegram?.WebApp) {
          window.Telegram.WebApp.showAlert('Ошибка при создании заказа')
        }
      } finally {
        orderLoading.value = false
      }
    }

    const resetOrder = () => {
      orderSuccess.value = false
      orderNumber.value = null
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
      loadProducts,
      debouncedSearch,
      addToCart,
      updateQuantity,
      removeFromCart,
      goToCart,
      goToCheckout,
      submitOrder,
      resetOrder
    }
  }
}
</script>

<style scoped>
.webapp-container {
  min-height: 100vh;
  background: #f5f5f5;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.loading, .error {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  text-align: center;
  padding: 2rem;
}

.error {
  color: #f44336;
}

.content {
  padding: 1rem;
  padding-bottom: 80px;
}

.shop-header, .cart-header, .checkout-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  position: relative;
}

.cart-header, .checkout-header {
  justify-content: flex-start;
  gap: 1rem;
}

.back-btn {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  color: #2196f3;
  padding: 0.5rem;
}

h1, h2 {
  margin: 0;
  color: #333;
  font-size: 1.5rem;
  flex: 1;
}

.cart-icon {
  position: relative;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.5rem;
}

.cart-count {
  position: absolute;
  top: 0;
  right: 0;
  background: #4CAF50;
  color: white;
  font-size: 0.8rem;
  font-weight: bold;
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 2px;
}

.delivery {
  color: #666;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #eee;
}

.filters {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.filters input, .filters select {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.9rem;
}

.filters input {
  flex: 2;
}

.filters select {
  flex: 1;
}

.products-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

.product-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  display: flex;
}

.product-image {
  width: 100px;
  height: 100px;
  flex-shrink: 0;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-info {
  padding: 1rem;
  flex: 1;
}

.product-info h3 {
  margin: 0 0 0.25rem;
  font-size: 1rem;
}

.price {
  font-weight: bold;
  color: #4CAF50;
  margin: 0.25rem 0;
}

.description {
  color: #666;
  font-size: 0.9rem;
  margin: 0.25rem 0;
}

.category {
  color: #888;
  font-size: 0.8rem;
  margin: 0.25rem 0;
}

.add-to-cart {
  background: #4CAF50;
  color: white;
  border: none;
  padding: 0.5rem;
  border-radius: 4px;
  width: 100%;
  cursor: pointer;
  font-size: 0.9rem;
  margin-top: 0.5rem;
}

.add-to-cart:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #999;
}

/* Стили для корзины */
.cart-items {
  margin-top: 1rem;
}

.cart-item {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.item-info {
  margin-bottom: 0.5rem;
}

.item-info h3 {
  margin: 0 0 0.25rem;
  font-size: 1rem;
}

.item-price {
  color: #4CAF50;
  font-weight: bold;
  margin: 0;
}

.item-quantity {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.qty-btn {
  background: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 4px;
  width: 32px;
  height: 32px;
  font-size: 1.2rem;
  cursor: pointer;
}

.qty-btn:hover {
  background: #e0e0e0;
}

.qty {
  min-width: 40px;
  text-align: center;
  font-weight: bold;
}

.remove-btn {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  margin-left: auto;
  opacity: 0.6;
}

.remove-btn:hover {
  opacity: 1;
}

.cart-total {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
}

.total-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  color: #666;
}

.grand-total {
  font-weight: bold;
  color: #333;
  font-size: 1.2rem;
  border-top: 1px solid #eee;
  padding-top: 0.5rem;
  margin-top: 0.5rem;
}

.checkout-btn {
  background: #4CAF50;
  color: white;
  border: none;
  padding: 1rem;
  border-radius: 8px;
  font-size: 1.1rem;
  font-weight: bold;
  width: 100%;
  margin-top: 1rem;
  cursor: pointer;
}

/* Стили для оформления заказа */
.checkout-form {
  margin-top: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #333;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.order-summary {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  margin: 1.5rem 0;
}

.order-summary h3 {
  margin: 0 0 1rem;
  color: #333;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  color: #666;
}

.summary-total {
  display: flex;
  justify-content: space-between;
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid #eee;
}

.grand-total {
  font-weight: bold;
  color: #333;
  font-size: 1.1rem;
}

.submit-order {
  background: #4CAF50;
  color: white;
  border: none;
  padding: 1rem;
  border-radius: 8px;
  font-size: 1.1rem;
  font-weight: bold;
  width: 100%;
  cursor: pointer;
}

.submit-order:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.success-message {
  text-align: center;
  padding: 2rem;
}

.success-message h3 {
  color: #4CAF50;
  margin-bottom: 1rem;
}

.continue-shopping {
  background: #2196f3;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  margin-top: 1rem;
}

@media (min-width: 600px) {
  .products-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>