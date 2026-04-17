<template>
  <div class="webapp-container">
    <div class="bg-grid"></div>

    <div v-if="currentView === 'catalog'" class="content panel-shell">
      <header class="shop-header-row">
        <div class="shop-title-line">
          <span class="brand-kicker">t-go shop</span>
          <span class="shop-name">{{ shop?.name || "Магазин" }}</span>
        </div>
        <button class="search-toggle" @click="toggleSearch" aria-label="Открыть поиск">🔍</button>
      </header>

      <div class="category-select-wrap">
        <select v-model="selectedCategory" @change="onCategoryChange" class="category-select">
          <option value="">Весь магазин</option>
          <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
            {{ cat.name }}
          </option>
        </select>
      </div>

      <div v-if="sliderProducts.length" class="hero-slider">
        <div class="hero-slide" @click="addToCart(sliderProducts[currentSlideIndex])">
          <img
            v-if="sliderProducts[currentSlideIndex]?.image"
            :src="sliderProducts[currentSlideIndex]?.image"
            :alt="sliderProducts[currentSlideIndex]?.name"
          />
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

      <div class="products-list" v-if="visibleProducts.length">
        <article
          v-for="(product, index) in visibleProducts"
          :key="product.id"
          class="product-card"
          :style="{ '--delay': `${index * 50}ms` }"
        >
          <button class="fav-btn" :class="{ active: isFavorite(product.id) }" @click="toggleFavorite(product)">
            {{ isFavorite(product.id) ? "♥" : "♡" }}
          </button>

          <div class="product-image" v-if="product.image">
            <img :src="product.image" :alt="product.name" />
          </div>

          <div class="product-info">
            <h3>{{ product.name }}</h3>
            <p class="price">{{ product.price }} ₽</p>

            <div v-if="product.description" class="product-description-wrapper">
              <p class="description" :class="{ expanded: isDescriptionExpanded(product.id) }">
                {{ product.description }}
              </p>
              <button
                @click="toggleDescription(product.id)"
                class="description-toggle-btn"
                :class="{ expanded: isDescriptionExpanded(product.id) }"
              >
                {{ isDescriptionExpanded(product.id) ? "Скрыть" : "Развернуть" }}
              </button>
            </div>

            <p class="category">{{ product.category_name || "Без категории" }}</p>
            <button class="add-to-cart" @click="addToCart(product)" :disabled="!product.in_stock">
              {{ product.in_stock ? "В корзину" : "Нет в наличии" }}
            </button>
          </div>
        </article>

        <button v-if="hasMoreProducts" class="show-more" @click="showMoreProducts">Показать ещё</button>
      </div>

      <div v-else-if="!loading" class="empty-state">
        <p>Товаров не найдено</p>
      </div>
    </div>

    <div v-else-if="currentView === 'favorites'" class="content panel-shell">
      <div class="cart-header">
        <h2>Избранное</h2>
      </div>
      <div v-if="favoriteItems.length" class="products-list">
        <article
          v-for="(product, index) in favoriteItems"
          :key="`fav-${product.id}`"
          class="product-card"
          :style="{ '--delay': `${index * 50}ms` }"
        >
          <button class="fav-btn active" @click="toggleFavorite(product)">♥</button>
          <div class="product-image" v-if="product.image">
            <img :src="product.image" :alt="product.name" />
          </div>
          <div class="product-info">
            <h3>{{ product.name }}</h3>
            <p class="price">{{ product.price }} ₽</p>
            <div v-if="product.description" class="product-description-wrapper">
              <p class="description" :class="{ expanded: isDescriptionExpanded(product.id) }">
                {{ product.description }}
              </p>
              <button
                @click="toggleDescription(product.id)"
                class="description-toggle-btn"
                :class="{ expanded: isDescriptionExpanded(product.id) }"
              >
                {{ isDescriptionExpanded(product.id) ? "Скрыть" : "Развернуть" }}
              </button>
            </div>
            <button class="add-to-cart" @click="addToCart(product)">В корзину</button>
          </div>
        </article>
      </div>
      <div v-else class="empty-state">
        <p>В избранном пока пусто</p>
      </div>
    </div>

    <div v-else-if="currentView === 'cart'" class="content panel-shell cart-view">
      <div class="cart-header">
        <button class="back-btn" @click="currentView = 'catalog'">← Назад</button>
        <h2>Корзина</h2>
      </div>

      <div v-if="cartItems.length" class="cart-items">
        <div v-for="(item, index) in cartItems" :key="item.id" class="cart-item" :style="{ '--delay': `${index * 50}ms` }">
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
            <span>{{ shop?.delivery_price || 0 }} ₽</span>
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

    <div v-else-if="currentView === 'profile'" class="content panel-shell">
      <div class="cart-header">
        <h2>Профиль</h2>
      </div>

      <div class="profile-card">
        <img v-if="resolvedProfileAvatar" :src="resolvedProfileAvatar" alt="avatar" class="profile-avatar" />
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
    <div v-else-if="currentView === 'checkout'" class="content panel-shell checkout-view">
      <div class="checkout-header">
        <button class="back-btn" @click="currentView = 'cart'">← Назад</button>
        <h2>Оформление заказа</h2>
      </div>

      <form @submit.prevent="submitOrder" class="checkout-form">
        <div class="form-group">
          <label>Ваше имя *</label>
          <input type="text" v-model="orderForm.name" required placeholder="Иван Петров" />
        </div>

        <div class="form-group">
          <label>Телефон *</label>
          <input type="tel" v-model="orderForm.phone" required placeholder="+7 999 123-45-67" />
        </div>

        <div class="form-group">
          <label>Способ доставки</label>
          <input
            type="text"
            :value="`${shop?.delivery_name || 'Доставка'} (${Number(shop?.delivery_price || 0)} ₽)`"
            readonly
          />
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
            <span>{{ shop?.delivery_price || 0 }} ₽</span>
          </div>
          <div class="summary-total grand-total">
            <span>Итого заказа:</span>
            <span>{{ cartTotal }} ₽</span>
          </div>
        </div>

        <button type="submit" class="submit-order" :disabled="orderLoading">
          {{ orderLoading ? "Сохраняем..." : "Подтвердить заказ" }}
        </button>
      </form>

      <p v-if="orderError" class="order-error">{{ orderError }}</p>

      <div v-if="orderSuccess" class="success-message">
        <h3>✅ Заказ оформлен!</h3>
        <p>Заказ №{{ orderNumber }} создан.</p>
        <p>Менеджер свяжется с вами для подтверждения и оплаты заказа.</p>
        <button v-if="hasManagerContact" class="continue-shopping" @click="openManagerContact">Написать менеджеру</button>
        <button class="continue-shopping" @click="resetOrder">Вернуться в магазин</button>
      </div>
    </div>

    <div v-if="loading" class="loading">Загрузка магазина...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-if="showBottomNav" class="bottom-nav">
      <button class="tab-btn" :class="{ active: currentView === 'catalog' }" @click="setView('catalog')">Главная</button>
      <button class="tab-btn" :class="{ active: currentView === 'favorites' }" @click="setView('favorites')">Избранное</button>
      <button class="tab-btn" :class="{ active: currentView === 'cart' }" @click="setView('cart')">
        Корзина
        <span v-if="cartTotalItems" class="tab-badge">{{ cartTotalItems }}</span>
      </button>
      <button class="tab-btn tab-profile" :class="{ active: currentView === 'profile' }" @click="setView('profile')">
        <img v-if="resolvedProfileAvatar" :src="resolvedProfileAvatar" alt="profile" class="tab-avatar" />
        <span v-else>Профиль</span>
      </button>
    </div>

    <div v-if="showSearch" class="search-overlay" @click.self="closeSearch">
      <div class="search-overlay-panel">
        <div class="search-overlay-head">
          <input ref="searchInput" type="text" v-model="searchQuery" placeholder="Поиск товаров..." class="search-input" />
          <button class="search-close" @click="closeSearch">✕</button>
        </div>

        <div class="search-results" v-if="searchResults.length">
          <div v-for="item in searchResults" :key="`search-${item.id}`" class="search-item">
            <div class="search-item-main">
              <p class="search-item-name">{{ item.name }}</p>
              <p class="search-item-meta">{{ item.price }} ₽ · {{ item.category_name || "Без категории" }}</p>
            </div>
            <button class="search-add" @click="addToCart(item)">В корзину</button>
          </div>
        </div>

        <div class="search-empty" v-else>Ничего не найдено</div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from "vue";
