<template>
  <aside class="sidebar" :class="{ collapsed }">
    <button class="collapse-btn" type="button" @click="$emit('toggle-collapse')">
      {{ collapsed ? '›' : '‹' }}
    </button>

    <div v-if="!collapsed && quickActions.length" class="quick-actions">
      <router-link
        v-for="action in quickActions"
        :key="action.key"
        :to="action.to"
        class="quick-btn"
      >
        <span class="quick-icon">{{ action.icon }}</span>
        <span>{{ action.label }}</span>
      </router-link>
    </div>

    <nav class="menu">
      <section
        v-for="group in groups"
        :key="group.key"
        class="menu-group"
      >
        <p v-if="!collapsed" class="group-title">{{ group.title }}</p>

        <router-link
          v-for="item in group.items"
          :key="item.key"
          :to="item.to"
          class="menu-item"
          :title="item.label"
        >
          <span class="menu-icon">{{ item.icon }}</span>
          <span v-if="!collapsed" class="menu-label">{{ item.label }}</span>
        </router-link>
      </section>
    </nav>
  </aside>
</template>

<script setup>
defineProps({
  groups: {
    type: Array,
    default: () => [],
  },
  quickActions: {
    type: Array,
    default: () => [],
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['toggle-collapse'])
</script>

<style scoped>
.sidebar {
  position: sticky;
  top: 0.9rem;
  height: calc(100vh - 1.8rem);
  width: 240px;
  padding: 0.8rem;
  border-radius: 16px;
  border: 1px solid rgba(151, 166, 207, 0.25);
  background: #f4f6fc;
  transition: width 180ms ease;
  overflow-y: auto;
}

.sidebar.collapsed {
  width: 86px;
}

.collapse-btn {
  width: 30px;
  height: 30px;
  margin: 0 auto 0.8rem;
  display: grid;
  place-items: center;
  border: 1px solid #d2daec;
  border-radius: 999px;
  background: #fff;
  color: #334155;
  cursor: pointer;
}

.menu {
  display: grid;
  gap: 0.7rem;
}

.quick-actions {
  display: grid;
  gap: 0.42rem;
  margin-bottom: 0.8rem;
}

.quick-btn {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  min-height: 36px;
  padding: 0 0.62rem;
  border-radius: 9px;
  text-decoration: none;
  background: #dbe8ff;
  color: #0f2a52;
  font-size: 0.88rem;
  font-weight: 600;
}

.quick-btn:hover {
  background: #d0e1ff;
}

.quick-icon {
  width: 20px;
  text-align: center;
}

.menu-group {
  display: grid;
  gap: 0.32rem;
}

.group-title {
  margin: 0 0 0.18rem;
  padding: 0 0.45rem;
  color: #6b7c97;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.68rem;
  font-weight: 700;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.7rem 0.65rem;
  color: #344256;
  border-radius: 10px;
  text-decoration: none;
}

.menu-item:hover {
  background: #e6ecf9;
}

.menu-item.router-link-active {
  background: #dbe8ff;
  color: #132952;
  font-weight: 600;
}

.menu-icon {
  width: 26px;
  height: 26px;
  display: grid;
  place-items: center;
  font-size: 1.05rem;
}

.menu-label {
  font-size: 1rem;
}

@media (max-width: 980px) {
  .sidebar {
    position: static;
    width: 100%;
    height: auto;
  }

  .sidebar.collapsed {
    width: 100%;
  }

  .collapse-btn {
    display: none;
  }

  .menu {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.45rem;
  }

  .menu-group {
    display: contents;
  }

  .group-title {
    display: none;
  }

  .quick-actions {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .menu-label {
    font-size: 1rem;
  }
}
</style>
