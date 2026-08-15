<template>
  <div class="login-page">
    <main class="login-card">
      <div class="login-brand">
        Drugstore
      </div>
      <h1>Iniciar sesión</h1>
      <p class="muted">
        Ingresá para administrar el negocio.
      </p>

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

        <button
          class="button primary full"
          type="submit"
          :disabled="form.processing"
        >
          {{ form.processing ? 'Ingresando...' : 'Ingresar' }}
        </button>
      </form>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

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

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--bg-main, #f8fafc);
  padding: 1.5rem;
}

.login-card {
  width: 100%;
  max-width: 400px;
  background: var(--surface, #ffffff);
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 12px;
  padding: 2.5rem 2rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
}

.login-brand {
  font-size: 0.875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--primary, #2563eb);
  margin-bottom: 0.75rem;
}

h1 {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0 0 0.25rem 0;
  color: var(--text-main, #0f172a);
}

.muted {
  color: var(--text-muted, #64748b);
  margin: 0 0 1.5rem 0;
  font-size: 0.9375rem;
}

.alert.error {
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  margin-bottom: 1.25rem;
  font-size: 0.875rem;
}

.stacked-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-main, #0f172a);
}

input {
  padding: 0.625rem 0.875rem;
  border: 1px solid var(--border, #cbd5e1);
  border-radius: 8px;
  font-size: 0.9375rem;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

input:focus {
  border-color: var(--primary, #2563eb);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.button.primary {
  background-color: var(--primary, #2563eb);
  color: #ffffff;
  border: none;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9375rem;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.button.primary:hover:not(:disabled) {
  background-color: var(--primary-hover, #1d4ed8);
}

.button.primary:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.full {
  width: 100%;
}
</style>
