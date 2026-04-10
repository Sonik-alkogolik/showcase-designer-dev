<template>
  <section class="page">
    <header class="page-head">
      <h1>Язык</h1>
      <p>MVP-настройка языка интерфейса панели.</p>
    </header>

    <div class="card">
      <label for="dashboard-language">Язык панели</label>
      <select id="dashboard-language" v-model="selectedLanguage" @change="saveLanguage">
        <option value="ru">Русский</option>
        <option value="en">English</option>
      </select>
      <p class="hint">Текущий выбор сохраняется в браузере и применяется при следующем открытии.</p>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'

const selectedLanguage = ref('ru')

const saveLanguage = () => {
  localStorage.setItem('dashboard_locale', selectedLanguage.value)
  document.documentElement.setAttribute('lang', selectedLanguage.value)
}

onMounted(() => {
  selectedLanguage.value = localStorage.getItem('dashboard_locale') || 'ru'
  document.documentElement.setAttribute('lang', selectedLanguage.value)
})
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

.card {
  max-width: 420px;
  border: 1px solid #d6dff1;
  border-radius: 12px;
  background: #fff;
  padding: 0.86rem;
  display: grid;
  gap: 0.5rem;
}

label {
  color: #324b75;
}

select {
  border: 1px solid #cbd7ee;
  border-radius: 9px;
  padding: 0.52rem 0.6rem;
}

.hint {
  margin: 0;
  color: #5f7190;
}
</style>
