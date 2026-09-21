<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { PageProps } from '@/types'

interface SaleRow {
  id: number
  fecha: string
  cliente: string
  vendedor: string
  total: number
  estado: string
}

interface SaleItem {
  producto: string
  cantidad: number
  precio: number
  subtotal: number
}

interface SaleDetail extends SaleRow {
  anulada_at: string | null
  anulada_por: string | null
  items: SaleItem[]
}

const props = defineProps<{
  sales: SaleRow[]
  detail: SaleDetail | null
  desde: string
  hasta: string
}>()

const page = usePage<PageProps>()
const authUser = computed(() => page.props.auth.user)
const hasPermission = (p: string) =>
  authUser.value?.es_admin || (authUser.value?.permisos ?? []).includes(p)

// Date filter form
const filterDesde = ref(props.desde)
const filterHasta = ref(props.hasta)

function applyFilter() {
  router.get('/ventas', { desde: filterDesde.value, hasta: filterHasta.value }, { preserveState: false })
}

function viewSale(id: number) {
  router.get('/ventas', { desde: props.desde, hasta: props.hasta, view: id }, { preserveState: false })
}

function closeDetail() {
  router.get('/ventas', { desde: props.desde, hasta: props.hasta }, { preserveState: false })
}

function cancelSale(id: number) {
  if (!confirm('Confirma que desea anular esta venta? Esta accion no se puede deshacer.')) return
  router.post(`/ventas/${id}/cancel`, {}, {
    onFinish: () => { /* redirect handled by controller */ },
  })
}

function formatMoney(value: number): string {
  return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value)
}
</script>

<template>
  <AppLayout title="Historial de Ventas">
    <div class="page-header">
      <h1>Historial de Ventas</h1>
      <a
        v-if="hasPermission('nueva_venta')"
        href="/nueva-venta"
        class="btn btn-primary"
      >Nueva Venta</a>
    </div>

    <!-- Date filter -->
    <div class="card mb-4">
      <div class="card-body">
        <form
          class="filter-form"
          @submit.prevent="applyFilter"
        >
          <div class="form-group">
            <label for="desde">Desde</label>
            <input
              id="desde"
              v-model="filterDesde"
              type="date"
              class="form-control"
            >
          </div>
          <div class="form-group">
            <label for="hasta">Hasta</label>
            <input
              id="hasta"
              v-model="filterHasta"
              type="date"
              class="form-control"
            >
          </div>
          <button
            type="submit"
            class="btn btn-secondary"
          >
            Filtrar
          </button>
        </form>
      </div>
    </div>

    <!-- Detail panel -->
    <div
      v-if="detail"
      class="card mb-4"
    >
      <div class="card-header">
        <strong>Comprobante #{{ detail.id }}</strong>
        <StatusBadge
          :active="detail.estado === 'confirmada'"
          :label="detail.estado === 'confirmada' ? 'Confirmada' : 'Anulada'"
        />
        <button
          class="btn btn-sm btn-secondary ml-auto"
          @click="closeDetail"
        >
          Cerrar detalle
        </button>
      </div>
      <div class="card-body">
        <dl class="detail-list">
          <dt>Fecha</dt><dd>{{ detail.fecha }}</dd>
          <dt>Cliente</dt><dd>{{ detail.cliente }}</dd>
          <dt>Vendedor</dt><dd>{{ detail.vendedor }}</dd>
          <dt>Total</dt><dd>{{ formatMoney(detail.total) }}</dd>
          <template v-if="detail.estado === 'anulada'">
            <dt>Anulada el</dt><dd>{{ detail.anulada_at }}</dd>
            <dt>Anulada por</dt><dd>{{ detail.anulada_por }}</dd>
          </template>
        </dl>

        <table class="table mt-3">
          <thead>
            <tr>
              <th>Producto</th>
              <th class="text-right">
                Cantidad
              </th>
              <th class="text-right">
                Precio unit.
              </th>
              <th class="text-right">
                Subtotal
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, idx) in detail.items"
              :key="idx"
            >
              <td>{{ item.producto }}</td>
              <td class="text-right">
                {{ item.cantidad }}
              </td>
              <td class="text-right">
                {{ formatMoney(item.precio) }}
              </td>
              <td class="text-right">
                {{ formatMoney(item.subtotal) }}
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td
                colspan="3"
                class="text-right"
              >
                <strong>Total</strong>
              </td>
              <td class="text-right">
                <strong>{{ formatMoney(detail.total) }}</strong>
              </td>
            </tr>
          </tfoot>
        </table>

        <div
          v-if="detail.estado === 'confirmada'"
          class="mt-3"
        >
          <button
            class="btn btn-danger"
            @click="cancelSale(detail.id)"
          >
            Anular venta
          </button>
        </div>
      </div>
    </div>

    <!-- Sales list -->
    <div class="card">
      <div class="card-header">
        <strong>Ventas ({{ sales.length }})</strong>
      </div>
      <div class="card-body p-0">
        <table
          v-if="sales.length"
          class="table"
        >
          <thead>
            <tr>
              <th>#</th>
              <th>Fecha</th>
              <th>Cliente</th>
              <th>Vendedor</th>
              <th class="text-right">
                Total
              </th>
              <th>Estado</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="sale in sales"
              :key="sale.id"
            >
              <td>{{ sale.id }}</td>
              <td>{{ sale.fecha }}</td>
              <td>{{ sale.cliente }}</td>
              <td>{{ sale.vendedor }}</td>
              <td class="text-right">
                {{ formatMoney(sale.total) }}
              </td>
              <td>
                <StatusBadge
                  :active="sale.estado === 'confirmada'"
                  :label="sale.estado === 'confirmada' ? 'Confirmada' : 'Anulada'"
                />
              </td>
              <td>
                <button
                  class="btn btn-sm btn-secondary"
                  @click="viewSale(sale.id)"
                >
                  Ver
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <p
          v-else
          class="p-3 text-muted"
        >
          No hay ventas en el periodo seleccionado.
        </p>
      </div>
    </div>
  </AppLayout>
</template>
