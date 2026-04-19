<template>
  <section class="page">
    <header class="page-head">
      <h1>Помощь</h1>
      <p>Поддержка</p>
    </header>

    <div class="cards">
      <article class="card">
        <h3>Поддержка</h3>
        <p>Связаться с поддержкой через Telegram.</p>
        <div class="manager-link-placeholder"></div>
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

.manager-link-placeholder {
  min-height: 20px;
}

@media (max-width: 760px) {
  .cards {
    grid-template-columns: 1fr;
  }
}
</style>
