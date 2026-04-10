<template>
  <section class="page">
    <header class="page-head">
      <h1>{{ title }}</h1>
      <p>{{ subtitle }}</p>
    </header>

    <div class="cards">
      <article class="card">
        <h3>Состояние</h3>
        <p>Блок готов для подключения данных и API.</p>
      </article>
      <article class="card">
        <h3>Следующий шаг</h3>
        <p>{{ nextStep }}</p>
      </article>
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    required: true,
  },
  nextStep: {
    type: String,
    required: true,
  },
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
  font-size: clamp(1.7rem, 3.5vw, 2.6rem);
}

.page-head p {
  margin: 0.3rem 0 0;
  color: #4b5d79;
}

.cards {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.card {
  border: 1px solid #d5def0;
  border-radius: 12px;
  background: #fff;
  padding: 0.9rem;
}

.card h3 {
  margin: 0 0 0.35rem;
  color: #1e3a6f;
}

.card p {
  margin: 0;
  color: #475975;
}

@media (max-width: 760px) {
  .cards {
    grid-template-columns: 1fr;
  }
}
</style>

