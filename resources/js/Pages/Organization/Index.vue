<template>
  <AppLayout title="Organización">
    <div class="organization-grid">
      <section class="panel">
        <h2>Empresa</h2>
        <form
          class="stacked-form"
          @submit.prevent="saveAccount"
        >
          <div class="form-group">
            <label for="account-name">Nombre de la cuenta</label>
            <input
              id="account-name"
              v-model="accountForm.name"
              maxlength="120"
              required
            >
          </div>
          <button
            class="button primary"
            type="submit"
            :disabled="accountForm.processing"
          >
            Guardar empresa
          </button>
        </form>
      </section>

      <section class="panel">
        <h2>Nueva sucursal</h2>
        <form
          class="stacked-form"
          @submit.prevent="createStore"
        >
          <div class="form-group">
            <label for="store-name">Nombre</label>
            <input
              id="store-name"
              v-model="storeForm.name"
              maxlength="120"
              required
            >
          </div>
          <p class="field-help">
            Se crea sin stock y sin productos disponibles para evitar ventas accidentales.
          </p>
          <button
            class="button primary"
            type="submit"
            :disabled="storeForm.processing"
          >
            Crear sucursal
          </button>
        </form>
      </section>
    </div>

    <section class="panel organization-section">
      <h2>Sucursales</h2>
      <div class="table-scroll">
        <table>
          <thead><tr><th>Nombre</th><th>Identificador</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody>
            <tr
              v-for="store in stores"
              :key="store.id"
            >
              <td>{{ store.name }}</td>
              <td>{{ store.slug }}</td>
              <td><StatusBadge :active="store.is_active" /></td>
              <td>
                <button
                  class="link-button"
                  type="button"
                  @click="toggleStore(store.id)"
                >
                  {{ store.is_active ? 'Desactivar' : 'Activar' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="panel organization-section">
      <h2>Miembros y acceso por tienda</h2>
      <div class="member-grid">
        <article
          v-for="member in editableMembers"
          :key="member.id"
          class="member-card"
        >
          <div class="member-heading">
            <div><strong>{{ member.name }}</strong><span>{{ member.email }}</span></div>
            <StatusBadge :active="member.is_active" />
          </div>

          <div class="field-row">
            <div>
              <label :for="`role-${member.id}`">Rol</label>
              <select
                :id="`role-${member.id}`"
                v-model="member.role"
              >
                <option value="owner">
                  Propietario
                </option>
                <option value="admin">
                  Administrador
                </option>
                <option value="staff">
                  Personal
                </option>
              </select>
            </div>
            <div>
              <label :for="`default-${member.id}`">Tienda predeterminada</label>
              <select
                :id="`default-${member.id}`"
                v-model="member.default_store_id"
              >
                <option
                  v-for="store in activeStores"
                  :key="store.id"
                  :value="store.id"
                >
                  {{ store.name }}
                </option>
              </select>
            </div>
          </div>

          <fieldset class="permission-list">
            <legend>Tiendas habilitadas</legend>
            <label
              v-for="store in activeStores"
              :key="store.id"
              class="check-row"
            >
              <input
                v-model="member.store_ids"
                type="checkbox"
                :value="store.id"
              >
              {{ store.name }}
            </label>
          </fieldset>

          <label class="check-row">
            <input
              v-model="member.is_active"
              type="checkbox"
            >
            Acceso activo a la cuenta
          </label>

          <button
            class="button secondary"
            type="button"
            @click="saveMember(member)"
          >
            Guardar acceso
          </button>
        </article>
      </div>
    </section>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

interface StoreItem { id: number; name: string; slug: string; is_active: boolean }
interface MemberItem {
  id: number; user_id: number; name: string; email: string; role: string;
  is_active: boolean; default_store_id: number; store_ids: number[];
}

const props = defineProps<{
  account: { id: number; name: string; role: string };
  stores: StoreItem[];
  members: MemberItem[];
}>();

const accountForm = useForm({ name: props.account.name });
const storeForm = useForm({ name: '' });
const editableMembers = reactive(props.members.map(member => ({ ...member, store_ids: [...member.store_ids] })));
const activeStores = computed(() => props.stores.filter(store => store.is_active));

const saveAccount = () => accountForm.put('/organizacion/cuenta', { preserveScroll: true });
const createStore = () => storeForm.post('/organizacion/tiendas', {
  preserveScroll: true,
  onSuccess: () => storeForm.reset(),
});
const toggleStore = (id: number) => router.post(`/organizacion/tiendas/${id}/toggle`, {}, { preserveScroll: true });
const saveMember = (member: MemberItem) => router.put(`/organizacion/miembros/${member.id}`, {
  role: member.role,
  is_active: member.is_active,
  default_store_id: member.default_store_id,
  store_ids: member.store_ids,
}, { preserveScroll: true });
</script>
