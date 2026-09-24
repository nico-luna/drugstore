<template>
  <PublicLayout>
    <section class="public-hero">
      <div class="public-hero-copy">
        <p class="eyebrow">
          Operación multitienda, sin planillas cruzadas
        </p>
        <h1>Ventas, stock y equipos conectados en un solo lugar.</h1>
        <p class="public-lead">
          Administrá cada sucursal con su propio inventario y precio, mientras conservás una visión unificada del negocio.
        </p>
        <div class="public-actions">
          <Link
            class="button primary large"
            href="/solicitar-acceso"
          >
            Solicitar acceso
          </Link>
          <Link
            class="button secondary large"
            :href="authenticated ? '/dashboard' : '/login'"
          >
            {{ authenticated ? 'Abrir mi panel' : 'Ya tengo una cuenta' }}
          </Link>
        </div>
        <ul class="public-checks">
          <li>Sin instalación en cada caja</li>
          <li>Accesos por usuario y sucursal</li>
          <li>Preparado para crecer con tu operación</li>
        </ul>
      </div>

      <div
        class="product-preview"
        aria-label="Vista previa del sistema"
      >
        <div class="preview-top">
          <span /><span /><span /><strong>Casa Central</strong>
        </div>
        <div class="preview-body">
          <div class="preview-sidebar">
            <i /><i /><i /><i />
          </div>
          <div class="preview-content">
            <p>Resumen de hoy</p>
            <div class="preview-stats">
              <b>$ 184.350</b><b>47 ventas</b>
            </div>
            <div class="preview-chart">
              <span /><span /><span /><span /><span /><span />
            </div>
            <div class="preview-row">
              <i /><i /><i />
            </div>
          </div>
        </div>
      </div>
    </section>

    <section
      id="funcionalidades"
      class="public-section"
    >
      <div class="section-heading">
        <p class="eyebrow">
          Todo lo esencial
        </p>
        <h2>Una operación ordenada, desde la primera venta.</h2>
        <p>Herramientas concretas para trabajar más rápido y reducir diferencias entre sucursales.</p>
      </div>
      <div class="feature-grid">
        <article><span>01</span><h3>Ventas ágiles</h3><p>Precios validados en el servidor, control de stock y anulaciones con devolución automática.</p></article>
        <article><span>02</span><h3>Inventario por tienda</h3><p>Cada sucursal administra disponibilidad, existencias y precios sin afectar a las demás.</p></article>
        <article><span>03</span><h3>Catálogo compartido</h3><p>Un mismo producto para toda la empresa, con datos comerciales adaptados a cada local.</p></article>
        <article><span>04</span><h3>Roles y permisos</h3><p>Propietarios, administradores y personal con acceso únicamente a las tiendas autorizadas.</p></article>
        <article><span>05</span><h3>Clientes e historial</h3><p>Información centralizada para consultar ventas, responsables, fechas e importes.</p></article>
        <article><span>06</span><h3>Configuración local</h3><p>Datos de contacto y operación independientes para cada sucursal de la cuenta.</p></article>
      </div>
    </section>

    <section
      id="como-funciona"
      class="public-section public-process"
    >
      <div class="section-heading">
        <p class="eyebrow">
          Puesta en marcha acompañada
        </p>
        <h2>De la solicitud a la primera operación.</h2>
      </div>
      <ol>
        <li><strong>Contanos sobre tu negocio</strong><span>Cantidad de sucursales, equipo y forma de trabajo actual.</span></li>
        <li><strong>Preparamos la estructura</strong><span>Cuenta, tiendas, propietario inicial y configuración segura.</span></li>
        <li><strong>Cargás catálogo y equipo</strong><span>Productos, inventario y accesos según cada responsabilidad.</span></li>
        <li><strong>Empezás a operar</strong><span>Con seguimiento inicial y controles antes de la salida definitiva.</span></li>
      </ol>
    </section>

    <section class="public-cta">
      <div>
        <p class="eyebrow">
          Próximo paso
        </p><h2>Organicemos tu operación multitienda.</h2>
      </div>
      <Link
        class="button primary large"
        href="/solicitar-acceso"
      >
        Solicitar una cuenta
      </Link>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps<{ authenticated: boolean }>();
</script>
