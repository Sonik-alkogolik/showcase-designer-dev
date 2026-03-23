<template>
  <div class="privacy-container">
    <h1>Политика конфиденциальности</h1>
    <p class="updated">Обновлено: 22.03.2026</p>

    <section>
      <h2>1. Какие данные мы собираем</h2>
      <p>
        Мы можем обрабатывать регистрационные данные пользователя (имя, email),
        технические данные сессии, а также данные, необходимые для работы магазина
        и Telegram WebApp.
      </p>
    </section>

    <section>
      <h2>2. Для чего используются данные</h2>
      <p>
        Данные используются для авторизации, отображения и управления магазинами,
        обработки заказов и отправки уведомлений в рамках функциональности сервиса.
      </p>
    </section>

    <section>
      <h2>3. Передача третьим лицам</h2>
      <p>
        Мы не передаём персональные данные третьим лицам, за исключением случаев,
        когда это требуется для работы интеграций (например, Telegram, платёжные
        провайдеры) или прямо предусмотрено законодательством.
      </p>
    </section>

    <section>
      <h2>4. Хранение и защита</h2>
      <p>
        Мы принимаем разумные технические и организационные меры для защиты данных
        от несанкционированного доступа, изменения, раскрытия или уничтожения.
      </p>
    </section>

    <section>
      <h2>5. Контакты</h2>
      <p>
        По вопросам обработки данных вы можете обратиться в поддержку сервиса.
      </p>
    </section>
  </div>
</template>

<style scoped>
.privacy-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 2rem 1rem 3rem;
  color: #ced5ea;
}

h1 {
  margin: 0 0 0.5rem;
  font-size: 2rem;
  color: var(--color-heading);
}

.updated {
  margin: 0 0 1.5rem;
  color: #a7b0ce;
}

section {
  margin-bottom: 1.25rem;
  padding: 1rem;
  border: 1px solid rgba(171, 186, 255, 0.2);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.03);
}

h2 {
  margin: 0 0 0.5rem;
  font-size: 1.1rem;
  color: #edf1ff;
}

p {
  margin: 0;
  line-height: 1.55;
}
</style>
