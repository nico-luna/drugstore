<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente', function (Blueprint $table) {
            $table->id('idcliente');
            $table->string('nombre', 100);
            $table->string('telefono', 30)->default('');
            $table->string('direccion', 200)->default('');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamp('creado_at')->useCurrent();
            $table->timestamp('actualizado_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['nombre', 'estado'], 'idx_cliente_nombre_estado');
            $table->index('usuario_id', 'idx_cliente_usuario');

            $table->foreign('usuario_id', 'fk_cliente_usuario')
                ->references('idusuario')->on('usuario')->onDelete('set null');
        });

        DB::table('cliente')->insert([
            'idcliente' => 1,
            'nombre' => 'Público en general',
            'telefono' => '',
            'direccion' => 'S/D',
            'usuario_id' => null,
            'estado' => true,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente');
    }
};
