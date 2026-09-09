<template>
  <AppLayout title="Nueva venta">
    <section class="panel sale-panel">
      <div
        v-if="products.length === 0"
        class="empty-state"
      >
        <h2>No hay productos disponibles</h2>
        <p>Creá o activá productos con stock antes de registrar una venta.</p>
        <Link
          v-if="canManageProducts"
          class="button primary"
          href="/productos"
        >
          Ir a productos
        </Link>
      </div>

      <form
        v-else
        class="stacked-form"
        @submit.prevent="submit"
      >
        <div class="sale-client">
          <label for="cliente_id">Cliente</label>
          <select
            id="cliente_id"
            v-model="form.cliente_id"
            required
          >
            <option
              v-for="c in clients"
              :key="c.idcliente"
              :value="c.idcliente"
            >
              {{ c.nombre }}
            </option>
          </select>
        </div>

        <div class="sale-lines-heading">
          <h2>Productos</h2>
          <button
            class="button secondary"
            type="button"
            @click="addLine"
          >
            Agregar producto
          </button>
        </div>

        <div class="sale-lines">
          <div
            v-for="(line, index) in lines"
            :key="index"
            class="sale-line"
          >
            <div>
              <label>
                Producto
                <select
                  v-model="line.producto_id"
                  required
                  @change="updateSubtotal(index)"
                >
                  <option value="">Seleccionar…</option>
                  <option
                    v-for="p in products"
                    :key="p.codproducto"
                    :value="p.codproducto"
                  >
                    {{ p.codigo }} · {{ p.descripcion }} — {{ formatMoney(p.precio) }}
                  </option>
                </select>
              </label>
            </div>

            <div>
              <label>
                Cantidad
                <input
                  v-model.number="line.cantidad"
                  type="number"
                  min="1"
                  max="100000"
                  step="1"
                  required
                  @input="updateSubtotal(index)"
                >
              </label>
            </div>

            <div class="line-subtotal">
              <span>Subtotal</span>
              <strong>{{ formatMoney(line.subtotal) }}</strong>
            </div>

            <button
              class="icon-button"
              type="button"
              aria-label="Quitar producto"
              :disabled="lines.length <= 1"
              @click="removeLine(index)"
            >
              ×
            </button>
          </div>
        </div>

        <div class="sale-summary">
          <span>Total estimado</span>
          <strong>{{ formatMoney(totalEstimated) }}</strong>
        </div>

        <p class="field-help">
          El precio y el stock se validan nuevamente en el servidor al confirmar.
        </p>

        <button
          class="button primary large"
          type="submit"
          :disabled="form.processing || lines.length === 0"
        >
          {{ form.processing ? 'Registrando...' : 'Confirmar venta' }}
        </button>
      </form>
    </section>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PageProps, User } from '@/types';

interface ClientItem {
  idcliente: number;
  nombre: string;
}

interface ProductOption {
  codproducto: number;
  codigo: string;
  descripcion: string;
  precio: number | string;
  existencia: number;
  controla_stock: boolean;
}

interface SaleLineItem {
  producto_id: number | '';
  cantidad: number;
  subtotal: number;
}

const props = defineProps<{
  clients: ClientItem[];
  products: ProductOption[];
}>();

const page = usePage<PageProps>();
const user = computed<User | null>(() => page.props.auth?.user ?? null);

const canManageProducts = computed(() => {
  if (!user.value) return false;
  if (user.value.es_admin) return true;
  return Array.isArray(user.value.permisos) && user.value.permisos.includes('productos');
});

const defaultClient = props.clients.find(c => c.idcliente === 1)?.idcliente || (props.clients[0]?.idcliente ?? 1);

const lines = ref<SaleLineItem[]>([
  { producto_id: '', cantidad: 1, subtotal: 0 },
]);

const form = useForm({
  cliente_id: defaultClient,
  producto_id: [] as number[],
  cantidad: [] as number[],
});

const formatMoney = (val: number | string): string => {
  const num = typeof val === 'string' ? parseFloat(val) : val;
  if (isNaN(num) || num === 0) return '$ 0,00';
  return '$ ' + num.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const updateSubtotal = (index: number) => {
  const line = lines.value[index];
  if (!line.producto_id) {
    line.subtotal = 0;
    return;
  }
  const prod = props.products.find(p => p.codproducto === Number(line.producto_id));
  const price = prod ? (typeof prod.precio === 'string' ? parseFloat(prod.precio) : prod.precio) : 0;
  line.subtotal = (line.cantidad || 0) * price;
};

const addLine = () => {
  lines.value.push({ producto_id: '', cantidad: 1, subtotal: 0 });
};

const removeLine = (index: number) => {
  if (lines.value.length > 1) {
    lines.value.splice(index, 1);
  }
};

const totalEstimated = computed(() => {
  return lines.value.reduce((acc, curr) => acc + (curr.subtotal || 0), 0);
});

const submit = () => {
  form.producto_id = lines.value
    .filter(l => l.producto_id !== '')
    .map(l => Number(l.producto_id));

  form.cantidad = lines.value
    .filter(l => l.producto_id !== '')
    .map(l => Number(l.cantidad));

  form.post('/nueva-venta', {
    preserveScroll: true,
  });
};
</script>
