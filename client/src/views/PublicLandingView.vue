<template>
  <section class="guest-landing">
    <div class="ambient ambient-a" />
    <div class="ambient ambient-b" />

    <header class="hero">
      <p class="eyebrow">Showcase Designer</p>
      <h1>Создавайте магазины в Telegram быстро и без лишней настройки</h1>
      <p class="lead">
        Сервис помогает собрать витрину, показать каталог и принимать заказы в одном кабинете.
        Подходит для старта и для роста действующего магазина.
      </p>

      <div class="hero-actions">
        <router-link class="cta cta-primary" to="/register">Зарегистрироваться</router-link>
        <router-link class="cta cta-ghost" to="/login">Войти</router-link>
      </div>
    </header>

    <section class="about">
      <h2>Кратко о проекте</h2>
      <p>
        Showcase Designer - это платформа для запуска и управления продажами в Telegram.
        Вы управляете товарами, ценами и магазинами из одного места, а клиенты получают удобный WebApp-интерфейс.
      </p>

      <div class="highlights">
        <article class="highlight">
          <h3>Быстрый запуск</h3>
          <p>Создание магазина и публикация каталога занимают несколько минут.</p>
        </article>
        <article class="highlight">
          <h3>Удобное управление</h3>
          <p>Добавляйте товары, обновляйте цены и контролируйте витрину в одном кабинете.</p>
        </article>
        <article class="highlight">
          <h3>Готово для Telegram</h3>
          <p>Проект изначально ориентирован на работу через Telegram WebApp.</p>
        </article>
      </div>
    </section>
  </section>
</template>

<style scoped>
.guest-landing {
  position: relative;
  overflow: hidden;
  padding: 3rem 0 4.4rem;
  animation: fade-in 640ms ease both;
}

.hero {
  position: relative;
  z-index: 2;
  max-width: 860px;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.78rem;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--color-muted);
  margin-bottom: 1rem;
}

.hero h1 {
  margin: 0;
  max-width: 17ch;
  font-size: clamp(2rem, 5vw, 3.7rem);
  line-height: 1.07;
  color: var(--color-heading);
  animation: rise-in 700ms 120ms cubic-bezier(.2, .8, .2, 1) both;
}

.lead {
  margin-top: 1.2rem;
  max-width: 58ch;
  color: #b8c0d9;
  font-size: 1.02rem;
  animation: rise-in 700ms 220ms cubic-bezier(.2, .8, .2, 1) both;
}

.hero-actions {
  margin-top: 1.7rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
  animation: rise-in 700ms 300ms cubic-bezier(.2, .8, .2, 1) both;
}

.cta {
  min-height: 44px;
  border-radius: 999px;
  padding: 0 1.1rem;
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
}

.cta:hover {
  transform: translateY(-2px);
}

.cta-primary {
  color: #f5f8ff;
  background: linear-gradient(120deg, #4f63ff, #33c5ff);
  box-shadow: 0 14px 32px rgba(63, 128, 255, 0.3);
}

.cta-ghost {
  border: 1px solid rgba(184, 193, 255, 0.34);
  color: #e1e6ff;
  background: rgba(255, 255, 255, 0.03);
}

.about {
  margin-top: 2.8rem;
  position: relative;
  z-index: 2;
  border: 1px solid rgba(188, 196, 255, 0.17);
  border-radius: 20px;
  padding: 1.3rem 1.2rem;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.02));
  backdrop-filter: blur(8px);
}

.about h2 {
  margin: 0;
  color: var(--color-heading);
  font-size: clamp(1.24rem, 2.3vw, 1.7rem);
}

.about > p {
  margin-top: 0.7rem;
  color: #b2bddf;
  max-width: 80ch;
}

.highlights {
  margin-top: 1rem;
  display: grid;
  gap: 0.8rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.highlight {
  border-radius: 14px;
  border: 1px solid rgba(177, 187, 255, 0.16);
  background: rgba(6, 11, 24, 0.45);
  padding: 0.9rem;
}

.highlight h3 {
  margin: 0;
  font-size: 1rem;
  color: #ebefff;
}

.highlight p {
  margin-top: 0.4rem;
  color: #adb7d6;
  font-size: 0.93rem;
}

.ambient {
  position: absolute;
  border-radius: 999px;
  filter: blur(18px);
  opacity: 0.34;
  z-index: 1;
}

.ambient-a {
  width: 320px;
  height: 320px;
  right: -95px;
  top: -90px;
  background: #4a5dff;
  animation: float-a 9s ease-in-out infinite;
}

.ambient-b {
  width: 260px;
  height: 260px;
  left: -80px;
  bottom: 4%;
  background: #0ea5e9;
  animation: float-b 11s ease-in-out infinite;
}

@keyframes fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes rise-in {
  from {
    opacity: 0;
    transform: translateY(14px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes float-a {
  0%, 100% { transform: translateY(0) translateX(0); }
  50% { transform: translateY(16px) translateX(-8px); }
}

@keyframes float-b {
  0%, 100% { transform: translateY(0) translateX(0); }
  50% { transform: translateY(-14px) translateX(11px); }
}

@media (max-width: 920px) {
  .highlights {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .guest-landing {
    padding-top: 2.1rem;
  }

  .hero-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .highlights {
    grid-template-columns: 1fr;
  }
}
</style>
