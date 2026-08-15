<template>
  <span :class="['badge', type || (active ? 'active' : 'inactive')]">
    {{ label || (active ? 'Activo' : 'Inactivo') }}
  </span>
</template>

<script setup lang="ts">
defineProps<{
  active?: boolean;
  type?: 'active' | 'inactive' | 'admin';
  label?: string;
}>();
</script>
