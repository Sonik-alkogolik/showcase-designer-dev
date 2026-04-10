<template>
  <section class="page">
    <header class="page-head">
      <h1>Помощь</h1>
      <p>Быстрые ссылки и опорные runbook-файлы.</p>
    </header>

    <div class="cards">
      <article class="card">
        <h3>Быстрый старт</h3>
        <p>Проверить локальную среду и запуск сервиса.</p>
        <a href="/md/DEV_ENV_RUNBOOK.md" target="_blank">Открыть DEV_ENV_RUNBOOK.md</a>
      </article>
      <article class="card">
        <h3>Релиз-процесс</h3>
        <p>Команды push/deploy и правила синхронизации.</p>
        <a href="/md/GIT_RELEASE_WORKFLOW.md" target="_blank">Открыть GIT_RELEASE_WORKFLOW.md</a>
      </article>
      <article class="card">
        <h3>Текущий handoff</h3>
        <p>Где остановились и что делаем следующим шагом.</p>
        <a href="/md/CHAT_HANDOFF.md" target="_blank">Открыть CHAT_HANDOFF.md</a>
      </article>
      <article class="card">
        <h3>Поддержка</h3>
        <p>Связаться с поддержкой через Telegram.</p>
        <a href="https://t.me/tgshopCLO_bot" target="_blank">Открыть Telegram</a>
      </article>
    </div>
  </section>
</template>

<script setup></script>

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

.cards {
  display: grid;
  gap: 0.65rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.card {
  border: 1px solid #d6dff1;
  border-radius: 12px;
  background: #fff;
  padding: 0.86rem;
}

.card h3 {
  margin: 0;
  color: #213a62;
}

.card p {
  margin: 0.35rem 0 0.56rem;
  color: #4f6280;
}

.card a {
  color: #1d4ed8;
  text-decoration: none;
}

@media (max-width: 760px) {
  .cards {
    grid-template-columns: 1fr;
  }
}
</style>
