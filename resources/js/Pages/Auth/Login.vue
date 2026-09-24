<template>
  <div class="login-shell">
    <aside class="login-aside">
      <Link
        class="public-brand inverse"
        href="/"
      >
        <span>D</span>
        Drugstore
      </Link>
      <div>
        <p class="eyebrow">
          Tu operación, siempre en contexto
        </p>
        <h1>Entrá a la tienda correcta y seguí trabajando.</h1>
        <p>Ventas, inventario, usuarios y sucursales desde un único acceso seguro.</p>
      </div>
      <Link href="/">
        ← Volver al sitio
      </Link>
    </aside>

    <main class="login-main">
      <div class="login-card">
        <div class="login-brand">
          Acceso al sistema
        </div>
        <h1>Bienvenido</h1>
        <p class="muted">
          Ingresá con el usuario asignado a tu cuenta.
        </p>

        <div
          v-if="success"
          class="alert success"
          role="status"
        >
          {{ success }}
        </div>
        <div
          v-if="form.errors.usuario"
          class="alert error"
          role="alert"
        >
          {{ form.errors.usuario }}
        </div>

        <form
          class="stacked-form"
          @submit.prevent="submit"
        >
          <div class="form-group">
            <label for="usuario">Usuario</label>
            <input
              id="usuario"
              v-model="form.usuario"
              type="text"
              name="usuario"
              autocomplete="username"
              required
              autofocus
              maxlength="50"
            >
          </div>

          <div class="form-group">
            <label for="clave">Contraseña</label>
            <input
              id="clave"
              v-model="form.clave"
              type="password"
              name="clave"
              autocomplete="current-password"
              required
            >
          </div>

          <div class="login-forgot">
            <Link href="/olvide-mi-clave">
              ¿Olvidaste tu contraseña?
            </Link>
          </div>

          <button
            class="button primary full"
            type="submit"
            :disabled="form.processing"
          >
            {{ form.processing ? 'Ingresando...' : 'Ingresar' }}
          </button>
        </form>
        <p class="login-help">
          ¿Todavía no tenés una cuenta? <Link href="/solicitar-acceso">
            Solicitá acceso
          </Link>.
        </p>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const success = computed(() => page.props.flash?.success);

const form = useForm({
  usuario: '',
  clave: '',
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('clave'),
  });
};
</script>
