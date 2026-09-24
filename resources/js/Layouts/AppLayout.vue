<template>
  <div class="app-layout">
    <header class="topbar">
      <Link
        class="brand"
        href="/dashboard"
      >
        Drugstore
      </Link>
      <button
        class="nav-toggle"
        type="button"
        aria-controls="main-nav"
        :aria-expanded="mobileNavOpen"
        @click="mobileNavOpen = !mobileNavOpen"
      >
        Menú
      </button>

      <nav
        id="main-nav"
        class="main-nav"
        :class="{ open: mobileNavOpen }"
        aria-label="Navegación principal"
      >
        <Link
          href="/dashboard"
          :class="{ active: currentUrl === '/dashboard' }"
        >
          Inicio
        </Link>
        <Link
          v-if="can('clientes')"
          href="/clientes"
          :class="{ active: currentUrl.startsWith('/clientes') }"
        >
          Clientes
        </Link>
        <Link
          v-if="can('productos')"
          href="/productos"
          :class="{ active: currentUrl.startsWith('/productos') }"
        >
          Productos
        </Link>
        <Link
          v-if="can('nueva_venta')"
          href="/nueva-venta"
          :class="{ active: currentUrl.startsWith('/nueva-venta') }"
        >
          Nueva venta
        </Link>
        <Link
          v-if="can('ventas')"
          href="/ventas"
          :class="{ active: currentUrl.startsWith('/ventas') }"
        >
          Ventas
        </Link>
        <Link
          v-if="can('usuarios')"
          href="/usuarios"
          :class="{ active: currentUrl.startsWith('/usuarios') }"
        >
          Usuarios
        </Link>
        <Link
          v-if="can('configuracion')"
          href="/configuracion"
          :class="{ active: currentUrl.startsWith('/configuracion') }"
        >
          Configuración
        </Link>
      </nav>

      <div class="account">
        <span>{{ user?.nombre }}</span>
        <button
          class="link-button"
          type="button"
          @click="logout"
        >
          Salir
        </button>
      </div>
    </header>

    <section
      v-if="tenant"
      class="workspace-bar"
      aria-label="Contexto de trabajo"
    >
      <div>
        <span>Cuenta</span>
        <select
          :value="tenant.account.id"
          aria-label="Cuenta activa"
          :disabled="tenant.accounts.length < 2"
          @change="changeAccount"
        >
          <option
            v-for="accountItem in tenant.accounts"
            :key="accountItem.id"
            :value="accountItem.id"
          >
            {{ accountItem.name }}
          </option>
        </select>
      </div>
      <div>
        <span>Tienda</span>
        <select
          :value="tenant.store.id"
          aria-label="Tienda activa"
          :disabled="tenant.stores.length < 2"
          @change="changeStore"
        >
          <option
            v-for="storeItem in tenant.stores"
            :key="storeItem.id"
            :value="storeItem.id"
          >
            {{ storeItem.name }}
          </option>
        </select>
      </div>
      <span class="workspace-role">{{ tenant.account.role }}</span>
    </section>

    <main class="page-shell">
      <div
        v-if="title"
        class="page-heading"
      >
        <h1>{{ title }}</h1>
      </div>

      <div
        v-if="flashSuccess"
        class="alert success"
        role="status"
      >
        {{ flashSuccess }}
      </div>
      <div
        v-if="flashError"
        class="alert error"
        role="alert"
      >
        {{ flashError }}
      </div>

      <slot />
    </main>

    <footer class="footer">
      Sistema de gestión Drugstore
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import type { PageProps, TenantContext, User } from '@/types';

defineProps<{
  title?: string;
}>();

const mobileNavOpen = ref(false);
const page = usePage<PageProps>();

const user = computed<User | null>(() => page.props.auth?.user ?? null);
const tenant = computed<TenantContext | null>(() => page.props.tenant ?? null);
const currentUrl = computed(() => page.url);

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const can = (permission: string): boolean => {
  if (!user.value) return false;
  if (user.value.es_admin) return true;
  return Array.isArray(user.value.permisos) && user.value.permisos.includes(permission);
};

const logout = () => {
  router.post('/logout');
};

const changeAccount = (event: Event) => {
  const accountId = Number((event.target as HTMLSelectElement).value);
  router.post('/contexto', { account_id: accountId, store_id: null }, {
    preserveScroll: true,
    preserveState: false,
  });
};

const changeStore = (event: Event) => {
  if (!tenant.value) return;

  router.post('/contexto', {
    account_id: tenant.value.account.id,
    store_id: Number((event.target as HTMLSelectElement).value),
  }, {
    preserveScroll: true,
    preserveState: false,
  });
};
</script>
