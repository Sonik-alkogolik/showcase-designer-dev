<template>
  <section class="page">
    <header class="page-head">
      <h1>Как начать?</h1>
      <p>Короткий запуск магазина в Telegram mini-app.</p>
    </header>

    <article class="intro-card">
      <p>
        Сначала привяжите Telegram к вашему профилю, затем создайте бота в BotFather,
        после этого создайте магазин и подключите токен бота.
      </p>
      <div class="intro-actions">
        <router-link class="action-btn primary" to="/profile">Открыть профиль</router-link>
        <a class="action-btn" href="/create-shop">Открыть create-shop</a>
        <button class="action-btn" type="button" @click="restartOnboarding">Запустить onboarding снова</button>
      </div>
    </article>

    <div class="steps">
      <article class="step-card">
        <div class="step-top">
          <span class="step-badge">Шаг 1</span>
          <h3>Привяжите Telegram в профиле</h3>
        </div>
        <p>Откройте профиль, нажмите «Привязать Telegram» и подтвердите связь аккаунта.</p>
        <img src="/help/start-step-1-profile-ui.svg" alt="Привязка Telegram в профиле" loading="lazy">
      </article>

      <article class="step-card">
        <div class="step-top">
          <span class="step-badge">Шаг 2</span>
          <h3>Создайте бота через BotFather</h3>
        </div>
        <p>Откройте Telegram и найдите <code>@BotFather</code>. Нажмите <code>/start</code>, затем выполните <code>/newbot</code>.</p>
        <p>BotFather попросит 2 значения: имя бота (например, <code>My Shop Bot</code>) и username бота (должен заканчиваться на <code>bot</code>, например <code>my_shop_test_bot</code>).</p>
        <p>После создания BotFather отправит <strong>HTTP API token</strong> вида <code>123456789:AA...</code>. Скопируйте этот токен и никому его не передавайте.</p>
        <img src="/help/start-step-2-botfather-ui.svg" alt="Создание бота в BotFather" loading="lazy">
      </article>

      <article class="step-card">
        <div class="step-top">
          <span class="step-badge">Шаг 3</span>
          <h3>Создайте магазин и подключите бота</h3>
        </div>
        <p>Перейдите на <code>https://e-tgo.ru/create-shop</code>, заполните поля магазина и вставьте токен в поле <code>Токен Telegram бота</code>.</p>
        <p>После сохранения откройте настройки магазина и нажмите <strong>Подключить бота</strong>. Статус должен стать <strong>Бот готов</strong>.</p>
        <p>Если токен неверный, подключение не пройдет: вернитесь в <code>@BotFather</code>, откройте <code>/mybots</code> и проверьте токен через <code>API Token</code>.</p>
        <img src="/help/start-step-3-shop-ui.svg" alt="Создание магазина и подключение бота" loading="lazy">
      </article>
    </div>
  </section>
</template>

<script setup>
import axios from 'axios'
import { useAuth } from '../../composables/useAuth'

const { user } = useAuth()

const restartOnboarding = async () => {
  try {
    await axios.post('/api/profile/onboarding/reset')
  } catch (error) {
    console.warn('Failed to reset onboarding on server:', error)
  }

  const userId = user.value?.id ? String(user.value.id) : 'guest'
  if (userId) {
    localStorage.removeItem(`dashboard_onboarding_done_${userId}`)
  }

  window.dispatchEvent(new Event('dashboard:open-onboarding'))
}
</script>

<style scoped>
.page {
  display: grid;
  gap: 1rem;
}

.page-head h1 {
  margin: 0;
  color: #0f2a52;
}

.page-head p {
  margin: 0.2rem 0 0;
  color: #4b5d79;
}

.intro-card {
  border: 1px solid #d6dff1;
  border-radius: 12px;
  background: #fff;
  padding: 1rem;
}

.intro-card p {
  margin: 0;
  color: #2a4267;
}

.intro-actions {
  margin-top: 0.8rem;
  display: flex;
  gap: 0.55rem;
  flex-wrap: wrap;
}

.action-btn {
  text-decoration: none;
  border: 1px solid #c6d4ed;
  border-radius: 10px;
  padding: 0.5rem 0.8rem;
  background: #f4f8ff;
  color: #1d3762;
}

.action-btn.primary {
  background: #2563eb;
  color: #fff;
  border-color: #2563eb;
}

.steps {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.step-card {
  border: 1px solid #d6dff1;
  border-radius: 12px;
  background: #fff;
  padding: 0.95rem;
}

.step-top {
  display: grid;
  gap: 0.35rem;
}

.step-badge {
  display: inline-flex;
  width: fit-content;
  border-radius: 999px;
  border: 1px solid #bfd4f8;
  padding: 0.2rem 0.56rem;
  background: #edf5ff;
  color: #2e4f86;
  font-size: 0.82rem;
}

.step-card h3 {
  margin: 0;
  color: #213a62;
}

.step-card p {
  margin: 0.5rem 0 0.65rem;
  color: #4f6280;
}

.step-card img {
  width: 100%;
  height: 170px;
  object-fit: cover;
  border-radius: 10px;
  border: 1px solid #d7e2f4;
  background: #f3f7ff;
}

@media (max-width: 1024px) {
  .steps {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 760px) {
  .steps {
    grid-template-columns: 1fr;
  }
}
</style>