import { useRoute } from "vue-router";
import axios from "axios";

const PRODUCTS_STEP = 10;

export default {
  name: "WebAppView",
  setup() {
    const route = useRoute();
    const shopId = route.query.shop || route.query.shopId;

    const loading = ref(true);
    const error = ref(null);
    const shop = ref(null);
    const products = ref([]);
    const categories = ref([]);
    const selectedCategory = ref("");
    const searchQuery = ref("");
    const showSearch = ref(false);
    const searchInput = ref(null);
    const visibleCount = ref(PRODUCTS_STEP);
    const ownerProfile = ref(null);
    const currentView = ref("catalog");
    const descriptionExpandedState = ref({});

    const normalizeShopId = (id) => String(id ?? "").trim();
    const normalizedShopId = normalizeShopId(shopId);
    const cartStorageKey = computed(() => `webapp_cart_shop_${normalizedShopId}`);
    const favoritesStorageKey = computed(() => `webapp_favorites_shop_${normalizedShopId}`);

    const readCartFromStorage = () => {
      const raw = localStorage.getItem(cartStorageKey.value);
      if (!raw) return {};
      try {
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === "object" ? parsed : {};
      } catch {
        return {};
      }
    };

    const persistCart = () => {
      localStorage.setItem(cartStorageKey.value, JSON.stringify(cart.value));
    };

    const readFavoritesFromStorage = () => {
      const raw = localStorage.getItem(favoritesStorageKey.value);
      if (!raw) return {};
      try {
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === "object" ? parsed : {};
      } catch {
        return {};
      }
    };

    const persistFavorites = () => {
      localStorage.setItem(favoritesStorageKey.value, JSON.stringify(favorites.value));
    };

    const cart = ref({});
    const favorites = ref({});
    const currentSlideIndex = ref(0);
    let sliderTimer = null;

    const orderForm = reactive({ name: "", phone: "" });
    const orderLoading = ref(false);
    const orderSuccess = ref(false);
    const orderNumber = ref(null);
    const orderError = ref("");
    const orderTotalForManager = ref(0);

    const cartItems = computed(() => Object.values(cart.value));
    const favoriteItems = computed(() => Object.values(favorites.value));
    const sliderProducts = computed(() => {
      const preferred = products.value.filter((item) => item.show_in_slider);
      return preferred.length > 0 ? preferred : products.value.slice(0, 5);
    });

    const visibleProducts = computed(() => products.value.slice(0, visibleCount.value));
    const hasMoreProducts = computed(() => products.value.length > visibleCount.value);

    const searchResults = computed(() => {
      const q = searchQuery.value.trim().toLowerCase();
      if (!q) return products.value.slice(0, 30);
      return products.value.filter((item) => {
        const haystack = `${item.name || ""} ${item.description || ""} ${item.category_name || ""}`.toLowerCase();
        return haystack.includes(q);
      });
    });

    const showBottomNav = computed(() => !loading.value && !error.value);
    const telegramUser = computed(() => window.Telegram?.WebApp?.initDataUnsafe?.user || {});

    const telegramDisplayName = computed(() => {
      if (ownerTelegramUsername.value) return ownerTelegramUsername.value;
      if (telegramUser.value?.username) return `@${telegramUser.value.username}`;
      const ownerName = ownerProfile.value?.name;
      if (ownerName) return ownerName;
      const firstName = telegramUser.value?.first_name || "";
      const lastName = telegramUser.value?.last_name || "";
      return `${firstName} ${lastName}`.trim() || "Пользователь";
    });

    const resolvedProfileAvatar = computed(() => ownerProfile.value?.avatar_url || ownerProfile.value?.telegram_avatar_url || telegramUser.value?.photo_url || "");

    const ownerTelegramUsername = computed(() => {
      const raw = String(ownerProfile.value?.telegram_username || "").trim();
      if (!raw) return "";
      return raw.startsWith("@") ? raw : `@${raw}`;
    });

    const ownerAccountName = computed(() => {
      const ownerName = String(ownerProfile.value?.name || "").trim();
      if (!ownerName) return "";
      if (ownerTelegramUsername.value && ownerName === ownerTelegramUsername.value) return "";
      return ownerName;
    });
    const cartTotalItems = computed(() => cartItems.value.reduce((sum, item) => sum + item.quantity, 0));
    const cartSubtotal = computed(() => cartItems.value.reduce((sum, item) => sum + ((parseFloat(item.price) || 0) * item.quantity), 0));
    const cartTotal = computed(() => cartSubtotal.value + (parseFloat(shop.value?.delivery_price) || 0));

    const managerUsername = computed(() => String(shop.value?.manager_telegram_username || "").trim().replace(/^@+/, ""));
    const hasManagerContact = computed(() => Boolean(managerUsername.value));
    const managerBaseUrl = computed(() => (hasManagerContact.value ? `https://t.me/${managerUsername.value}` : ""));

    const resolveCategoryName = (category) => {
      if (!category) return null;
      if (typeof category === "string") return category;
      if (typeof category === "object" && category.name) return category.name;
      return null;
    };

    const isDescriptionExpanded = (productId) => descriptionExpandedState.value[productId] === true;
    const toggleDescription = (productId) => {
      descriptionExpandedState.value[productId] = !isDescriptionExpanded(productId);
    };

    const clearSlider = () => {
      if (!sliderTimer) return;
      clearInterval(sliderTimer);
      sliderTimer = null;
    };

    const onCategoryChange = async () => {
      await loadProducts();
      visibleCount.value = PRODUCTS_STEP;
    };

    const showMoreProducts = () => {
      visibleCount.value += PRODUCTS_STEP;
    };

    const openSearch = async () => {
      showSearch.value = true;
      await nextTick();
      searchInput.value?.focus();
    };

    const closeSearch = () => {
      showSearch.value = false;
      searchQuery.value = "";
    };

    const toggleSearch = () => {
      showSearch.value ? closeSearch() : openSearch();
    };

    onMounted(async () => {
      if (window.Telegram?.WebApp) {
        window.Telegram.WebApp.ready();
        window.Telegram.WebApp.expand();
      }
      if (!shopId) {
        error.value = "Не указан ID магазина";
        loading.value = false;
        return;
      }

      cart.value = readCartFromStorage();
      favorites.value = readFavoritesFromStorage();

      try {
        await Promise.all([loadShop(), loadProducts()]);
      } finally {
        loading.value = false;
      }
    });

    onUnmounted(() => {
      clearSlider();
    });

    watch(currentView, (value) => {
      if (value !== "catalog") closeSearch();
    });

    const loadShop = async () => {
      try {
        const response = await axios.get(`/api/shops/${shopId}/public`);
        shop.value = response.data.shop;
        ownerProfile.value = response.data.shop?.owner_profile || null;
      } catch {
        error.value = "Ошибка загрузки магазина";
      }
    };

    const loadProducts = async () => {
      try {
        const response = await axios.get(`/api/shops/${shopId}/products/public`, {
          params: { category: selectedCategory.value },
        });

        products.value = (response.data.products || []).map((product) => ({
          ...product,
          category_name: resolveCategoryName(product.category),
        }));

        categories.value = response.data.categories || [];
        currentSlideIndex.value = 0;
        clearSlider();

        if (sliderProducts.value.length > 1) {
          sliderTimer = setInterval(() => {
            currentSlideIndex.value = (currentSlideIndex.value + 1) % sliderProducts.value.length;
          }, 4000);
        }
      } catch (err) {
        console.error("Ошибка загрузки товаров:", err);
      }
    };

    const addToCart = (product) => {
      if (!product.in_stock) return;

      if (cart.value[product.id]) {
        cart.value[product.id].quantity++;
      } else {
        cart.value[product.id] = { id: product.id, name: product.name, price: product.price, quantity: 1 };
      }

      persistCart();
      if (window.Telegram?.WebApp) window.Telegram.WebApp.showAlert(`"${product.name}" в корзине`);
    };

    const updateQuantity = (productId, delta) => {
      if (!cart.value[productId]) return;
      const newQty = cart.value[productId].quantity + delta;
      if (newQty <= 0) delete cart.value[productId];
      else cart.value[productId].quantity = newQty;
      persistCart();
    };

    const removeFromCart = (productId) => {
      delete cart.value[productId];
      persistCart();
    };

    const setView = (view) => {
      currentView.value = view;
    };

    const goToSlide = (idx) => {
      currentSlideIndex.value = idx;
    };

    const isFavorite = (productId) => Boolean(favorites.value[productId]);
    const toggleFavorite = (product) => {
      if (isFavorite(product.id)) delete favorites.value[product.id];
      else favorites.value[product.id] = { ...product };
      persistFavorites();
    };

    const goToCheckout = () => {
      if (cartItems.value.length === 0) {
        if (window.Telegram?.WebApp) window.Telegram.WebApp.showAlert("Корзина пуста");
        return;
      }
      currentView.value = "checkout";
    };

    const openManagerContact = () => {
      if (!managerBaseUrl.value) return;
      const text = encodeURIComponent(`Здравствуйте! Заказ #${orderNumber.value}\nМагазин: ${shop.value?.name}\nСумма: ${orderTotalForManager.value} ₽`);
      const url = `${managerBaseUrl.value}?text=${text}`;
      if (window.Telegram?.WebApp?.openLink) window.Telegram.WebApp.openLink(url);
      else window.open(url, "_blank");
    };

    const openSupportChat = () => {
      const url = managerBaseUrl.value || "https://t.me/tgshopCLO_bot";
      if (window.Telegram?.WebApp?.openLink) window.Telegram.WebApp.openLink(url);
      else window.open(url, "_blank");
    };

    const submitOrder = async () => {
      if (!orderForm.name || !orderForm.phone) return;

      orderLoading.value = true;
      try {
        const payload = {
          shop_id: parseInt(shopId),
          customer_name: orderForm.name,
          phone: orderForm.phone,
          items: cartItems.value.map((item) => ({ id: item.id, quantity: item.quantity })),
          create_payment: false,
        };

        const response = await axios.post("/api/orders", payload, {
          headers: { "X-Telegram-Init-Data": window.Telegram?.WebApp?.initData || "" },
        });

        if (response.data.success) {
          orderNumber.value = response.data.order.id;
          orderSuccess.value = true;
          orderTotalForManager.value = response.data.amounts.total;
          cart.value = {};
          localStorage.removeItem(cartStorageKey.value);
        }
      } catch (err) {
        orderError.value = err?.response?.data?.message || "Ошибка";
      } finally {
        orderLoading.value = false;
      }
    };

    const resetOrder = () => {
      orderSuccess.value = false;
      currentView.value = "catalog";
    };

    return {
      loading,
      error,
      shop,
      categories,
      selectedCategory,
      onCategoryChange,
      showSearch,
      searchQuery,
      searchInput,
      searchResults,
      toggleSearch,
      closeSearch,
      visibleProducts,
      hasMoreProducts,
      showMoreProducts,
      currentView,
      sliderProducts,
      currentSlideIndex,
      favoriteItems,
      showBottomNav,
      telegramDisplayName,
      resolvedProfileAvatar,
      ownerAccountName,
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
      addToCart,
      updateQuantity,
      removeFromCart,
      setView,
      goToSlide,
      toggleFavorite,
      isFavorite,
      openSupportChat,
      goToCheckout,
      openManagerContact,
      submitOrder,
      resetOrder,
      isDescriptionExpanded,
      toggleDescription,
    };
  },
};
</script>

