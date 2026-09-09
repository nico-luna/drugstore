<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto', function (Blueprint $table) {
            $table->id('codproducto');
            $table->string('codigo', 50)->unique('uq_producto_codigo');
            $table->string('descripcion', 200);
            $table->decimal('precio', 12, 2)->unsigned();
            $table->unsignedInteger('existencia')->default(0);
            $table->boolean('controla_stock')->default(true);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamp('creado_at')->useCurrent();
            $table->timestamp('actualizado_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['descripcion', 'estado'], 'idx_producto_descripcion_estado');
            $table->index('usuario_id', 'idx_producto_usuario');

            $table->foreign('usuario_id', 'fk_producto_usuario')
                ->references('idusuario')->on('usuario')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto');
    }
};
