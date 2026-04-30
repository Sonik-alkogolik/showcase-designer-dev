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

    <div v-if="showOnboardingModal" class="onboarding-overlay" @click.self="dismissOnboarding">
      <div class="onboarding-modal">
        <p class="onboarding-kicker">Быстрый старт</p>
        <h3>Помочь настроить магазин?</h3>

        <div v-if="quizStep === 0" class="quiz-step">
          <p class="quiz-question">У вас уже привязан Telegram в профиле?</p>
          <div class="quiz-actions">
            <button class="btn-primary" @click="nextQuizStep">Да, привязан</button>
            <button class="btn-secondary" @click="nextQuizStep">Нет, покажите как</button>
          </div>
        </div>

        <div v-else-if="quizStep === 1" class="quiz-step">
          <p class="quiz-question">Бот уже создан через BotFather?</p>
          <div class="quiz-actions">
            <button class="btn-primary" @click="nextQuizStep">Да, есть бот</button>
            <button class="btn-secondary" @click="nextQuizStep">Нет, нужен гайд</button>
          </div>
        </div>

        <div v-else class="quiz-step">
          <p class="quiz-question">Отлично. Давайте откроем пошаговую инструкцию «Как начать?»</p>
          <div class="quiz-actions">
            <button class="btn-primary" @click="openHelpFromOnboarding">Открыть инструкцию</button>
            <button class="btn-secondary" @click="dismissOnboarding">Позже</button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardSidebar from '../../components/dashboard/DashboardSidebar.vue'
import DashboardShopSelector from '../../components/dashboard/DashboardShopSelector.vue'
import { dashboardMenu, dashboardMenuGroups } from '../../config/dashboardMenu'
import { useDashboardContext } from '../../composables/useDashboardContext'
import { useAuth } from '../../composables/useAuth'

const sidebarCollapsed = ref(false)
const route = useRoute()
const router = useRouter()
const { user } = useAuth()
const { shops, shopsLoading, selectedShopId, loadShops, setSelectedShopId } = useDashboardContext()
const showOnboardingModal = ref(false)
const quizStep = ref(0)

const onboardingStorageKey = computed(() => {
  const userId = user.value?.id ? String(user.value.id) : 'guest'
  return `dashboard_onboarding_done_${userId}`
})

const hasCompletedOnboarding = () => localStorage.getItem(onboardingStorageKey.value) === '1'
const markOnboardingDone = () => localStorage.setItem(onboardingStorageKey.value, '1')

const pageTitle = computed(() => {
  const item = dashboardMenu.find((entry) => route.path.startsWith(entry.to))
  return item ? item.label : 'Панель'
})

const nextQuizStep = () => {
  quizStep.value = Math.min(quizStep.value + 1, 2)
}

const dismissOnboarding = () => {
  markOnboardingDone()
  showOnboardingModal.value = false
}

const openHelpFromOnboarding = async () => {
  markOnboardingDone()
  showOnboardingModal.value = false
  await router.push('/dashboard/help')
}

onMounted(() => {
  loadShops()
  if (!hasCompletedOnboarding()) {
    showOnboardingModal.value = true
  }
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

.onboarding-overlay {
  position: fixed;
  inset: 0;
  z-index: 1200;
  background: rgba(10, 18, 36, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.onboarding-modal {
  width: min(520px, 100%);
  border-radius: 16px;
  border: 1px solid #d4def1;
  background: #fff;
  padding: 1rem;
  box-shadow: 0 20px 44px rgba(15, 42, 82, 0.28);
}

.onboarding-kicker {
  margin: 0;
  color: #5f7598;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.72rem;
  font-weight: 700;
}

.onboarding-modal h3 {
  margin: 0.35rem 0 0.75rem;
  color: #133468;
}

.quiz-question {
  margin: 0;
  color: #2a456e;
}

.quiz-actions {
  margin-top: 0.9rem;
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.btn-primary,
.btn-secondary {
  border: 0;
  border-radius: 10px;
  padding: 0.6rem 0.9rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary {
  background: #2563eb;
  color: #fff;
}

.btn-secondary {
  background: #eef4ff;
  color: #1d3f72;
  border: 1px solid #c5d7f5;
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
