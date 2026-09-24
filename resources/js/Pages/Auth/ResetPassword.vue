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
        Nuevo acceso
      </p>
      <h1>Elegí una contraseña</h1>
      <p class="muted">
        Debe tener al menos 12 caracteres, mayúsculas, minúsculas, números y símbolos.
      </p>
      <div
        v-if="form.hasErrors"
        class="alert error"
        role="alert"
      >
        <p
          v-for="(error, key) in form.errors"
          :key="key"
        >
          {{ error }}
        </p>
      </div>
      <form
        class="stacked-form"
        @submit.prevent="submit"
      >
        <div class="form-group">
          <label for="correo">Correo</label><input
            id="correo"
            v-model="form.correo"
            type="email"
            required
          >
        </div>
        <div class="form-group">
          <label for="password">Nueva contraseña</label><input
            id="password"
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            required
          >
        </div>
        <div class="form-group">
          <label for="confirmation">Repetir contraseña</label><input
            id="confirmation"
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
            required
          >
        </div>
        <button
          class="button primary full"
          type="submit"
          :disabled="form.processing"
        >
          Actualizar contraseña
        </button>
      </form>
    </main>
  </div>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{ token: string; email: string }>();
const form = useForm({ token: props.token, correo: props.email, password: '', password_confirmation: '' });
const submit = () => form.post('/restablecer-clave', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>
