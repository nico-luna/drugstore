<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cliente');
            $table->decimal('total', 12, 2)->unsigned();
            $table->unsignedBigInteger('id_usuario');
            $table->enum('estado', ['confirmada', 'anulada'])->default('confirmada');
            $table->timestamp('fecha')->useCurrent();
            $table->timestamp('anulada_at')->nullable();
            $table->unsignedBigInteger('anulada_por')->nullable();

            $table->index('fecha', 'idx_ventas_fecha');
            $table->index('id_cliente', 'idx_ventas_cliente');
            $table->index('id_usuario', 'idx_ventas_usuario');
            $table->index('estado', 'idx_ventas_estado');

            $table->foreign('id_cliente', 'fk_ventas_cliente')
                ->references('idcliente')->on('cliente')->onDelete('restrict');
            $table->foreign('id_usuario', 'fk_ventas_usuario')
                ->references('idusuario')->on('usuario')->onDelete('restrict');
            $table->foreign('anulada_por', 'fk_ventas_anulada_por')
                ->references('idusuario')->on('usuario')->onDelete('restrict');
        });

        Schema::create('detalle_venta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_producto');
            $table->unsignedBigInteger('id_venta');
            $table->unsignedInteger('cantidad');
            $table->decimal('precio', 12, 2)->unsigned();
            $table->decimal('subtotal', 12, 2)->unsigned();

            $table->unique(['id_venta', 'id_producto'], 'uq_detalle_venta_producto');
            $table->index('id_producto', 'idx_detalle_producto');

            $table->foreign('id_producto', 'fk_detalle_venta_producto')
                ->references('codproducto')->on('producto')->onDelete('restrict');
            $table->foreign('id_venta', 'fk_detalle_venta_venta')
                ->references('id')->on('ventas')->onDelete('cascade');
        });

        Schema::create('configuracion', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('nombre', 100)->default('');
            $table->string('telefono', 30)->default('');
            $table->string('email', 190)->default('');
            $table->string('direccion', 255)->default('');
            $table->timestamp('actualizado_at')->useCurrent()->useCurrentOnUpdate();
        });

        DB::table('configuracion')->insert([
            'id' => 1,
            'nombre' => 'Drugstore',
            'telefono' => '',
            'email' => '',
            'direccion' => '',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion');
        Schema::dropIfExists('detalle_venta');
        Schema::dropIfExists('ventas');
    }
};
