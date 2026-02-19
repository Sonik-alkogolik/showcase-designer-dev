<template>
  <div class="plans-container">
    <h1>Выберите тариф</h1>
    
    <div v-if="loading" class="loading">
      Загрузка...
    </div>
    
    <div v-else class="plans-grid">
      <div 
        v-for="(plan, key) in plans" 
        :key="key"
        class="plan-card"
        :class="{ 'popular': plan.popular, 'selected': selectedPlan === key }"
        @click="selectedPlan = key"
      >
        <div v-if="plan.popular" class="popular-badge">Популярное</div>
        <h2>{{ plan.name }}</h2>
        <div class="price">{{ plan.price_formatted }}</div>
        <ul class="features">
          <li v-for="(feature, index) in plan.features" :key="index">
            ✓ {{ feature }}
          </li>
        </ul>
      </div>
    </div>

    <div v-if="selectedPlan" class="subscription-options">
      <div class="checkbox-group" v-if="plans[selectedPlan]?.auto_renew !== false">
        <label class="checkbox">
          <input type="checkbox" v-model="autoRenew">
          <span>Согласен на автопродление подписки</span>
        </label>
      </div>

      <div class="checkbox-group">
        <label class="checkbox">
          <input type="checkbox" v-model="offerAccepted">
          <span>Я ознакомлен с <a href="/offer" target="_blank">офертой</a></span>
        </label>
      </div>

      <div class="checkbox-group">
        <label class="checkbox">
          <input type="checkbox" v-model="privacyAccepted">
          <span>Я ознакомлен с <a href="/privacy" target="_blank">политикой конфиденциальности</a></span>
        </label>
      </div>

      <button 
        class="subscribe-btn"
        :disabled="!canSubscribe"
        @click="subscribe"
      >
        {{ subscribeLoading ? 'Оформление...' : 'Оформить подписку' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const loading = ref(true)
const subscribeLoading = ref(false)
const plans = ref({})
const selectedPlan = ref(null)
const autoRenew = ref(false)
const offerAccepted = ref(false)
const privacyAccepted = ref(false)

const canSubscribe = computed(() => {
  return selectedPlan.value && offerAccepted.value && privacyAccepted.value
})

onMounted(async () => {
  try {
    const response = await axios.get('/api/subscription/plans')
    plans.value = response.data.plans
  } catch (error) {
    console.error('Ошибка загрузки тарифов:', error)
  } finally {
    loading.value = false
  }
})

const subscribe = async () => {
  if (!canSubscribe.value) return
  
  subscribeLoading.value = true
  try {
    const response = await axios.post('/api/subscription/subscribe', {
      plan: selectedPlan.value,
      auto_renew: autoRenew.value,
      offer_accepted: offerAccepted.value,
      privacy_accepted: privacyAccepted.value
    })
    
    if (response.data.success) {
      alert('Подписка успешно оформлена!')
      // Здесь можно перенаправить на страницу магазинов
    }
  } catch (error) {
    console.error('Ошибка оформления подписки:', error)
    alert(error.response?.data?.message || 'Ошибка при оформлении подписки')
  } finally {
    subscribeLoading.value = false
  }
}
</script>

<style scoped>
.plans-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

h1 {
  text-align: center;
  margin-bottom: 2rem;
  color: #333;
}

.plans-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

.plan-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  border: 2px solid transparent;
}

.plan-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 12px rgba(0,0,0,0.15);
}

.plan-card.selected {
  border-color: #4CAF50;
  box-shadow: 0 0 0 2px rgba(76,175,80,0.2);
}

.plan-card.popular {
  border-color: #ff9800;
}

.popular-badge {
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  background: #ff9800;
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: bold;
}

.plan-card h2 {
  text-align: center;
  margin-bottom: 1rem;
  color: #333;
  font-size: 1.5rem;
}

.price {
  text-align: center;
  font-size: 2rem;
  font-weight: bold;
  color: #4CAF50;
  margin-bottom: 1.5rem;
}

.features {
  list-style: none;
  padding: 0;
  margin: 0;
}

.features li {
  padding: 0.5rem 0;
  color: #666;
  border-bottom: 1px solid #eee;
}

.features li:last-child {
  border-bottom: none;
}

.subscription-options {
  max-width: 500px;
  margin: 0 auto;
  background: #f9f9f9;
  padding: 2rem;
  border-radius: 12px;
}

.checkbox-group {
  margin-bottom: 1rem;
}

.checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  color: #333;
}

.checkbox a {
  color: #4CAF50;
  text-decoration: none;
}

.checkbox a:hover {
  text-decoration: underline;
}

.subscribe-btn {
  width: 100%;
  padding: 1rem;
  background: #4CAF50;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1.1rem;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.3s ease;
  margin-top: 1rem;
}

.subscribe-btn:hover:not(:disabled) {
  background: #45a049;
}

.subscribe-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.loading {
  text-align: center;
  padding: 2rem;
  color: #666;
}
</style>