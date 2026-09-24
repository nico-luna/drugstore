<template>
  <div class="public-site">
    <header class="public-header">
      <Link
        class="public-brand"
        href="/"
      >
        <span>D</span>
        Drugstore
      </Link>
      <nav aria-label="Navegación pública">
        <a href="/#funcionalidades">Funcionalidades</a>
        <a href="/#como-funciona">Cómo funciona</a>
        <Link href="/solicitar-acceso">
          Solicitar acceso
        </Link>
      </nav>
      <Link
        class="button secondary"
        :href="authenticated ? '/dashboard' : '/login'"
      >
        {{ authenticated ? 'Ir al panel' : 'Ingresar' }}
      </Link>
    </header>

    <main>
      <slot />
    </main>

    <footer class="public-footer">
      <div>
        <strong>Drugstore</strong>
        <span>Gestión simple para comercios con una o varias sucursales.</span>
      </div>
      <div>
        <Link href="/login">
          Acceso al sistema
        </Link>
        <Link href="/solicitar-acceso">
          Solicitar una cuenta
        </Link>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const authenticated = computed(() => Boolean(page.props.auth?.user));
</script>
