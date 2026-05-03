<template>
  <div class="webapp-container" :style="themeStyleVars">
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
        <button class="category-select-trigger" @click="toggleCategoryDropdown">
          <span>{{ selectedCategoryLabel }}</span>
          <span class="category-chevron" :class="{ open: isCategoryDropdownOpen }">⌄</span>
        </button>
      </div>

      <div v-if="sliderProducts.length" class="hero-slider">
        <div class="hero-slide" @click="addToCart(sliderProducts[currentSlideIndex])">
          <img
            v-if="sliderProducts[currentSlideIndex]?.image"
            :src="sliderProducts[currentSlideIndex]?.image"
            :alt="sliderProducts[currentSlideIndex]?.name"
            @error="onImageError"
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

          <div class="product-image" v-if="product.image" @click.stop="openImagePreview(product.image, product.name)">
            <img :src="product.image" :alt="product.name" @error="onImageError" />
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

            <p class="category">Категория: {{ product.category_name || "Без категории" }}</p>
            <p class="stock" :class="{ 'in-stock': product.in_stock }">
              {{ product.in_stock ? "В наличии" : "Нет в наличии" }}
            </p>
            <button class="add-to-cart" @click="addToCart(product)" :disabled="!product.in_stock">
              <template v-if="product.in_stock">В корзину</template>
              <template v-else>Нет в наличии</template>
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
          <div class="product-image" v-if="product.image" @click.stop="openImagePreview(product.image, product.name)">
            <img :src="product.image" :alt="product.name" @error="onImageError" />
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
            <p class="category">Категория: {{ product.category_name || "Без категории" }}</p>
            <p class="stock" :class="{ 'in-stock': product.in_stock }">
              {{ product.in_stock ? "В наличии" : "Нет в наличии" }}
            </p>
            <button class="add-to-cart" @click="addToCart(product)">
              В корзину
            </button>
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

        <button class="checkout-btn" @click="openManagerCartPopup">Оформить покупку</button>
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
        <button class="profile-item" @click="openSupportChat">Связаться с менеджером</button>
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
      <button class="tab-btn tab-favorites" :class="{ active: currentView === 'favorites' }" @click="setView('favorites')">
        Избранное<span v-if="favoriteTotalItems">: {{ favoriteTotalItems }}</span>
      </button>
      <button class="tab-btn tab-cart" :class="{ active: currentView === 'cart' }" @click="setView('cart')">
        Корзина<span v-if="cartTotalItems">: {{ cartTotalItems }}</span>
      </button>
      <button class="tab-btn tab-profile" :class="{ active: currentView === 'profile' }" @click="setView('profile')">
        <img v-if="resolvedProfileAvatar" :src="resolvedProfileAvatar" alt="profile" class="tab-avatar" />
        <span v-else>Профиль</span>
      </button>
    </div>

    <div v-if="bottomNoticeVisible" class="bottom-notice">
      {{ bottomNotice }}
    </div>

    <div v-if="showManagerPopup" class="manager-popup-overlay" @click.self="closeManagerPopup">
      <div class="manager-popup">
        <h3>Сообщение менеджеру</h3>
        <textarea v-model="managerDraftMessage" rows="10" />
        <div class="manager-popup-actions">
          <button class="btn-ghost-light" type="button" @click="closeManagerPopup">Отмена</button>
          <button class="btn-primary-light" type="button" @click="sendToManager">Отправить</button>
        </div>
      </div>
    </div>

    <div v-if="previewImageUrl" class="image-preview-overlay" @click="closeImagePreview">
      <div class="image-preview-content" @click.stop>
        <button type="button" class="image-preview-close" @click="closeImagePreview">✕</button>
        <img :src="previewImageUrl" :alt="previewImageAlt || 'preview'" class="image-preview-full">
      </div>
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
            <button class="search-add" @click="addToCart(item)">
              В корзину
            </button>
          </div>
        </div>

        <div class="search-empty" v-else>Ничего не найдено</div>
      </div>
    </div>

    <div v-if="isCategoryDropdownOpen" class="category-sheet-backdrop" @click.self="closeCategoryDropdown">
      <div class="category-sheet">
        <div class="category-sheet-head">
          <h3>Категории</h3>
          <button class="category-sheet-close" @click="closeCategoryDropdown">✕</button>
        </div>
        <div class="category-sheet-list">
          <button class="category-option" :class="{ active: selectedCategory === '' }" @click="selectCategoryOption('')">
            Весь магазин
          </button>
          <button
            v-for="cat in categories"
            :key="cat.id"
            class="category-option"
            :class="{ active: selectedCategory === String(cat.id) }"
            @click="selectCategoryOption(String(cat.id))"
          >
            {{ cat.name }}
          </button>
        </div>
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
    const isCategoryDropdownOpen = ref(false);
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
    const bottomNotice = ref("");
    const bottomNoticeVisible = ref(false);
    const previewImageUrl = ref("");
    const previewImageAlt = ref("");
    const showManagerPopup = ref(false);
    const managerDraftMessage = ref("");
    let bottomNoticeTimer = null;

    const cartItems = computed(() => Object.values(cart.value));
    const favoriteItems = computed(() => Object.values(favorites.value));
    const sliderProducts = computed(() => {
      return products.value.filter((item) => item.show_in_slider);
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
    const themeStyleVars = computed(() => {
      const theme = shop.value?.theme_settings || {};
      const fallback = {
        background_start: "#070B18",
        background_end: "#0D1326",
        text_color: "#EFF6FF",
        dots_color: "#38E8FF",
        shop_name_color: "#EFF6FF",
        search_color: "#EFF6FF",
        categories_color: "#FFFFFF",
        footer_text_color: "#9FB0D3",
        footer_bg_color: "#0A0F1E",
        card_bg_color: "#050B1D",
        card_title_color: "#EEF4FF",
        card_price_color: "#4CAF50",
        card_button_bg_color: "#38E8FF",
        card_button_text_color: "#00151A",
        manager_send_button_text_color: "#FFFFFF",
      };
      const pick = (key) => {
        const value = String(theme?.[key] || "").trim().toUpperCase();
        return /^#([A-F0-9]{6})$/.test(value) ? value : fallback[key];
      };

      return {
        "--bg-0": pick("background_start"),
        "--bg-1": pick("background_end"),
        "--ink-0": pick("text_color"),
        "--hero-dot-active": pick("dots_color"),
        "--shop-name-color": pick("shop_name_color"),
        "--search-color": pick("search_color"),
        "--categories-color": pick("categories_color"),
        "--footer-text-color": pick("footer_text_color"),
        "--footer-bg-color": pick("footer_bg_color"),
        "--card-bg-color": pick("card_bg_color"),
        "--card-title-color": pick("card_title_color"),
        "--card-price-color": pick("card_price_color"),
        "--card-button-bg-color": pick("card_button_bg_color"),
        "--card-button-text-color": pick("card_button_text_color"),
        "--manager-send-button-text-color": pick("manager_send_button_text_color"),
      };
    });
    const favoriteTotalItems = computed(() => favoriteItems.value.length);
    const cartTotalItems = computed(() => cartItems.value.reduce((sum, item) => sum + item.quantity, 0));
    const cartSubtotal = computed(() => cartItems.value.reduce((sum, item) => sum + ((parseFloat(item.price) || 0) * item.quantity), 0));
    const cartTotal = computed(() => cartSubtotal.value + (parseFloat(shop.value?.delivery_price) || 0));

    const hasManagerContact = computed(() => Boolean(shop.value?.manager_contact_ready || shop.value?.manager_telegram_url));

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

    const showBottomNotice = (message) => {
      bottomNotice.value = message;
      bottomNoticeVisible.value = true;
      if (bottomNoticeTimer) clearTimeout(bottomNoticeTimer);
      bottomNoticeTimer = setTimeout(() => {
        bottomNoticeVisible.value = false;
      }, 1600);
    };

    const onCategoryChange = async () => {
      await loadProducts();
      visibleCount.value = PRODUCTS_STEP;
    };

    const selectedCategoryLabel = computed(() => {
      if (!selectedCategory.value) return "Весь магазин";
      const found = categories.value.find((cat) => String(cat.id) === selectedCategory.value);
      return found?.name || "Весь магазин";
    });

    const toggleCategoryDropdown = () => {
      isCategoryDropdownOpen.value = !isCategoryDropdownOpen.value;
    };

    const closeCategoryDropdown = () => {
      isCategoryDropdownOpen.value = false;
    };

    const selectCategoryOption = async (value) => {
      selectedCategory.value = value;
      isCategoryDropdownOpen.value = false;
      await onCategoryChange();
    };

    const onImageError = (event) => {
      const target = event?.target;
      if (!target) return;
      target.style.display = "none";
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

    const openImagePreview = (url, alt = "") => {
      previewImageUrl.value = String(url || "");
      previewImageAlt.value = String(alt || "");
    };

    const closeImagePreview = () => {
      previewImageUrl.value = "";
      previewImageAlt.value = "";
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
      if (bottomNoticeTimer) clearTimeout(bottomNoticeTimer);
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
      const qty = cart.value[product.id]?.quantity || 0;
      showBottomNotice(`Добавлено в корзину: ${qty} шт.`);
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
      if (isFavorite(product.id)) {
        delete favorites.value[product.id];
        showBottomNotice("Удалено из избранного");
      } else {
        favorites.value[product.id] = { ...product };
        showBottomNotice("Добавлено в избранное");
      }
      persistFavorites();
    };

    const goToCheckout = () => {
      if (cartItems.value.length === 0) {
        if (window.Telegram?.WebApp) window.Telegram.WebApp.showAlert("Корзина пуста");
        return;
      }
      currentView.value = "checkout";
    };

    const getDefaultManagerTemplate = () => "Добрый день! Хотел бы приобрести товар или товары";

    const buildCartItemsText = () => {
      if (cartItems.value.length === 0) return "- Корзина пока пустая";
      return cartItems.value
        .map((item) => `- ${item.name} x${item.quantity} (${item.price} ₽)`)
        .join("\n");
    };

    const buildManagerMessage = () => {
      const rawTemplate = String(shop.value?.manager_message_template || "").trim() || getDefaultManagerTemplate();
      const itemsText = buildCartItemsText();
      if (rawTemplate.includes("{items}")) {
        return rawTemplate.replaceAll("{items}", itemsText);
      }
      return `${rawTemplate}\n\nСостав корзины:\n${itemsText}`;
    };

    const openManagerContact = () => {
      if (!hasManagerContact.value) return;
      managerDraftMessage.value = `Здравствуйте! Заказ #${orderNumber.value}\nМагазин: ${shop.value?.name}\nСумма: ${orderTotalForManager.value} ₽`;
      showManagerPopup.value = true;
    };

    const openManagerCartPopup = () => {
      managerDraftMessage.value = buildManagerMessage();
      showManagerPopup.value = true;
    };

    const closeManagerPopup = () => {
      showManagerPopup.value = false;
    };

    const openManagerLink = (prefillText = "") => {
      const baseUrl = String(shop.value?.manager_telegram_url || "").trim();
      if (!baseUrl) return;

      let url = baseUrl;
      const text = String(prefillText || "").trim();
      if (text) {
        const delimiter = baseUrl.includes("?") ? "&" : "?";
        url = `${baseUrl}${delimiter}text=${encodeURIComponent(text)}`;
      }

      if (window.Telegram?.WebApp?.openLink) window.Telegram.WebApp.openLink(url);
      else window.open(url, "_blank");
    };

    const sendToManager = async () => {
      if (!hasManagerContact.value) {
        if (window.Telegram?.WebApp?.showAlert) {
          window.Telegram.WebApp.showAlert("Контакт менеджера не настроен");
        }
        return;
      }

      try {
        const text = managerDraftMessage.value || buildManagerMessage();
        const response = await axios.post(`/api/shops/${shopId}/manager-message`, {
          message: text,
        }, {
          headers: { "X-Telegram-Init-Data": window.Telegram?.WebApp?.initData || "" },
        });

        if (response.data?.success) {
          showManagerPopup.value = false;
          showBottomNotice("Сообщение отправлено менеджеру");
          openManagerLink(managerDraftMessage.value || buildManagerMessage());
        }
      } catch (err) {
        const msg = err?.response?.data?.message || "Не удалось отправить сообщение через API. Открываем чат менеджера.";
        if (window.Telegram?.WebApp?.showAlert) window.Telegram.WebApp.showAlert(msg);
        openManagerLink(managerDraftMessage.value || buildManagerMessage());
      }
    };

    const openSupportChat = () => {
      if (!hasManagerContact.value) {
        if (window.Telegram?.WebApp?.showAlert) {
          window.Telegram.WebApp.showAlert("Контакт менеджера не настроен");
        }
        return;
      }
      if (shop.value?.manager_contact_ready) {
        managerDraftMessage.value = String(shop.value?.manager_message_template || "").trim() || getDefaultManagerTemplate();
        showManagerPopup.value = true;
        return;
      }
      openManagerLink(String(shop.value?.manager_message_template || "").trim() || getDefaultManagerTemplate());
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
      themeStyleVars,
      categories,
      selectedCategory,
      selectedCategoryLabel,
      isCategoryDropdownOpen,
      onCategoryChange,
      toggleCategoryDropdown,
      closeCategoryDropdown,
      selectCategoryOption,
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
      favoriteTotalItems,
      cartTotalItems,
      cartSubtotal,
      cartTotal,
      orderForm,
      orderLoading,
      orderSuccess,
      orderNumber,
      orderError,
      bottomNotice,
      bottomNoticeVisible,
      showManagerPopup,
      managerDraftMessage,
      previewImageUrl,
      previewImageAlt,
      hasManagerContact,
      addToCart,
      openImagePreview,
      closeImagePreview,
      updateQuantity,
      removeFromCart,
      setView,
      goToSlide,
      toggleFavorite,
      isFavorite,
      openSupportChat,
      goToCheckout,
      openManagerContact,
      openManagerCartPopup,
      closeManagerPopup,
      sendToManager,
      submitOrder,
      resetOrder,
      isDescriptionExpanded,
      toggleDescription,
      onImageError,
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
  --hero-dot-active: #38e8ff;
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
.shop-name { font-size: 1rem; font-weight: 700; color: var(--shop-name-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.search-toggle { border: 1px solid var(--line); background: rgba(255,255,255,.08); color: var(--search-color); border-radius: 12px; width: 40px; height: 40px; font-size: 1rem; cursor: pointer; flex-shrink: 0; }
.category-select-wrap {
  padding: 8px 10px 12px;
  position: relative;
}

.category-select-trigger {
  width: 100%;
  height: 42px;
  border-radius: 12px;
  border: 1px solid var(--line);
  background: rgba(255, 255, 255, 0.08);
  color: var(--categories-color);
  padding: 0 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  font-size: 0.95rem;
}

.category-chevron {
  font-size: 1.05rem;
  transition: transform 0.2s ease;
}

.category-chevron.open {
  transform: rotate(180deg);
}

.category-dropdown-menu {
  display: none;
}

.category-option {
  width: 100%;
  border: 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: transparent;
  color: var(--categories-color);
  text-align: left;
  padding: 10px 12px;
  cursor: pointer;
  font-size: 0.92rem;
}

.category-option:last-child {
  border-bottom: 0;
}

.category-option.active {
  background: rgba(56, 232, 255, 0.15);
  color: var(--categories-color);
}

.category-sheet-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1300;
  background: rgba(4, 8, 16, 0.75);
  display: flex;
  align-items: flex-end;
}

.category-sheet {
  width: 100%;
  max-height: min(62vh, 420px);
  border-top-left-radius: 16px;
  border-top-right-radius: 16px;
  border: 1px solid var(--line);
  border-bottom: 0;
  background: #111d33;
  box-shadow: 0 -10px 26px rgba(0, 0, 0, 0.4);
  display: flex;
  flex-direction: column;
}

.category-sheet-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.09);
}

.category-sheet-head h3 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
}

.category-sheet-close {
  border: 1px solid var(--line);
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  cursor: pointer;
}

.category-sheet-list {
  overflow: auto;
  padding-bottom: max(10px, env(safe-area-inset-bottom));
}

.hero-slider { margin: 0 10px 12px; }
.hero-slide { position: relative; border-radius: 14px; overflow: hidden; border: 1px solid var(--line); min-height: 180px; cursor: pointer; }
.hero-slide img { width: 100%; height: 180px; object-fit: cover; }
.hero-slide-overlay { position: absolute; inset: auto 0 0; background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,.78) 100%); padding: 14px; }
.hero-slide-overlay h2 { margin: 0; font-size: 1rem; }
.hero-slide-overlay p { margin: 4px 0 0; color: var(--accent-2); font-weight: 700; }
.hero-dots { display: flex; justify-content: center; gap: 6px; margin-top: 8px; }
.hero-dot { width: 8px; height: 8px; border-radius: 50%; border: 0; background: rgba(255,255,255,.3); }
.hero-dot.active { background: var(--hero-dot-active); }

