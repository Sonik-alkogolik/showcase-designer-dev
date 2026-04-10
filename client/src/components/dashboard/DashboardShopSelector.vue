<template>
  <div class="shop-selector">
    <label for="dashboard-shop-select">Магазин</label>
    <select
      id="dashboard-shop-select"
      :value="selectedShopId"
      :disabled="loading || shops.length === 0"
      @change="$emit('update:selectedShopId', $event.target.value)"
    >
      <option v-if="shops.length === 0" value="">
        {{ loading ? 'Загрузка магазинов...' : 'Нет доступных магазинов' }}
      </option>
      <option v-for="shop in shops" :key="shop.id" :value="String(shop.id)">
        {{ shop.name }}
      </option>
    </select>
  </div>
</template>

<script setup>
defineProps({
  shops: {
    type: Array,
    default: () => [],
  },
  selectedShopId: {
    type: [String, Number],
    default: '',
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['update:selectedShopId'])
</script>

<style scoped>
.shop-selector {
  display: grid;
  gap: 0.28rem;
  min-width: 260px;
}

.shop-selector label {
  color: #4b5d79;
  font-size: 0.85rem;
}

.shop-selector select {
  border: 1px solid #c9d4eb;
  border-radius: 10px;
  background: #fff;
  color: #1e293b;
  padding: 0.55rem 0.65rem;
}
</style>

