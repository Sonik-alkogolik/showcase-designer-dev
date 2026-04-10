<template>
  <section class="page">
    <header class="page-head">
      <h1>Профиль</h1>
      <p>Данные текущего аккаунта и статус Telegram-привязки.</p>
    </header>

    <div v-if="loading" class="empty-box">Загрузка профиля...</div>

    <div v-else-if="!profile" class="empty-box">Не удалось загрузить профиль.</div>

    <article v-else class="profile-card">
      <div class="avatar-wrap">
        <img v-if="resolvedAvatar" :src="resolvedAvatar" alt="avatar" class="avatar">
        <div v-else class="avatar placeholder">{{ initials }}</div>
      </div>
      <div class="meta">
        <h3>{{ profile.name }}</h3>
        <p>{{ profile.email }}</p>
        <p :class="profile.telegram_linked ? 'status-ok' : 'status-off'">
          {{ profile.telegram_linked ? 'Telegram привязан' : 'Telegram не привязан' }}
        </p>
        <p v-if="profile.telegram_username">@{{ profile.telegram_username }}</p>
      </div>
      <div class="actions">
        <router-link to="/profile" class="btn-primary">Открыть полный профиль</router-link>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuth } from '../../composables/useAuth'

const { user, loadProfile } = useAuth()
const loading = ref(false)

const profile = computed(() => user.value)
const resolvedAvatar = computed(() => profile.value?.telegram_avatar_url || profile.value?.avatar_url || '')
const initials = computed(() => {
  const name = String(profile.value?.name || '').trim()
  if (!name) return '👤'
  return name
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('')
})

onMounted(async () => {
  loading.value = true
  try {
    await loadProfile()
  } finally {
    loading.value = false
  }
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

.profile-card {
  border: 1px solid #d6dff1;
  border-radius: 12px;
  background: #fff;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 0.9rem;
  flex-wrap: wrap;
}

.avatar-wrap {
  flex: 0 0 auto;
}

.avatar {
  width: 84px;
  height: 84px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #7aa6ff;
}

.avatar.placeholder {
  display: grid;
  place-items: center;
  font-weight: 700;
  color: #1f365c;
  background: #dbeafe;
}

.meta {
  min-width: 220px;
}

.meta h3 {
  margin: 0;
  color: #122b52;
}

.meta p {
  margin: 0.2rem 0 0;
  color: #3f5272;
}

.status-ok {
  color: #166534;
  font-weight: 600;
}

.status-off {
  color: #991b1b;
  font-weight: 600;
}

.btn-primary {
  text-decoration: none;
  border-radius: 10px;
  padding: 0.55rem 0.72rem;
  color: #fff;
  background: #2563eb;
}

.empty-box {
  border: 1px dashed #c8d3ea;
  border-radius: 12px;
  background: #fff;
  padding: 1rem;
  color: #546480;
}
</style>
