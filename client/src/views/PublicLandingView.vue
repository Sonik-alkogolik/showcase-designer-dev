<template>
  <section class="guest-landing">
    <div class="ambient ambient-a" />
    <div class="ambient ambient-b" />

    <header class="hero">
      <div class="hero-content">
        <p class="eyebrow">t-go</p>
        <h1>Создавайте магазины в Telegram быстро и без лишней настройки</h1>
        <p class="lead">
          Сервис помогает собрать витрину, показать каталог и принимать заказы в одном кабинете.
          Подходит для старта и для роста действующего магазина.
        </p>

        <div class="hero-actions">
          <router-link class="cta cta-primary" to="/register">Зарегистрироваться</router-link>
          <router-link class="cta cta-ghost" to="/login">Войти</router-link>
        </div>
      </div>

      <div class="hero-demo" aria-hidden="true">
        <div class="demo-window">
          <div class="demo-head">
            <span />
            <span />
            <span />
            <p>Сценарии работы магазина</p>
          </div>
          <div class="demo-viewport">
            <div class="demo-track">
              <div class="demo-slide">
                <div class="demo-body">
                  <div class="admin-panel">
                    <p class="panel-title">Магазин одежды: кабинет</p>
                    <div class="panel-card metrics">
                      <div>
                        <small>Заказы сегодня</small>
                        <strong>24</strong>
                      </div>
                      <div>
                        <small>Выручка</small>
                        <strong>48 900 ₽</strong>
                      </div>
                    </div>
                    <div class="panel-card product-row active">
                      <span>Футболка Classic</span>
                      <em>В наличии</em>
                    </div>
                    <div class="panel-card product-row">
                      <span>Худи Street</span>
                      <em>5 шт.</em>
                    </div>
                    <div class="panel-card product-row">
                      <span>Кроссовки Air</span>
                      <em>12 шт.</em>
                    </div>
                    <div class="panel-card product-row product-row-new">
                      <span>+ Бейсболка Urban</span>
                      <em>Новый товар</em>
                    </div>
                  </div>

                  <div class="telegram-preview">
                    <div class="phone-frame">
                      <div class="phone-top">Telegram</div>
                      <div class="chat-area">
                        <div class="bubble bubble-shop">Новый заказ #2841</div>
                        <div class="bubble bubble-user">Оплатил картой, доставку сегодня</div>
                        <div class="bubble bubble-system">Товар «Бейсболка Urban» добавлен</div>
                        <div class="webapp-card">
                          <p>Mini App: Магазин</p>
                          <div class="webapp-btn">Открыть витрину</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="demo-slide">
                <div class="demo-body demo-body-add">
                  <div class="admin-panel add-product-panel">
                    <p class="panel-title">Магазин одежды: добавление товара</p>
                    <div class="panel-card input-line">
                      <small>Название</small>
                      <strong>Бейсболка Urban</strong>
                    </div>
                    <div class="panel-card input-line">
                      <small>Цена</small>
                      <strong>1 990 ₽</strong>
                    </div>
                    <div class="panel-card input-line">
                      <small>Категория</small>
                      <strong>Аксессуары</strong>
                    </div>
                    <div class="panel-card save-btn">Сохранить товар</div>
                  </div>

                  <div class="telegram-preview stock-preview">
                    <div class="phone-frame">
                      <div class="phone-top">Telegram WebApp</div>
                      <div class="chat-area">
                        <div class="bubble bubble-system">Каталог обновлён</div>
                        <div class="bubble bubble-shop">Новый товар: Бейсболка Urban</div>
                        <div class="webapp-card">
                          <p>Витрина магазина</p>
                          <div class="stock-chip">В наличии: 37</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="demo-dots">
            <span class="dot dot-1" />
            <span class="dot dot-2" />
          </div>
        </div>
      </div>
    </header>

    <section class="about">
      <h2>Кратко о проекте</h2>
      <p>
        t-go - это платформа для запуска и управления продажами в Telegram.
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
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1.25rem;
  z-index: 2;
  max-width: 1120px;
  animation: hero-drift 26s ease-in-out infinite;
}

