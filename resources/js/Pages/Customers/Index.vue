<template>
  <AppLayout title="Clientes">
    <div class="content-grid">
      <!-- Form Panel -->
      <section class="panel form-panel">
        <h2>{{ editing ? 'Editar cliente' : 'Nuevo cliente' }}</h2>

        <div
          v-if="form.hasErrors"
          class="alert error"
          role="alert"
        >
          <strong>Revisá los datos:</strong>
          <ul>
            <li
              v-for="(err, key) in form.errors"
              :key="key"
            >
              {{ err }}
            </li>
          </ul>
        </div>

        <form
          class="stacked-form"
          @submit.prevent="submit"
        >
          <div class="form-group">
            <label for="nombre">Nombre</label>
            <input
              id="nombre"
              v-model="form.nombre"
              type="text"
              maxlength="100"
              required
            >
          </div>

          <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input
              id="telefono"
              v-model="form.telefono"
              type="text"
              maxlength="30"
            >
          </div>

          <div class="form-group">
            <label for="direccion">Dirección</label>
            <input
              id="direccion"
              v-model="form.direccion"
              type="text"
              maxlength="200"
            >
          </div>

          <div class="button-row">
            <button
              class="button primary"
              type="submit"
              :disabled="form.processing"
            >
              {{ form.processing ? 'Guardando...' : 'Guardar' }}
            </button>
            <Link
              v-if="editing"
              class="button secondary"
              href="/clientes"
            >
              Cancelar
            </Link>
          </div>
        </form>
      </section>

      <!-- Table Panel -->
      <section class="panel table-panel">
        <div class="panel-toolbar">
          <h2>Listado</h2>
          <form
            class="search-form"
            @submit.prevent="performSearch"
          >
            <label
              class="sr-only"
              for="q"
            >Buscar cliente</label>
            <input
              id="q"
              v-model="searchQuery"
              placeholder="Nombre o teléfono"
            >
            <button
              class="button secondary"
              type="submit"
            >
              Buscar
            </button>
          </form>
        </div>

        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="c in customers"
                :key="c.idcliente"
              >
                <td>{{ c.nombre }}</td>
                <td>{{ c.telefono }}</td>
                <td>{{ c.direccion }}</td>
                <td>
                  <StatusBadge :active="Boolean(c.estado)" />
                </td>
                <td class="actions">
                  <Link :href="`/clientes?edit=${c.idcliente}`">
                    Editar
                  </Link>
                  <button
                    v-if="c.idcliente !== 1"
                    class="link-button"
                    type="button"
                    @click="toggleCustomer(c.idcliente)"
                  >
                    {{ c.estado ? 'Desactivar' : 'Activar' }}
                  </button>
                </td>
              </tr>
              <tr v-if="customers.length === 0">
                <td
                  colspan="5"
                  class="empty-state"
                >
                  No hay clientes para mostrar.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

interface CustomerItem {
  idcliente: number;
  nombre: string;
  telefono: string;
  direccion: string;
  estado: boolean;
}

const props = defineProps<{
  customers: CustomerItem[];
  editing?: CustomerItem | null;
  search?: string;
}>();

const searchQuery = ref(props.search || '');

const form = useForm({
  nombre: props.editing?.nombre || '',
  telefono: props.editing?.telefono || '',
  direccion: props.editing?.direccion || '',
});

const submit = () => {
  if (props.editing) {
    form.put(`/clientes/${props.editing.idcliente}`, {
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  } else {
    form.post('/clientes', {
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  }
};

const performSearch = () => {
  router.get('/clientes', { q: searchQuery.value }, { preserveState: true });
};

const toggleCustomer = (id: number) => {
  router.post(`/clientes/${id}/toggle`, {}, { preserveScroll: true });
};
</script>
