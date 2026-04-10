<template>
  <section class="page">
    <header class="page-head">
      <h1>Товары</h1>
      <p>Список товаров выбранного магазина.</p>
    </header>

    <div v-if="!selectedShopId" class="empty-box">Выберите магазин в верхнем селекторе.</div>

    <template v-else>
      <div class="filters">
        <input
          v-model="search"
          type="text"
          placeholder="Поиск по названию..."
          @input="debouncedLoad"
        >
        <select v-model="category" @change="loadProducts">
          <option value="">Все категории</option>
          <option v-for="item in categories" :key="item.id" :value="String(item.id)">
            {{ item.name }}
          </option>
        </select>
        <router-link class="btn-primary" :to="`/shops/${selectedShopId}/products`">Открыть полный редактор</router-link>
      </div>

      <div v-if="loading" class="empty-box">Загрузка товаров...</div>
      <div v-else-if="products.length === 0" class="empty-box">Товары не найдены.</div>

      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Название</th>
              <th>Категория</th>
              <th>Цена</th>
              <th>Наличие</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in products" :key="product.id">
              <td>{{ product.id }}</td>
              <td>{{ product.name }}</td>
              <td>{{ resolveCategoryName(product.category) || 'Без категории' }}</td>
              <td>{{ Number(product.price || 0) }} ₽</td>
              <td>
                <span :class="product.in_stock ? 'status-ok' : 'status-off'">
                  {{ product.in_stock ? 'В наличии' : 'Нет' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </section>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import debounce from 'lodash/debounce'
import { useDashboardContext } from '../../composables/useDashboardContext'

const { selectedShopId } = useDashboardContext()

const loading = ref(false)
const products = ref([])
const categories = ref([])
const search = ref('')
const category = ref('')

const loadProducts = async () => {
  if (!selectedShopId.value) {
    products.value = []
    categories.value = []
    return
  }

  loading.value = true
  try {
    const response = await axios.get(`/api/shops/${selectedShopId.value}/products`, {
      params: {
        search: search.value,
        category: category.value,
        per_page: 30,
      },
    })
    products.value = response.data?.products?.data || []
    categories.value = response.data?.categories || []
  } catch (error) {
    console.error('Failed to load dashboard products:', error)
    products.value = []
    categories.value = []
  } finally {
    loading.value = false
  }
}

const resolveCategoryName = (categoryValue) => {
  if (!categoryValue) return null
  if (typeof categoryValue === 'string') return categoryValue
  if (typeof categoryValue === 'object') return categoryValue.name || null
  return null
}

const debouncedLoad = debounce(loadProducts, 280)

watch(selectedShopId, () => {
  category.value = ''
  search.value = ''
  loadProducts()
}, { immediate: true })
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

.filters {
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.filters input,
.filters select {
  border: 1px solid #c9d4eb;
  border-radius: 10px;
  background: #fff;
  padding: 0.55rem 0.62rem;
}

.filters input {
  min-width: 260px;
}

.btn-primary {
  text-decoration: none;
  border-radius: 10px;
  padding: 0.55rem 0.72rem;
  color: #fff;
  background: #2563eb;
}

.table-wrap {
  border: 1px solid #d4def1;
  border-radius: 12px;
  background: #fff;
  overflow: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  text-align: left;
  padding: 0.65rem 0.7rem;
  border-bottom: 1px solid #e5ecf8;
}

th {
  color: #1f3150;
  font-weight: 700;
}

.status-ok {
  color: #166534;
}

.status-off {
  color: #991b1b;
}

.empty-box {
  border: 1px dashed #c8d3ea;
  border-radius: 12px;
  background: #fff;
  padding: 1rem;
  color: #546480;
}
</style>
