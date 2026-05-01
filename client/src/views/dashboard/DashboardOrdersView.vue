<template>
  <section class="page">
    <header class="page-head">
      <h1>Заказы</h1>
      <p>Управление статусами заказов выбранного магазина.</p>
    </header>

    <div v-if="!selectedShopId" class="empty-box">Выберите магазин в верхнем селекторе.</div>
    <div v-else-if="loading" class="empty-box">Загрузка заказов...</div>
    <div v-else-if="orders.length === 0" class="empty-box">Заказы пока отсутствуют.</div>

    <div v-else class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Клиент</th>
            <th>Телефон</th>
            <th>Сумма</th>
            <th>Статус</th>
            <th>Обновить</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in orders" :key="order.id">
            <td>#{{ order.id }}</td>
            <td>{{ order.customer_name }}</td>
            <td>{{ order.phone }}</td>
            <td>{{ orderTotal(order) }} ₽</td>
            <td><span :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span></td>
            <td>
              <select :value="order.status" @change="changeStatus(order, $event.target.value)">
                <option value="pending">pending</option>
                <option value="paid">paid</option>
                <option value="cancelled">cancelled</option>
              </select>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { useDashboardContext } from '../../composables/useDashboardContext'

const { selectedShopId } = useDashboardContext()
const loading = ref(false)
const orders = ref([])

const loadOrders = async () => {
  if (!selectedShopId.value) {
    orders.value = []
    return
  }
  loading.value = true
  try {
    const response = await axios.get(`/api/shops/${selectedShopId.value}/orders`, {
      params: { per_page: 30 },
    })
    orders.value = response.data?.orders?.data || []
  } catch (error) {
    console.error('Failed to load dashboard orders:', error)
    orders.value = []
  } finally {
    loading.value = false
  }
}

const statusLabel = (value) => {
  if (value === 'paid') return 'Оплачен'
  if (value === 'cancelled') return 'Отменен'
  return 'Ожидает'
}

const statusClass = (value) => {
  if (value === 'paid') return 'status-ok'
  if (value === 'cancelled') return 'status-off'
  return 'status-pending'
}

const orderTotal = (order) => {
  const subtotal = Number(order.total || 0)
  const delivery = Number(order.delivery_price || 0)
  return subtotal + delivery
}

const changeStatus = async (order, status) => {
  if (!selectedShopId.value || status === order.status) return
  try {
    await axios.put(`/api/shops/${selectedShopId.value}/orders/${order.id}`, { status })
    order.status = status
  } catch (error) {
    console.error('Failed to update order status:', error)
  }
}

watch(selectedShopId, loadOrders, { immediate: true })
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
  color: #1f2f47;
}

th {
  color: #132848;
  font-weight: 700;
}

select {
  border: 1px solid #cbd7ee;
  border-radius: 8px;
  padding: 0.36rem 0.44rem;
  background: #fff;
}

.status-ok {
  color: #166534;
  font-weight: 600;
}

.status-off {
  color: #991b1b;
  font-weight: 600;
}

.status-pending {
  color: #92400e;
  font-weight: 600;
}

.empty-box {
  border: 1px dashed #c8d3ea;
  border-radius: 12px;
  background: #fff;
  padding: 1rem;
  color: #546480;
}
</style>
