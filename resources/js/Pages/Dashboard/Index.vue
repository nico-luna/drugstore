<template>
  <div class="dashboard-page">
    <header class="hero-panel">
      <div>
        <p class="eyebrow">
          Resumen operativo
        </p>
        <h2>Hola, {{ authUser?.nombre || 'Usuario' }}</h2>
        <p>Gestioná la operación diaria desde un único lugar.</p>
      </div>
      <form
        method="post"
        action="/logout"
      >
        <button
          type="submit"
          class="button secondary"
        >
          Cerrar sesión
        </button>
      </form>
    </header>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const authUser = computed(() => {
  const props = page.props as Record<string, any>;
  return props?.auth?.user;
});
</script>

<style scoped>
.dashboard-page {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}
.hero-panel {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  padding: 1.5rem 2rem;
  border-radius: 12px;
  border: 1px solid var(--border, #e2e8f0);
}
.eyebrow {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted, #64748b);
  margin: 0;
}
h2 {
  margin: 0.25rem 0;
}
.button.secondary {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
}
</style>
