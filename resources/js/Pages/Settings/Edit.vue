<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps<{
  config: {
    nombre: string
    telefono: string
    email: string
    direccion: string
  }
}>()

const form = useForm({
  nombre:    props.config.nombre,
  telefono:  props.config.telefono,
  email:     props.config.email,
  direccion: props.config.direccion,
})

function submit() {
  form.put('/configuracion', { preserveScroll: true })
}
</script>

<template>
  <AppLayout title="Configuracion">
    <div class="page-header">
      <h1>Datos del negocio</h1>
    </div>

    <div class="card narrow-panel">
      <div class="card-body">
        <form
          class="stacked-form"
          @submit.prevent="submit"
        >
          <div class="form-group">
            <label for="nombre">Nombre comercial <span class="text-danger">*</span></label>
            <input
              id="nombre"
              v-model="form.nombre"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': form.errors.nombre }"
              maxlength="100"
              required
            >
            <div
              v-if="form.errors.nombre"
              class="invalid-feedback"
            >
              {{ form.errors.nombre }}
            </div>
          </div>

          <div class="form-group">
            <label for="telefono">Telefono</label>
            <input
              id="telefono"
              v-model="form.telefono"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': form.errors.telefono }"
              maxlength="30"
            >
            <div
              v-if="form.errors.telefono"
              class="invalid-feedback"
            >
              {{ form.errors.telefono }}
            </div>
          </div>

          <div class="form-group">
            <label for="email">Correo</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              class="form-control"
              :class="{ 'is-invalid': form.errors.email }"
              maxlength="190"
            >
            <div
              v-if="form.errors.email"
              class="invalid-feedback"
            >
              {{ form.errors.email }}
            </div>
          </div>

          <div class="form-group">
            <label for="direccion">Direccion</label>
            <input
              id="direccion"
              v-model="form.direccion"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': form.errors.direccion }"
              maxlength="255"
            >
            <div
              v-if="form.errors.direccion"
              class="invalid-feedback"
            >
              {{ form.errors.direccion }}
            </div>
          </div>

          <button
            type="submit"
            class="btn btn-primary"
            :disabled="form.processing"
          >
            Guardar cambios
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>