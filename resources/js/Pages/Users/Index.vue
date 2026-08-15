<template>
  <AppLayout title="Usuarios">
    <div class="content-grid">
      <!-- Form Panel -->
      <section class="panel form-panel">
        <h2>{{ editing ? 'Editar usuario' : 'Nuevo usuario' }}</h2>

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
            <label for="correo">Correo</label>
            <input
              id="correo"
              v-model="form.correo"
              type="email"
              maxlength="190"
              required
            >
          </div>

          <div class="form-group">
            <label for="usuario">Usuario</label>
            <input
              id="usuario"
              v-model="form.usuario"
              type="text"
              maxlength="50"
              required
              autocomplete="off"
            >
          </div>

          <div class="form-group">
            <label for="clave">
              Contraseña {{ editing ? '(dejar vacía para conservar)' : '' }}
            </label>
            <input
              id="clave"
              v-model="form.clave"
              type="password"
              autocomplete="new-password"
              :required="!editing"
            >
            <p class="field-help">
              Mínimo 12 caracteres, con mayúsculas, minúsculas, números y símbolos.
            </p>
          </div>

          <div
            v-if="currentUser?.es_admin"
            class="form-group"
          >
            <label class="check-row">
              <input
                v-model="form.es_admin"
                type="checkbox"
              >
              Administrador con acceso total
            </label>
          </div>

          <fieldset
            class="permission-list"
            :disabled="form.es_admin"
          >
            <legend>Permisos específicos</legend>
            <label
              v-for="perm in permissions"
              :key="perm.id"
              class="check-row"
            >
              <input
                v-model="form.permisos"
                type="checkbox"
                :value="perm.id"
              >
              {{ perm.etiqueta }}
            </label>
          </fieldset>

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
              href="/usuarios"
            >
              Cancelar
            </Link>
          </div>
        </form>
      </section>

      <!-- Table Panel -->
      <section class="panel table-panel">
        <h2>Listado</h2>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Correo</th>
                <th>Acceso</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="u in users"
                :key="u.idusuario"
              >
                <td>{{ u.nombre }}</td>
                <td>{{ u.usuario }}</td>
                <td>{{ u.correo }}</td>
                <td>
                  <span
                    v-if="u.es_admin"
                    class="badge admin"
                  >Administrador</span>
                  <span
                    v-else
                    class="muted"
                  >Permisos asignados</span>
                </td>
                <td>
                  <StatusBadge :active="Boolean(u.estado)" />
                </td>
                <td class="actions">
                  <Link :href="`/usuarios?edit=${u.idusuario}`">
                    Editar
                  </Link>
                  <button
                    v-if="u.idusuario !== currentUser?.idusuario"
                    class="link-button"
                    type="button"
                    @click="toggleUser(u.idusuario)"
                  >
                    {{ u.estado ? 'Desactivar' : 'Activar' }}
                  </button>
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
import { computed } from 'vue';
import { useForm, usePage, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { PageProps, User } from '@/types';

interface PermissionItem {
  id: number;
  nombre: string;
  etiqueta: string;
}

interface EditingUserData {
  idusuario: number;
  nombre: string;
  correo: string;
  usuario: string;
  es_admin: boolean;
  permisos: number[];
}

const props = defineProps<{
  users: User[];
  permissions: PermissionItem[];
  editing?: EditingUserData | null;
}>();

const page = usePage<PageProps>();
const currentUser = computed(() => page.props.auth?.user);

const form = useForm({
  nombre: props.editing?.nombre || '',
  correo: props.editing?.correo || '',
  usuario: props.editing?.usuario || '',
  clave: '',
  es_admin: props.editing?.es_admin || false,
  permisos: props.editing?.permisos || [],
});

const submit = () => {
  if (props.editing) {
    form.put(`/usuarios/${props.editing.idusuario}`, {
      preserveScroll: true,
      onSuccess: () => form.reset('clave'),
    });
  } else {
    form.post('/usuarios', {
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  }
};

const toggleUser = (id: number) => {
  router.post(`/usuarios/${id}/toggle`, {}, { preserveScroll: true });
};
</script>
