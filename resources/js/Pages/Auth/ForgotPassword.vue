<template>
  <div class="login-page">
    <main class="login-card">
      <Link
        class="public-brand"
        href="/"
      >
        <span>D</span>Drugstore
      </Link>
      <p class="eyebrow auth-eyebrow">
        Recuperación de acceso
      </p>
      <h1>Restablecé tu contraseña</h1>
      <p class="muted">
        Ingresá el correo asociado a tu usuario. Si existe, te enviaremos un enlace válido por 60 minutos.
      </p>
      <div
        v-if="success"
        class="alert success"
        role="status"
      >
        {{ success }}
      </div>
      <div
        v-if="form.errors.correo"
        class="alert error"
        role="alert"
      >
        {{ form.errors.correo }}
      </div>
      <form
        class="stacked-form"
        @submit.prevent="submit"
      >
        <div class="form-group">
          <label for="correo">Correo</label>
          <input
            id="correo"
            v-model="form.correo"
            type="email"
            autocomplete="email"
            required
            autofocus
          >
        </div>
        <button
          class="button primary full"
          type="submit"
          :disabled="form.processing"
        >
          Enviar enlace
        </button>
      </form>
      <p class="login-help">
        <Link href="/login">
          Volver al inicio de sesión
        </Link>
      </p>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const success = computed(() => page.props.flash?.success);
const form = useForm({ correo: '' });
const submit = () => form.post('/olvide-mi-clave', { preserveScroll: true });
</script>
