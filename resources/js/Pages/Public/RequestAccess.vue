<template>
  <PublicLayout>
    <section class="request-page">
      <div class="request-copy">
        <p class="eyebrow">
          Solicitud de acceso
        </p>
        <h1>Preparemos una cuenta para tu negocio.</h1>
        <p>En esta etapa el alta es acompañada. Revisamos la estructura de sucursales y te contactamos para configurar el acceso inicial.</p>
        <ul class="public-checks vertical">
          <li>Cuenta y primera sucursal configuradas</li>
          <li>Usuario propietario inicial</li>
          <li>Validación previa a la puesta en marcha</li>
        </ul>
      </div>

      <section class="panel request-card">
        <div
          v-if="success"
          class="alert success"
          role="status"
        >
          {{ success }}
        </div>
        <div
          v-if="form.hasErrors"
          class="alert error"
          role="alert"
        >
          Revisá los datos indicados.
        </div>
        <form
          class="stacked-form"
          @submit.prevent="submit"
        >
          <div class="field-row">
            <div>
              <label for="business">Nombre del negocio</label><input
                id="business"
                v-model="form.business_name"
                maxlength="120"
                required
              >
            </div>
            <div>
              <label for="contact">Persona de contacto</label><input
                id="contact"
                v-model="form.contact_name"
                maxlength="120"
                required
              >
            </div>
          </div>
          <div class="field-row">
            <div>
              <label for="email">Correo</label><input
                id="email"
                v-model="form.email"
                type="email"
                maxlength="190"
                required
              >
            </div>
            <div>
              <label for="phone">Teléfono</label><input
                id="phone"
                v-model="form.phone"
                maxlength="40"
              >
            </div>
          </div>
          <div class="form-group">
            <label for="stores">Cantidad de sucursales</label><input
              id="stores"
              v-model.number="form.store_count"
              type="number"
              min="1"
              max="500"
              required
            >
          </div>
          <div class="form-group">
            <label for="notes">¿Qué necesitás resolver?</label><textarea
              id="notes"
              v-model="form.notes"
              maxlength="2000"
              rows="5"
            />
          </div>
          <button
            class="button primary large"
            type="submit"
            :disabled="form.processing"
          >
            {{ form.processing ? 'Enviando…' : 'Enviar solicitud' }}
          </button>
        </form>
      </section>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const success = computed(() => page.props.flash?.success);
const form = useForm({ business_name: '', contact_name: '', email: '', phone: '', store_count: 1, notes: '' });
const submit = () => form.post('/solicitar-acceso', { preserveScroll: true, onSuccess: () => form.reset() });
</script>
