<template>
  <section class="dashboard-layout">
    <DashboardSidebar
      :groups="dashboardMenuGroups"
      :collapsed="sidebarCollapsed"
      @toggle-collapse="sidebarCollapsed = !sidebarCollapsed"
    />

    <main class="dashboard-content">
      <header class="dashboard-toolbar">
        <div>
          <p class="kicker">Business Dashboard</p>
          <h2>{{ pageTitle }}</h2>
        </div>
        <DashboardShopSelector
          :shops="shops"
          :loading="shopsLoading"
          :selected-shop-id="selectedShopId"
          @update:selected-shop-id="setSelectedShopId"
        />
      </header>

      <router-view />
    </main>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import DashboardSidebar from '../../components/dashboard/DashboardSidebar.vue'
import DashboardShopSelector from '../../components/dashboard/DashboardShopSelector.vue'
import { dashboardMenu, dashboardMenuGroups } from '../../config/dashboardMenu'
import { useDashboardContext } from '../../composables/useDashboardContext'

const sidebarCollapsed = ref(false)
const route = useRoute()
const { shops, shopsLoading, selectedShopId, loadShops, setSelectedShopId } = useDashboardContext()

const pageTitle = computed(() => {
  const item = dashboardMenu.find((entry) => route.path.startsWith(entry.to))
  return item ? item.label : 'Панель'
})

onMounted(() => {
  loadShops()
})
</script>

<style scoped>
.dashboard-layout {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 0.9rem;
  align-items: start;
  min-height: 70vh;
}

.dashboard-content {
  min-width: 0;
  border: 1px solid rgba(151, 166, 207, 0.2);
  border-radius: 16px;
  background: #f6f8fc;
  padding: 1rem;
}

.dashboard-toolbar {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 0.8rem;
  border-bottom: 1px solid #d8e0f1;
  padding-bottom: 0.75rem;
  margin-bottom: 0.95rem;
}

.kicker {
  margin: 0;
  color: #5b6c88;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.74rem;
  font-weight: 700;
}

.dashboard-toolbar h2 {
  margin: 0.2rem 0 0;
  color: #0f2a52;
}

@media (max-width: 980px) {
  .dashboard-layout {
    grid-template-columns: 1fr;
  }

  .dashboard-toolbar {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
