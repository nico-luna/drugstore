<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->id('idusuario');
            $table->string('nombre', 100);
            $table->string('correo', 190)->unique('uq_usuario_correo');
            $table->string('usuario', 50)->unique('uq_usuario_usuario');
            $table->string('clave', 255);
            $table->boolean('es_admin')->default(false);
            $table->boolean('estado')->default(true);
            $table->timestamp('creado_at')->useCurrent();
            $table->timestamp('actualizado_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('permisos', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('nombre', 50)->unique('uq_permisos_nombre');
            $table->string('etiqueta', 80);
        });

        DB::table('permisos')->insert([
            ['id' => 1, 'nombre' => 'configuracion', 'etiqueta' => 'Configuración'],
            ['id' => 2, 'nombre' => 'usuarios', 'etiqueta' => 'Usuarios'],
            ['id' => 3, 'nombre' => 'clientes', 'etiqueta' => 'Clientes'],
            ['id' => 4, 'nombre' => 'productos', 'etiqueta' => 'Productos'],
            ['id' => 5, 'nombre' => 'ventas', 'etiqueta' => 'Ventas'],
            ['id' => 6, 'nombre' => 'nueva_venta', 'etiqueta' => 'Nueva venta'],
        ]);

        Schema::create('detalle_permisos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('id_permiso');
            $table->unsignedBigInteger('id_usuario');

            $table->unique(['id_permiso', 'id_usuario'], 'uq_permiso_usuario');
            $table->index('id_usuario', 'idx_detalle_permisos_usuario');

            $table->foreign('id_permiso', 'fk_detalle_permisos_permiso')
                ->references('id')->on('permisos')->onDelete('cascade');
            $table->foreign('id_usuario', 'fk_detalle_permisos_usuario')
                ->references('idusuario')->on('usuario')->onDelete('cascade');
        });

        Schema::create('intentos_login', function (Blueprint $table) {
            $table->char('identificador', 64)->primary();
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('ultimo_intento')->useCurrent();
            $table->timestamp('bloqueado_hasta')->nullable();

            $table->index('ultimo_intento', 'idx_intentos_login_ultimo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intentos_login');
        Schema::dropIfExists('detalle_permisos');
        Schema::dropIfExists('permisos');
        Schema::dropIfExists('usuario');
    }
};