.hero-content {
  flex: auto;
  min-width: 0;
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
  max-width: 30ch;
  font-size: clamp(1.86rem, 2.2vw, 3.35rem);
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

.hero-demo {
  margin-top: 0.3rem;
  flex: 0 0 clamp(330px, 37vw, 450px);
  width: auto;
  animation: rise-in 700ms 380ms cubic-bezier(.2, .8, .2, 1) both, demo-breathe 14s ease-in-out infinite;
}

.demo-window {
  border-radius: 16px;
  border: 1px solid rgba(169, 181, 255, 0.24);
  background: linear-gradient(180deg, rgba(16, 24, 52, 0.85), rgba(9, 14, 33, 0.9));
  box-shadow: 0 20px 44px rgba(0, 0, 0, 0.35);
  overflow: hidden;
  animation: window-glow 12s ease-in-out infinite;
}

.demo-head {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  min-height: 38px;
  padding: 0 0.85rem;
  border-bottom: 1px solid rgba(176, 187, 255, 0.18);
  background: rgba(255, 255, 255, 0.03);
}

.demo-head span {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: rgba(189, 201, 255, 0.6);
}

.demo-head p {
  margin-left: 0.35rem;
  font-size: 0.83rem;
  color: #d6defd;
}

.demo-viewport {
  overflow: hidden;
}

.demo-track {
  display: flex;
  width: 200%;
  animation: demo-slide-cycle 18s ease-in-out infinite;
}

.demo-slide {
  width: 50%;
}

.demo-body {
  display: grid;
  grid-template-columns: 1.15fr 0.95fr;
  gap: 0.8rem;
  padding: 0.9rem;
}

.admin-panel {
  border-radius: 12px;
  border: 1px solid rgba(173, 185, 255, 0.2);
  background: rgba(2, 8, 21, 0.55);
  padding: 0.68rem;
  display: grid;
  gap: 0.45rem;
  animation: panel-live 16s ease-in-out infinite;
}

.panel-title {
  margin: 0 0 0.25rem;
  font-size: 0.8rem;
  color: #d8e2ff;
}

.panel-card {
  border-radius: 9px;
  border: 1px solid rgba(166, 178, 255, 0.18);
  background: rgba(255, 255, 255, 0.03);
}

.metrics {
  padding: 0.48rem 0.56rem;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}

.metrics small {
  display: block;
  font-size: 0.66rem;
  color: #9eadd9;
}

.metrics strong {
  font-size: 0.78rem;
  color: #ebf1ff;
}

.product-row {
  min-height: 30px;
  padding: 0 0.56rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #bcc7e7;
  position: relative;
  overflow: hidden;
}

.product-row::after {
  content: "";
  position: absolute;
  inset: 0;
  transform: translateX(-120%);
  background: linear-gradient(90deg, transparent, rgba(138, 180, 255, 0.16), transparent);
  animation: row-sweep 10s ease-in-out infinite;
}

.product-row em {
  font-style: normal;
  color: #93a5d7;
}

.product-row.active {
  border-color: rgba(103, 165, 255, 0.42);
  background: rgba(64, 133, 255, 0.18);
  animation: row-pulse 6s ease-in-out infinite;
}

.product-row-new {
  border-color: rgba(87, 198, 255, 0.46);
  background: rgba(45, 174, 230, 0.16);
  animation: product-added 9s ease-in-out infinite;
}

.product-row-new em {
  color: #b5f0ff;
}

.product-row:nth-of-type(3)::after {
  animation-delay: 1.8s;
}

.product-row:nth-of-type(4)::after {
  animation-delay: 3.6s;
}

.telegram-preview {
  border-radius: 12px;
  border: 1px solid rgba(173, 185, 255, 0.2);
  background: rgba(2, 8, 21, 0.55);
  padding: 0.6rem;
  display: grid;
  place-items: center;
}

.phone-frame {
  width: min(190px, 100%);
  border-radius: 14px;
  border: 1px solid rgba(167, 181, 255, 0.28);
  background: linear-gradient(180deg, #0e1736, #0a1228);
  overflow: hidden;
  animation: phone-float 14s ease-in-out infinite;
}

.phone-top {
  min-height: 26px;
  display: grid;
  place-items: center;
  font-size: 0.72rem;
  color: #d6e0ff;
  border-bottom: 1px solid rgba(170, 183, 255, 0.18);
  background: rgba(255, 255, 255, 0.04);
}

.chat-area {
  padding: 0.5rem;
  display: grid;
  gap: 0.4rem;
}

.bubble {
  border-radius: 9px;
  padding: 0.35rem 0.45rem;
  font-size: 0.68rem;
  line-height: 1.25;
  max-width: 95%;
}

.bubble-shop {
  justify-self: start;
  background: rgba(73, 110, 255, 0.32);
  color: #e4ebff;
  animation: bubble-show 8s ease-in-out infinite;
}

.bubble-user {
  justify-self: end;
  background: rgba(42, 182, 242, 0.26);
  color: #ddf5ff;
  animation: bubble-show 8s ease-in-out infinite 2.4s;
}

.bubble-system {
  justify-self: start;
  background: rgba(74, 208, 184, 0.22);
  color: #d9fff5;
  animation: bubble-show 8s ease-in-out infinite 4.2s;
}

.webapp-card {
  margin-top: 0.15rem;
  border-radius: 10px;
  border: 1px solid rgba(159, 175, 255, 0.22);
  background: rgba(255, 255, 255, 0.04);
  padding: 0.42rem;
  animation: bubble-show 8s ease-in-out infinite 4.6s;
}

.webapp-card p {
  margin: 0 0 0.3rem;
  font-size: 0.67rem;
  color: #ccdbff;
}

.webapp-btn {
  height: 24px;
  border-radius: 8px;
  display: grid;
  place-items: center;
  font-size: 0.67rem;
  color: #f4f8ff;
  background: linear-gradient(120deg, #4f63ff, #33c5ff);
  animation: button-breathe 6.5s ease-in-out infinite;
}

.demo-body-add .add-product-panel {
  animation: panel-live 14s ease-in-out infinite;
}

.input-line {
  min-height: 42px;
  padding: 0.35rem 0.56rem;
  display: grid;
  gap: 0.08rem;
  animation: form-fill 8s ease-in-out infinite;
}

.input-line:nth-of-type(3) {
  animation-delay: 1.4s;
}

.input-line:nth-of-type(4) {
  animation-delay: 2.8s;
}

.input-line small {
  font-size: 0.63rem;
  color: #97a6d6;
}

.input-line strong {
  font-size: 0.78rem;
  color: #e9efff;
  font-weight: 600;
}

.save-btn {
  min-height: 34px;
  display: grid;
  place-items: center;
  color: #f5f9ff;
  font-size: 0.75rem;
  font-weight: 700;
  background: linear-gradient(120deg, #4f63ff, #33c5ff);
  border-color: rgba(104, 161, 255, 0.5);
  animation: button-breathe 5.4s ease-in-out infinite;
}

.stock-chip {
  min-height: 24px;
  border-radius: 8px;
  display: grid;
  place-items: center;
  font-size: 0.66rem;
  color: #dff8ff;
  background: rgba(60, 198, 200, 0.28);
  animation: bubble-show 8s ease-in-out infinite 4.8s;
}

.demo-dots {
  min-height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.34rem;
  border-top: 1px solid rgba(168, 180, 255, 0.16);
}

.dot {
  width: 6px;
  height: 6px;
  border-radius: 999px;
  background: rgba(180, 191, 255, 0.38);
}

.dot-1 {
  animation: dot-live-1 18s ease-in-out infinite;
}

.dot-2 {
  animation: dot-live-2 18s ease-in-out infinite;
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
  animation: panel-drift 32s ease-in-out infinite;
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
  animation: tile-drift 24s ease-in-out infinite;
}

.highlight:nth-child(2) {
  animation-duration: 28s;
  animation-delay: -5s;
}

.highlight:nth-child(3) {
  animation-duration: 30s;
  animation-delay: -9s;
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

@keyframes hero-drift {
  0%, 100% { transform: translate3d(0, 0, 0); }
  50% { transform: translate3d(0, -5px, 0); }
}

@keyframes demo-slide-cycle {
  0%, 42% { transform: translateX(0); }
  50%, 92% { transform: translateX(-50%); }
  100% { transform: translateX(0); }
}

@keyframes panel-drift {
  0%, 100% { transform: translate3d(0, 0, 0); }
  50% { transform: translate3d(0, 4px, 0); }
}

@keyframes tile-drift {
  0%, 100% { transform: translate3d(0, 0, 0); }
  50% { transform: translate3d(0, -3px, 0); }
}

@keyframes panel-live {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-2px); }
}

@keyframes row-pulse {
  0%, 100% { filter: brightness(1); }
  50% { filter: brightness(1.15); }
}

@keyframes row-sweep {
  0%, 70%, 100% { transform: translateX(-120%); }
  82%, 92% { transform: translateX(120%); }
}

@keyframes phone-float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-3px); }
}

