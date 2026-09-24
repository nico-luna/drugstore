<template>
  <AppLayout title="Solicitudes de acceso">
    <section class="panel">
      <div class="panel-toolbar">
        <div>
          <h2>Bandeja de onboarding</h2>
          <p class="muted">
            Revisá y clasificá las solicitudes antes de crear una cuenta.
          </p>
        </div>
        <span class="badge admin">{{ requests.length }} solicitudes</span>
      </div>

      <div class="request-admin-grid">
        <article
          v-for="item in requests"
          :key="item.id"
          class="request-admin-card"
        >
          <div class="member-heading">
            <div>
              <strong>{{ item.business_name }}</strong>
              <span>{{ item.contact_name }} · {{ item.email }}</span>
            </div>
            <span :class="['badge', statusType(item.status)]">{{ statusLabel(item.status) }}</span>
          </div>
          <dl class="detail-grid compact">
            <div><dt>Sucursales</dt><dd>{{ item.store_count }}</dd></div>
            <div><dt>Teléfono</dt><dd>{{ item.phone || '—' }}</dd></div>
            <div><dt>Fecha</dt><dd>{{ formatDate(item.created_at) }}</dd></div>
          </dl>
          <p
            v-if="item.notes"
            class="request-notes"
          >
            {{ item.notes }}
          </p>
          <div class="request-status-control">
            <label :for="`status-${item.id}`">Estado</label>
            <select
              :id="`status-${item.id}`"
              :value="item.status"
              @change="updateStatus(item.id, $event)"
            >
              <option value="new">
                Nueva
              </option>
              <option value="contacted">
                Contactada
              </option>
              <option value="approved">
                Aprobada
              </option>
              <option value="rejected">
                Descartada
              </option>
            </select>
          </div>
        </article>
        <p
          v-if="requests.length === 0"
          class="empty-state"
        >
          Todavía no hay solicitudes.
        </p>
      </div>
    </section>
  </AppLayout>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

interface RequestItem {
  id: number; business_name: string; contact_name: string; email: string; phone: string;
  store_count: number; notes: string | null; status: string; created_at: string;
}

defineProps<{ requests: RequestItem[] }>();

const statusLabel = (status: string) => ({ new: 'Nueva', contacted: 'Contactada', approved: 'Aprobada', rejected: 'Descartada' }[status] || status);
const statusType = (status: string) => status === 'approved' ? 'active' : status === 'rejected' ? 'inactive' : 'admin';
const formatDate = (date: string) => new Date(date).toLocaleDateString('es-AR');
const updateStatus = (id: number, event: Event) => router.put(`/plataforma/solicitudes/${id}`, {
  status: (event.target as HTMLSelectElement).value,
}, { preserveScroll: true });
</script>
