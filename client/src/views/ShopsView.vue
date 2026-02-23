<template>
  <div class="shops-container">
    <h1>Мои магазины</h1>
    
    <div v-if="shops.length" class="shops-grid">
      <div v-for="shop in shops" :key="shop.id" class="shop-card">
        <h2>{{ shop.name }}</h2>
        <p class="delivery">
          Доставка: {{ shop.delivery_name }} - {{ shop.delivery_price }} ₽
        </p>
        <div class="actions">
          <router-link :to="`/shops/${shop.id}/products`" class="btn-primary">
            Товары
          </router-link>
          <button @click="editShop(shop)" class="btn-edit">✏️</button>
        </div>
      </div>
    </div>
    
    <div v-else class="empty-state">
      <p>У вас пока нет магазинов</p>
      <router-link to="/" class="btn-primary">Создать магазин</router-link>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'ShopsView',
  setup() {
    const shops = ref([])

    const loadShops = async () => {
      try {
        const response = await axios.get('/api/shops')
        shops.value = response.data.shops || []
      } catch (error) {
        console.error('Ошибка загрузки магазинов:', error)
      }
    }

    const editShop = (shop) => {
      // TODO: реализовать редактирование
      console.log('Edit shop:', shop)
    }

    onMounted(loadShops)

    return {
      shops,
      editShop
    }
  }
}
</script>

<style scoped>
.shops-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

h1 {
  margin-bottom: 2rem;
}

.shops-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.shop-card {
  padding: 1.5rem;
  border: 1px solid #eee;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.shop-card h2 {
  margin: 0 0 1rem;
  font-size: 1.3rem;
}

.delivery {
  color: #666;
  margin-bottom: 1.5rem;
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.btn-primary {
  background: #4CAF50;
  color: white;
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  flex: 1;
  text-align: center;
}

.btn-edit {
  background: #2196f3;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #999;
}

.empty-state .btn-primary {
  display: inline-block;
  margin-top: 1rem;
}
</style>
