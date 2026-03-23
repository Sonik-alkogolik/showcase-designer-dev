<template>
  <section class="landing">
    <div class="ambient ambient-a" />
    <div class="ambient ambient-b" />

    <header class="hero">
      <p class="eyebrow">Telegram Commerce Toolkit</p>
      <h1>Запускайте магазин в Telegram за один вечер</h1>
      <p class="lead">
        Подключайте каталог, оформляйте заказы и управляйте витриной в едином интерфейсе.
        Никакого конструктора с нуля, только быстрый старт и продажи.
      </p>

      <div class="hero-actions">
        <router-link class="cta cta-primary" to="/register">Начать бесплатно</router-link>
        <router-link class="cta cta-ghost" to="/login">Войти в кабинет</router-link>
      </div>
    </header>

    <section class="preview" v-if="!loading">
      <div class="preview-head">
        <h2>{{ shop?.name || 'Демо-витрина' }}</h2>
        <p>
          Пример каталога из вашей базы. Публичный endpoint:
          <code>/api/shops/{{ shopId }}/products/public</code>
        </p>
      </div>

      <div class="product-grid">
        <article v-for="product in featuredProducts" :key="product.id" class="product-tile">
          <div class="image-wrap">
            <img v-if="product.image_url" :src="product.image_url" :alt="product.name" />
            <div v-else class="image-fallback">{{ product.name?.[0] || 'P' }}</div>
          </div>
          <h3>{{ product.name }}</h3>
          <p>{{ product.description || 'Товар доступен для заказа в WebApp.' }}</p>
          <strong>{{ formatPrice(product.price) }}</strong>
        </article>
      </div>
    </section>

    <section class="preview error-state" v-else-if="error">
      <h2>Демо-данные недоступны</h2>
      <p>{{ error }}</p>
    </section>
  </section>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const loading = ref(true)
const error = ref('')
const shop = ref(null)
const products = ref([])

const shopId = computed(() => Number(route.query.shop || route.query.shopId || 2))
const featuredProducts = computed(() => products.value.slice(0, 6))

const formatPrice = (value) => {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(amount)
}

onMounted(async () => {
  loading.value = true
  error.value = ''

  try {
    const [shopResponse, productsResponse] = await Promise.all([
      axios.get(`/api/shops/${shopId.value}/public`),
      axios.get(`/api/shops/${shopId.value}/products/public`)
    ])

    shop.value = shopResponse.data?.shop || null
    const list = productsResponse.data?.products || []
    products.value = Array.isArray(list) ? list : []
  } catch (err) {
    error.value = err?.response?.data?.message || 'Проверьте, что публичный магазин существует и доступен.'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.landing {
  position: relative;
  overflow: hidden;
  padding: 3rem 0 5rem;
  animation: fade-in 700ms ease both;
}

.hero {
  position: relative;
  max-width: 880px;
  z-index: 2;
}

.eyebrow {
  font-size: 0.78rem;
  letter-spacing: 0.24em;
  text-transform: uppercase;
  color: var(--color-muted);
  margin-bottom: 1rem;
}

.hero h1 {
  font-size: clamp(2rem, 5vw, 4rem);
  line-height: 1.08;
  margin: 0;
  max-width: 14ch;
  color: var(--color-heading);
  animation: rise-in 700ms 140ms cubic-bezier(.2,.8,.2,1) both;
}

.lead {
  margin-top: 1.2rem;
  max-width: 56ch;
  color: #b7b9c2;
  font-size: 1.02rem;
  animation: rise-in 700ms 220ms cubic-bezier(.2,.8,.2,1) both;
}

.hero-actions {
  display: flex;
  gap: 0.9rem;
  margin-top: 1.6rem;
  animation: rise-in 700ms 300ms cubic-bezier(.2,.8,.2,1) both;
}

.cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
  border-radius: 999px;
  padding: 0 1.1rem;
  font-weight: 600;
  text-decoration: none;
  transition: transform 220ms ease, box-shadow 220ms ease, background-color 220ms ease;
}

.cta:hover {
  transform: translateY(-2px);
}

.cta-primary {
  background: linear-gradient(120deg, #4f63ff, #33c5ff);
  color: #f6f8ff;
  box-shadow: 0 16px 35px rgba(63, 128, 255, 0.35);
}

.cta-ghost {
  border: 1px solid rgba(179, 188, 255, 0.28);
  color: #dce1ff;
  background: rgba(255, 255, 255, 0.03);
}

.preview {
  margin-top: 3rem;
  position: relative;
  z-index: 2;
}

.preview-head h2 {
  font-size: clamp(1.2rem, 3vw, 1.8rem);
  color: var(--color-heading);
}

.preview-head p {
  margin-top: 0.5rem;
  color: #aeb4c8;
}

.preview-head code {
  font-size: 0.82rem;
  color: #d6dcff;
}

.product-grid {
  margin-top: 1.25rem;
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.product-tile {
  border: 1px solid rgba(188, 196, 255, 0.14);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
  border-radius: 18px;
  padding: 1rem;
  backdrop-filter: blur(8px);
  transition: transform 260ms ease, border-color 260ms ease;
}

.product-tile:hover {
  transform: translateY(-4px);
  border-color: rgba(142, 176, 255, 0.45);
}

.image-wrap {
  border-radius: 12px;
  overflow: hidden;
  height: 160px;
  margin-bottom: 0.8rem;
}

.image-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-fallback {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
  font-size: 2rem;
  font-weight: 700;
  color: #edf2ff;
  background: radial-gradient(circle at 25% 25%, #3f69ff, #1b2137 70%);
}

.product-tile h3 {
  font-size: 1.02rem;
  margin: 0 0 0.4rem;
  color: #edf0ff;
}

.product-tile p {
  margin: 0;
  color: #adb4ca;
  font-size: 0.92rem;
  min-height: 2.4em;
}

.product-tile strong {
  display: block;
  margin-top: 0.8rem;
  color: #f5f7ff;
}

.error-state {
  border: 1px solid rgba(255, 112, 112, 0.35);
  background: rgba(255, 95, 95, 0.08);
  border-radius: 16px;
  padding: 1rem;
}

.ambient {
  position: absolute;
  border-radius: 999px;
  filter: blur(16px);
  opacity: 0.35;
  z-index: 1;
}

.ambient-a {
  width: 320px;
  height: 320px;
  right: -90px;
  top: -90px;
  background: #495dff;
  animation: float-a 9s ease-in-out infinite;
}

.ambient-b {
  width: 260px;
  height: 260px;
  left: -80px;
  bottom: 10%;
  background: #0ea5e9;
  animation: float-b 11s ease-in-out infinite;
}

@keyframes fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes rise-in {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes float-a {
  0%, 100% { transform: translateY(0) translateX(0); }
  50% { transform: translateY(20px) translateX(-10px); }
}

@keyframes float-b {
  0%, 100% { transform: translateY(0) translateX(0); }
  50% { transform: translateY(-16px) translateX(12px); }
}

@media (max-width: 960px) {
  .product-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .landing {
    padding-top: 2rem;
  }

  .hero-actions {
    flex-direction: column;
  }

  .product-grid {
    grid-template-columns: 1fr;
  }
}
</style>
