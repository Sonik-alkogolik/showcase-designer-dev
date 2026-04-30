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
      </div>
    </article>

    <div class="steps">
      <article class="step-card">
        <div class="step-top">
          <span class="step-badge">Шаг 1</span>
          <h3>Привяжите Telegram в профиле</h3>
        </div>
        <p>Откройте профиль, нажмите «Привязать Telegram» и подтвердите связь аккаунта.</p>
        <img src="/help/start-step-1-profile.svg" alt="Привязка Telegram в профиле" loading="lazy">
      </article>

      <article class="step-card">
        <div class="step-top">
          <span class="step-badge">Шаг 2</span>
          <h3>Создайте бота через BotFather</h3>
        </div>
        <p>В BotFather выполните <code>/newbot</code>, задайте имя и получите токен формата <code>123:ABC</code>.</p>
        <img src="/help/start-step-2-botfather.svg" alt="Создание бота в BotFather" loading="lazy">
      </article>

      <article class="step-card">
        <div class="step-top">
          <span class="step-badge">Шаг 3</span>
          <h3>Создайте магазин и подключите бота</h3>
        </div>
        <p>Перейдите на <code>https://e-tgo.ru/create-shop</code>, создайте магазин и вставьте токен в настройки магазина.</p>
        <img src="/help/start-step-3-shop.svg" alt="Создание магазина и подключение бота" loading="lazy">
      </article>
    </div>
  </section>
</template>

<script setup></script>

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
