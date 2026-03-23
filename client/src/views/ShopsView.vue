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
          <button
            @click="deleteShop(shop)"
            class="btn-delete"
            :disabled="deletingShopId === shop.id"
            :title="deletingShopId === shop.id ? 'Удаление...' : 'Удалить магазин'"
          >
            {{ deletingShopId === shop.id ? '...' : '🗑️' }}
          </button>
        </div>
      </div>
    </div>
    
    <div v-else class="empty-state">
      <p>У вас пока нет магазинов</p>
      <router-link to="/create-shop" class="btn-primary">Создать магазин</router-link>
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
    const deletingShopId = ref(null)

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

    const deleteShop = async (shop) => {
      if (!confirm(`Удалить магазин "${shop.name}"?`)) return

      try {
        deletingShopId.value = shop.id
        await axios.delete(`/api/shops/${shop.id}`)
        shops.value = shops.value.filter(item => item.id !== shop.id)
      } catch (error) {
        console.error('Ошибка удаления магазина:', error)
        alert(error?.response?.data?.message || 'Не удалось удалить магазин')
      } finally {
        deletingShopId.value = null
      }
    }

    onMounted(loadShops)

    return {
      shops,
      deletingShopId,
      editShop,
      deleteShop
    }
  }
}
</script>

<style scoped>
.shops-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1.5rem 0;
  animation: page-in 500ms cubic-bezier(.2,.8,.2,1) both;
}

h1 {
  margin-bottom: 2rem;
  color: var(--color-heading);
}

.shops-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.shop-card {
  padding: 1.5rem;
  border: 1px solid rgba(171, 186, 255, 0.2);
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.02));
  box-shadow: 0 16px 32px rgba(0, 0, 0, 0.2);
  transition: transform 220ms ease, border-color 220ms ease;
}

.shop-card:hover {
  transform: translateY(-3px);
  border-color: rgba(117, 155, 255, 0.45);
}

.shop-card h2 {
  margin: 0 0 1rem;
  font-size: 1.3rem;
  color: #edf1ff;
}

.delivery {
  color: #b4bdd8;
  margin-bottom: 1.5rem;
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.btn-primary {
  background: linear-gradient(120deg, #4f63ff, #33c5ff);
  color: #f5f8ff;
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  flex: 1;
  text-align: center;
}

.btn-edit {
  background: rgba(106, 172, 255, 0.2);
  color: #dfe8ff;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
}

.btn-delete {
  background: rgba(255, 105, 126, 0.2);
  color: #ffd4db;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
}

.btn-delete:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #a4afcf;
}

.empty-state .btn-primary {
  display: inline-block;
  margin-top: 1rem;
}

@keyframes page-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