<style scoped>
:root {
  --bg-0: #070b18;
  --bg-1: #0d1326;
  --ink-0: #eff6ff;
  --ink-1: #a3b5db;
  --line: rgba(138, 178, 255, 0.24);
  --surface: rgba(14, 24, 46, 0.82);
  --surface-soft: rgba(17, 29, 55, 0.72);
  --accent: #38e8ff;
  --accent-2: #41ffbf;
  --bottom-nav-height: 72px;
}

.webapp-container {
  position: relative;
  min-height: 100vh;
  overflow-x: hidden;
  background: linear-gradient(165deg, var(--bg-0) 0%, var(--bg-1) 100%);
  font-family: sans-serif;
  color: var(--ink-0);
}

.bg-grid {
  position: absolute;
  inset: 0;
  opacity: 0.1;
  pointer-events: none;
  background-image: linear-gradient(var(--line) 1px, transparent 1px), linear-gradient(90deg, var(--line) 1px, transparent 1px);
  background-size: 30px 30px;
}

.content {
  position: relative;
  z-index: 1;
  width: 98%;
  margin: 0 auto;
  padding: 10px;
  padding-bottom: calc(var(--bottom-nav-height) + 24px);
  box-sizing: border-box;
}

.panel-shell {
  background: var(--surface-soft);
  border: 1px solid var(--line);
  border-radius: 20px;
  margin-top: 8px;
  backdrop-filter: blur(16px);
}

