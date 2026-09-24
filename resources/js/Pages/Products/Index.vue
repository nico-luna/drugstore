<template>
  <AppLayout title="Productos">
    <div class="content-grid">
      <!-- Form Panel -->
      <section class="panel form-panel">
        <h2>{{ editing ? 'Editar producto' : 'Nuevo producto' }}</h2>

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
            <label for="codigo">Código</label>
            <input
              id="codigo"
              v-model="form.codigo"
              type="text"
              maxlength="50"
              required
            >
          </div>

          <div class="form-group">
            <label for="descripcion">Descripción</label>
            <input
              id="descripcion"
              v-model="form.descripcion"
              type="text"
              maxlength="200"
              required
            >
          </div>

          <div class="field-row">
            <div class="form-group">
              <label for="precio">Precio</label>
              <input
                id="precio"
                v-model="form.precio"
                type="number"
                min="0"
                max="9999999999.99"
                step="0.01"
                required
              >
            </div>
            <div class="form-group">
              <label for="existencia">Existencia</label>
              <input
                id="existencia"
                v-model="form.existencia"
                type="number"
                min="0"
                step="1"
                required
              >
            </div>
          </div>

          <div class="form-group">
            <label class="check-row">
              <input
                v-model="form.controla_stock"
                type="checkbox"
              >
              Descontar stock en cada venta
            </label>
          </div>

          <div class="form-group">
            <label class="check-row">
              <input
                v-model="form.is_available"
                type="checkbox"
              >
              Disponible para venta en esta tienda
            </label>
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
              href="/productos"
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
            >Buscar producto</label>
            <input
              id="q"
              v-model="searchQuery"
              placeholder="Código o descripción"
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
                <th>Código</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Tienda</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="p in products"
                :key="p.codproducto"
              >
                <td>{{ p.codigo }}</td>
                <td>{{ p.descripcion }}</td>
                <td>{{ formatMoney(p.precio) }}</td>
                <td>
                  <span v-if="p.controla_stock">{{ p.existencia }}</span>
                  <span
                    v-else
                    class="muted"
                  >Sin control</span>
                </td>
                <td>
                  <StatusBadge
                    :active="Boolean(p.is_available)"
                    :label="p.is_available ? 'Disponible' : 'No disponible'"
                  />
                </td>
                <td>
                  <StatusBadge :active="Boolean(p.estado)" />
                </td>
                <td class="actions">
                  <Link :href="`/productos?edit=${p.codproducto}`">
                    Editar
                  </Link>
                  <button
                    class="link-button"
                    type="button"
                    @click="toggleProduct(p.codproducto)"
                  >
                    {{ p.estado ? 'Desactivar' : 'Activar' }}
                  </button>
                </td>
              </tr>
              <tr v-if="products.length === 0">
                <td
                  colspan="7"
                  class="empty-state"
                >
                  No hay productos para mostrar.
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

interface ProductItem {
  codproducto: number;
  codigo: string;
  descripcion: string;
  precio: number | string;
  existencia: number;
  controla_stock: boolean;
  estado: boolean;
  is_available: boolean;
}

const props = defineProps<{
  products: ProductItem[];
  editing?: ProductItem | null;
  search?: string;
}>();

const searchQuery = ref(props.search || '');

const form = useForm({
  codigo: props.editing?.codigo || '',
  descripcion: props.editing?.descripcion || '',
  precio: props.editing?.precio !== undefined ? props.editing.precio : '',
  existencia: props.editing?.existencia !== undefined ? props.editing.existencia : 0,
  controla_stock: props.editing ? Boolean(props.editing.controla_stock) : true,
  is_available: props.editing ? Boolean(props.editing.is_available) : true,
});

const formatMoney = (val: number | string): string => {
  const num = typeof val === 'string' ? parseFloat(val) : val;
  if (isNaN(num)) return '$ 0,00';
  return '$ ' + num.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const submit = () => {
  if (props.editing) {
    form.put(`/productos/${props.editing.codproducto}`, {
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  } else {
    form.post('/productos', {
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  }
};

const performSearch = () => {
  router.get('/productos', { q: searchQuery.value }, { preserveState: true });
};

const toggleProduct = (id: number) => {
  router.post(`/productos/${id}/toggle`, {}, { preserveScroll: true });
};
</script>