@keyframes window-glow {
  0%, 100% { box-shadow: 0 20px 44px rgba(0, 0, 0, 0.35); }
  50% { box-shadow: 0 20px 44px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(114, 167, 255, 0.24), 0 0 24px rgba(90, 149, 255, 0.18); }
}

@keyframes demo-breathe {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-2px); }
}

@keyframes bubble-show {
  0%, 20%, 100% { opacity: 0.58; transform: translateY(0); }
  28%, 72% { opacity: 1; transform: translateY(-1px); }
}

@keyframes form-fill {
  0%, 45%, 100% { border-color: rgba(166, 178, 255, 0.18); background: rgba(255, 255, 255, 0.03); }
  55%, 78% { border-color: rgba(102, 167, 255, 0.46); background: rgba(70, 132, 255, 0.13); }
}

@keyframes product-added {
  0%, 55%, 100% { opacity: 0.52; transform: scale(0.985); }
  63%, 85% { opacity: 1; transform: scale(1); }
}

@keyframes button-breathe {
  0%, 100% { filter: brightness(1); transform: scale(1); }
  50% { filter: brightness(1.08); transform: scale(1.02); }
}

@keyframes dot-live-1 {
  0%, 42% { background: rgba(125, 178, 255, 0.9); transform: scale(1.15); }
  50%, 100% { background: rgba(180, 191, 255, 0.38); transform: scale(1); }
}

@keyframes dot-live-2 {
  0%, 42% { background: rgba(180, 191, 255, 0.38); transform: scale(1); }
  50%, 92% { background: rgba(125, 178, 255, 0.9); transform: scale(1.15); }
  100% { background: rgba(180, 191, 255, 0.38); transform: scale(1); }
}

@media (max-width: 860px) {
  .hero {
    flex-direction: column;
  }

  .hero-demo {
    width: min(720px, 100%);
    flex-basis: auto;
  }

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

  .demo-body {
    grid-template-columns: 1fr;
  }
}

@media (prefers-reduced-motion: reduce) {
  .guest-landing,
  .hero,
  .lead,
  .hero-actions,
  .about,
  .highlight,
  .ambient-a,
  .ambient-b {
    animation: none !important;
  }
}
</style>