.shop-header-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 10px 4px; }
.shop-title-line { display: flex; align-items: baseline; gap: 8px; min-width: 0; }
.brand-kicker { font-size: 0.72rem; color: var(--accent); letter-spacing: 0.09em; text-transform: uppercase; white-space: nowrap; }
.shop-name { font-size: 1rem; font-weight: 700; color: var(--ink-0); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.search-toggle { border: 1px solid var(--line); background: rgba(255,255,255,.08); color:#fff; border-radius: 12px; width: 40px; height: 40px; font-size: 1rem; cursor: pointer; flex-shrink: 0; }
.category-select-wrap { padding: 8px 10px 12px; }
.category-select { width: 100%; height: 42px; border-radius: 12px; border: 1px solid var(--line); background: rgba(255,255,255,.08); color:#fff; padding: 0 10px; }

.hero-slider { margin: 0 10px 12px; }
.hero-slide { position: relative; border-radius: 14px; overflow: hidden; border: 1px solid var(--line); min-height: 180px; cursor: pointer; }
.hero-slide img { width: 100%; height: 180px; object-fit: cover; }
.hero-slide-overlay { position: absolute; inset: auto 0 0; background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,.78) 100%); padding: 14px; }
.hero-slide-overlay h2 { margin: 0; font-size: 1rem; }
.hero-slide-overlay p { margin: 4px 0 0; color: var(--accent-2); font-weight: 700; }
.hero-dots { display: flex; justify-content: center; gap: 6px; margin-top: 8px; }
.hero-dot { width: 8px; height: 8px; border-radius: 50%; border: 0; background: rgba(255,255,255,.3); }
.hero-dot.active { background: var(--accent); }

.products-list { display: grid; grid-template-columns: 1fr; gap: 10px; padding: 0 10px 12px; }
.product-card { width: 100%; border-radius: 16px; background: var(--surface); border: 1px solid var(--line); padding: 10px; box-sizing: border-box; position: relative; animation: cardIn .3s ease both; animation-delay: var(--delay); }
@keyframes cardIn { from { opacity:0; transform: translateY(6px);} to { opacity:1; transform: translateY(0);} }
.fav-btn { position: absolute; top: 8px; right: 8px; border: 0; background: rgba(255,255,255,.88); border-radius: 8px; width: 30px; height: 30px; cursor: pointer; }
.fav-btn.active { color: #ff4d6d; }
.product-image { width: 100%; border-radius: 12px; overflow: hidden; margin-bottom: 8px; }
.product-image img { width: 100%; height: 180px; object-fit: cover; display:block; }
.product-info h3 { margin: 0 0 4px; font-size: 1.08rem; line-height: 1.3; word-break: break-word; }
.price { margin: 0 0 6px; font-size: 1.1rem; color: var(--accent-2); font-weight: 700; }
.product-description-wrapper { margin-bottom: 8px; }
.description { margin: 0; color: var(--ink-1); font-size: .92rem; line-height: 1.4; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; word-break: break-word; }
.description.expanded { display: block; overflow: visible; }
.description-toggle-btn { margin-top: 4px; border: 0; background: none; color: var(--accent); font-size: .85rem; padding: 0; cursor: pointer; }
.category { margin: 0 0 8px; font-size: .82rem; color: var(--ink-1); }
.add-to-cart,
.show-more,
.checkout-btn,
.continue-shopping,
.submit-order,
.profile-item { border: 0; border-radius: 12px; cursor: pointer; }
.add-to-cart { width: 100%; padding: 10px; background: linear-gradient(90deg, var(--accent), var(--accent-2)); color: #00151a; font-weight: 700; }
.add-to-cart:disabled { background: rgba(255,255,255,.25); color: rgba(255,255,255,.7); }
.show-more { width: 100%; padding: 12px; background: rgba(255,255,255,.12); color: #fff; border: 1px solid var(--line); }

.cart-header,
.checkout-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px; }
.back-btn,
.qty-btn,
.remove-btn { border: 1px solid var(--line); background: rgba(255,255,255,.08); color: #fff; border-radius: 10px; cursor: pointer; }
.back-btn { padding: 8px 12px; }
.cart-items,
.checkout-form { padding: 0 10px 12px; }
.cart-item { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 10px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.item-info h3 { margin: 0 0 4px; font-size: 1rem; }
.item-price { margin: 0; color: var(--accent-2); }
.item-quantity { display: flex; align-items: center; gap: 6px; }
.qty-btn,
.remove-btn { min-width: 30px; min-height: 30px; }

.cart-total,
.order-summary { border: 1px solid var(--line); border-radius: 12px; background: rgba(255,255,255,.06); padding: 10px; margin: 10px 0; }
.total-row,
.summary-total,
.summary-item { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 6px; }
.grand-total { font-weight: 700; color: var(--accent-2); }
.checkout-btn,
.submit-order,
.continue-shopping { width: 100%; padding: 12px; font-weight: 700; background: linear-gradient(90deg, var(--accent), var(--accent-2)); color: #021018; margin-top: 8px; }
.form-group { margin-bottom: 10px; }
.form-group label { display: block; font-size: .85rem; margin-bottom: 4px; }
.form-group input { width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid var(--line); background: rgba(255,255,255,.08); color: #fff; height: 40px; padding: 0 10px; }

.manager-hint,
.order-error,
.success-message,
.empty-state { margin: 10px; padding: 12px; border-radius: 12px; border: 1px solid var(--line); background: rgba(255,255,255,.08); }
.success-message { border-color: rgba(65,255,191,.5); }

.profile-card { margin: 10px; padding: 14px; border-radius: 14px; border: 1px solid var(--line); background: var(--surface); text-align: center; }
.profile-avatar { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; display: block; margin: 0 auto 8px; }
.profile-avatar-placeholder { display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,.12); font-size: 1.5rem; }
.profile-username { margin: 4px 0 0; color: var(--ink-1); word-break: break-word; }
.profile-list { display: grid; gap: 8px; padding: 0 10px 12px; }
.profile-item { width: 100%; padding: 12px; background: rgba(255,255,255,.12); color: #fff; border: 1px solid var(--line); }

.bottom-nav { position: fixed; left: 0; right: 0; bottom: 0; height: var(--bottom-nav-height); display: grid; grid-template-columns: repeat(4,1fr); align-items: center; gap: 4px; padding: 8px 10px; background: rgba(10,15,30,.96); border-top: 1px solid var(--line); backdrop-filter: blur(12px); z-index: 999; box-sizing: border-box; }
.tab-btn { border: 0; background: none; color: #9fb0d3; font-size: .78rem; line-height: 1.2; padding: 4px; position: relative; cursor: pointer; }
.tab-btn.active { color: var(--accent); }
.tab-badge { position: absolute; top: -2px; right: 8px; min-width: 16px; height: 16px; border-radius: 999px; background: var(--accent-2); color: #00131a; font-size: .7rem; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; font-weight: 700; }
.tab-avatar { width: 20px; height: 20px; border-radius: 50%; object-fit: cover; }

.search-overlay { position: fixed; inset: 0; background: rgba(6,10,18,.96); z-index: 1200; padding: 10px; box-sizing: border-box; display: flex; }
.search-overlay-panel { width: 100%; border-radius: 14px; border: 1px solid var(--line); background: #0b1326; display: flex; flex-direction: column; max-height: 100%; }
.search-overlay-head { display: flex; gap: 8px; padding: 10px; border-bottom: 1px solid var(--line); }
.search-input { flex: 1; height: 40px; border-radius: 10px; border: 1px solid var(--line); background: rgba(255,255,255,.08); color: #fff; padding: 0 10px; }
.search-close { width: 40px; height: 40px; border-radius: 10px; border: 1px solid var(--line); background: rgba(255,255,255,.08); color: #fff; cursor: pointer; }
.search-results { overflow: auto; padding: 8px 10px 12px; }
.search-item { display: flex; justify-content: space-between; gap: 8px; align-items: center; border-bottom: 1px solid rgba(255,255,255,.08); padding: 10px 0; }
.search-item-name { margin: 0; font-size: .95rem; }
.search-item-meta { margin: 4px 0 0; color: var(--ink-1); font-size: .8rem; }
.search-add { border: 1px solid var(--line); background: rgba(56,232,255,.2); color: #fff; border-radius: 10px; padding: 8px 10px; cursor: pointer; }
.search-empty,
.loading,
.error { padding: 14px; text-align: center; color: var(--ink-1); }

@media (min-width: 768px) {
  .content { max-width: 720px; }
}
</style>