.products-list { display: flex; flex-direction: column; gap: 10px; padding: 0 10px 12px; width: 100%; margin-bottom: 50px; }
.product-card { width: 100%; max-width: none; margin: 0 auto; border-radius: 10px; background: var(--card-bg-color); border: 1px solid rgba(215, 229, 255, 0.65); padding: 12px; box-sizing: border-box; position: relative; animation: cardIn .3s ease both; animation-delay: var(--delay); }
@keyframes cardIn { from { opacity:0; transform: translateY(6px);} to { opacity:1; transform: translateY(0);} }
.fav-btn { position: absolute; top: 12px; right: 12px; border: 0; background: rgba(255,255,255,.88); border-radius: 8px; width: 30px; height: 30px; cursor: pointer; }
.fav-btn.active { color: #ff4d6d; }
.product-image { width: 100%; border-radius: 12px; overflow: hidden; margin-bottom: 8px; }
.product-image img { width: 100%; height: 180px; object-fit: cover; display:block; }
.product-info { display: flex; flex-direction: column; gap: 6px; }
.product-info h3 { margin: 0 0 4px; padding-right: 38px; font-size: 1.14rem; line-height: 1.3; color: var(--card-title-color); overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; word-break: break-word; overflow-wrap: anywhere; }
.price { margin: 0 0 6px; font-size: 2rem; color: var(--card-price-color); font-weight: 700; }
.product-description-wrapper { margin-bottom: 8px; }
.description { margin: 0; color: #c7d7ef; font-size: .96rem; line-height: 1.45; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; word-break: break-word; }
.description.expanded { display: block; overflow: visible; }
.description-toggle-btn { width: 100%; margin-top: 6px; border: 1px solid #e3e8ef; background: #f7f9fc; color: #2c3e50; border-radius: 8px; font-size: .95rem; padding: 10px 12px; cursor: pointer; text-align: left; font-weight: 600; }
.category { margin: 0 0 4px; font-size: .92rem; color: #9fb4d0; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; word-break: break-word; overflow-wrap: anywhere; }
.stock { margin: 0 0 10px; color: #f44336; font-weight: 600; }
.stock.in-stock { color: #4CAF50; }
.add-to-cart,
.show-more,
.checkout-btn,
.continue-shopping,
.submit-order,
.profile-item { border: 0; border-radius: 12px; cursor: pointer; }
.add-to-cart { width: 100%; min-height: 42px; display: flex; align-items: center; justify-content: center; text-align: center; padding: 10px; background: var(--card-button-bg-color); color: var(--card-button-text-color); font-weight: 700; }
.add-to-cart:disabled { background: rgba(255,255,255,.2); color: rgba(255,255,255,.6); }
.show-more { width: 100%; padding: 12px; margin-top: 2px; margin-bottom: calc(var(--bottom-nav-height) + 8px); background: #f7f9fc; color: #2c3e50; border: 1px solid #d7e3f3; font-weight: 700; }

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
.continue-shopping { width: 100%; padding: 12px; font-weight: 700; background: linear-gradient(90deg, rgba(18, 50, 110, 0.95), rgba(26, 84, 176, 0.95)); color: #ffffff; margin-top: 8px; border: 1px solid rgba(140, 188, 255, 0.65); box-shadow: 0 8px 20px rgba(22, 90, 210, 0.35); text-shadow: 0 1px 1px rgba(0,0,0,.35); }
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

.bottom-nav { position: fixed; left: 0; right: 0; bottom: 0; height: var(--bottom-nav-height); display: grid; grid-template-columns: repeat(4,1fr); align-items: center; gap: 4px; padding: 8px 10px; background: var(--footer-bg-color); border-top: 1px solid var(--line); backdrop-filter: blur(12px); z-index: 999; box-sizing: border-box; }
.tab-btn { border: 0; background: none; color: var(--footer-text-color); font-size: .78rem; line-height: 1.2; padding: 4px; position: relative; cursor: pointer; }
.tab-btn.active { color: var(--footer-text-color); font-weight: 700; }
.tab-avatar { width: 20px; height: 20px; border-radius: 50%; object-fit: cover; }
.manager-popup-overlay { position: fixed; inset: 0; z-index: 1500; background: rgba(3, 9, 19, 0.8); display: grid; place-items: center; padding: 12px; }
.manager-popup { width: min(720px, 100%); border-radius: 14px; border: 1px solid var(--line); background: #0f1b33; padding: 12px; display: grid; gap: 10px; }
.manager-popup h3 { margin: 0; font-size: 1rem; }
.manager-popup textarea { width: 100%; border: 1px solid var(--line); border-radius: 10px; background: rgba(255,255,255,.08); color: #fff; padding: 10px; box-sizing: border-box; resize: vertical; }
.manager-popup-actions { display: flex; justify-content: flex-end; gap: 8px; }
.btn-ghost-light, .btn-primary-light { border-radius: 10px; min-height: 38px; padding: 0 12px; cursor: pointer; }
.btn-ghost-light { border: 1px solid var(--line); background: transparent; color: #dbe8ff; }
.btn-primary-light { border: 0; background: var(--accent); color: var(--manager-send-button-text-color); font-weight: 700; }
.bottom-notice { position: fixed; left: 10px; right: 10px; bottom: calc(var(--bottom-nav-height) + 52px); z-index: 1000; padding: 10px 12px; border-radius: 10px; border: 1px solid rgba(65,255,191,.45); background: rgba(6, 26, 24, .93); color: #dafff0; font-size: .86rem; text-align: center; font-weight: 700; backdrop-filter: blur(8px); }

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
.image-preview-overlay { position: fixed; inset: 0; z-index: 1400; background: rgba(4, 8, 16, 0.82); display: flex; align-items: center; justify-content: center; padding: 12px; }
.image-preview-content { position: relative; max-width: 96vw; max-height: 92vh; }
.image-preview-full { display: block; max-width: 96vw; max-height: 92vh; width: auto; height: auto; border-radius: 12px; box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5); }
.image-preview-close { position: absolute; top: -12px; right: -12px; width: 32px; height: 32px; border: 1px solid var(--line); border-radius: 999px; background: rgba(10, 15, 30, 0.95); color: #fff; cursor: pointer; font-size: 1rem; line-height: 1; }

@media (min-width: 768px) {
  .content { max-width: 720px; }
}
</style>
