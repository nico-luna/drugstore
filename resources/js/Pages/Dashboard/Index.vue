<template>
  <AppLayout title="Panel principal">
    <section class="hero-panel">
      <div>
        <p class="eyebrow">
          Resumen operativo
        </p>
        <h2>Hola, {{ user?.nombre }}</h2>
        <p>Gestioná la operación diaria desde un único lugar.</p>
      </div>
      <Link
        v-if="canNuevaVenta"
        class="button primary"
        href="/nueva-venta"
      >
        Registrar venta
      </Link>
    </section>

    <section
      class="stats-grid"
      aria-label="Indicadores"
    >
      <!-- Stat cards will be populated in 1B.6 Dashboard -->
    </section>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PageProps, User } from '@/types';

const page = usePage<PageProps>();
const user = computed<User | null>(() => page.props.auth?.user ?? null);

const canNuevaVenta = computed(() => {
  if (!user.value) return false;
  if (user.value.es_admin) return true;
  return Array.isArray(user.value.permisos) && user.value.permisos.includes('nueva_venta');
});
</script>
