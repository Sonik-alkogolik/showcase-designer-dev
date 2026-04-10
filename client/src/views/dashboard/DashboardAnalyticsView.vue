<template>
  <section class="page">
    <header class="page-head">
      <h1>Аналитика</h1>
      <p>Базовые метрики по товарам и заказам выбранного магазина.</p>
    </header>

    <div v-if="!selectedShopId" class="empty-box">Выберите магазин в верхнем селекторе.</div>
    <div v-else-if="loading" class="empty-box">Считаю метрики...</div>

    <div v-else class="grid">
      <article class="metric-card">
        <h3>Заказов</h3>
        <p>{{ metrics.ordersCount }}</p>
      </article>
      <article class="metric-card">
        <h3>Оплаченных</h3>
        <p>{{ metrics.paidCount }}</p>
      </article>
      <article class="metric-card">
        <h3>Выручка</h3>
        <p>{{ metrics.revenue }} ₽</p>
      </article>
      <article class="metric-card">
        <h3>Товаров</h3>
        <p>{{ metrics.productsCount }}</p>
      </article>
      <article class="metric-card">
        <h3>В наличии</h3>
        <p>{{ metrics.inStockCount }}</p>
      </article>
      <article class="metric-card">
        <h3>Средний чек</h3>
        <p>{{ metrics.avgCheck }} ₽</p>
      </article>
    </div>
  </section>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import axios from 'axios'
import { useDashboardContext } from '../../composables/useDashboardContext'

const { selectedShopId } = useDashboardContext()
const loading = ref(false)

const metrics = reactive({
  ordersCount: 0,
  paidCount: 0,
  revenue: 0,
  productsCount: 0,
  inStockCount: 0,
  avgCheck: 0,
})

const resetMetrics = () => {
  metrics.ordersCount = 0
  metrics.paidCount = 0
  metrics.revenue = 0
  metrics.productsCount = 0
  metrics.inStockCount = 0
  metrics.avgCheck = 0
}

const loadAnalytics = async () => {
  if (!selectedShopId.value) {
    resetMetrics()
    return
  }

  loading.value = true
  try {
    const [ordersRes, productsRes] = await Promise.all([
      axios.get(`/api/shops/${selectedShopId.value}/orders`, { params: { per_page: 100 } }),
      axios.get(`/api/shops/${selectedShopId.value}/products`, { params: { per_page: 100 } }),
    ])

    const orders = ordersRes.data?.orders?.data || []
    const products = productsRes.data?.products?.data || []

    const paidOrders = orders.filter((item) => item.status === 'paid')
    const revenue = paidOrders.reduce((sum, item) => sum + Number(item.total || 0) + Number(item.delivery_price || 0), 0)

    metrics.ordersCount = orders.length
    metrics.paidCount = paidOrders.length
    metrics.revenue = Number(revenue.toFixed(2))
    metrics.productsCount = products.length
    metrics.inStockCount = products.filter((item) => Boolean(item.in_stock)).length
    metrics.avgCheck = paidOrders.length > 0 ? Number((revenue / paidOrders.length).toFixed(2)) : 0
  } catch (error) {
    console.error('Failed to load analytics:', error)
    resetMetrics()
  } finally {
    loading.value = false
  }
}

watch(selectedShopId, loadAnalytics, { immediate: true })
</script>

<style scoped>
.page {
  display: grid;
  gap: 0.9rem;
}

.page-head h1 {
  margin: 0;
  color: #0f2a52;
}

.page-head p {
  margin: 0.2rem 0 0;
  color: #4b5d79;
}

.grid {
  display: grid;
  gap: 0.65rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.metric-card {
  border: 1px solid #d6dff1;
  border-radius: 12px;
  background: #fff;
  padding: 0.8rem;
}

.metric-card h3 {
  margin: 0;
  color: #2c446e;
  font-size: 0.95rem;
}

.metric-card p {
  margin: 0.36rem 0 0;
  font-size: 1.45rem;
  color: #0f2a52;
  font-weight: 700;
}

.empty-box {
  border: 1px dashed #c8d3ea;
  border-radius: 12px;
  background: #fff;
  padding: 1rem;
  color: #546480;
}

@media (max-width: 980px) {
  .grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 620px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
