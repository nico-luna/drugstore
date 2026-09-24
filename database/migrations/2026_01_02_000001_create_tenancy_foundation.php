<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 80)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 80);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['account_id', 'slug'], 'uq_store_account_slug');
            $table->index(['account_id', 'is_active'], 'idx_store_account_active');
        });

        Schema::create('account_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->foreignId('default_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('role', 30)->default('staff');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['account_id', 'user_id'], 'uq_account_user');
            $table->index(['user_id', 'is_active'], 'idx_account_user_active');
            $table->foreign('user_id', 'fk_account_user_user')
                ->references('idusuario')->on('usuario')->cascadeOnDelete();
        });

        Schema::create('store_user', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->primary(['store_id', 'user_id']);
            $table->index('user_id', 'idx_store_user_user');
            $table->foreign('user_id', 'fk_store_user_user')
                ->references('idusuario')->on('usuario')->cascadeOnDelete();
        });

        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Cuenta principal',
            'slug' => 'cuenta-principal',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $storeId = DB::table('stores')->insertGetId([
            'account_id' => $accountId,
            'name' => 'Casa Central',
            'slug' => 'casa-central',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('cliente', function (Blueprint $table) use ($accountId) {
            $table->foreignId('account_id')->default($accountId)->after('idcliente')
                ->constrained('accounts')->restrictOnDelete();
            $table->index(['account_id', 'estado'], 'idx_cliente_account_estado');
        });

        Schema::table('producto', function (Blueprint $table) use ($accountId) {
            $table->foreignId('account_id')->default($accountId)->after('codproducto')
                ->constrained('accounts')->restrictOnDelete();
            $table->dropUnique('uq_producto_codigo');
            $table->unique(['account_id', 'codigo'], 'uq_producto_account_codigo');
            $table->index(['account_id', 'estado'], 'idx_producto_account_estado');
        });

        Schema::table('ventas', function (Blueprint $table) use ($accountId, $storeId) {
            $table->foreignId('account_id')->default($accountId)->after('id')
                ->constrained('accounts')->restrictOnDelete();
            $table->foreignId('store_id')->default($storeId)->after('account_id')
                ->constrained('stores')->restrictOnDelete();
            $table->index(['account_id', 'store_id', 'fecha'], 'idx_ventas_context_fecha');
        });

        $legacySettings = DB::table('configuracion')->first();

        Schema::create('configuracion_tenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->string('nombre', 100)->default('');
            $table->string('telefono', 30)->default('');
            $table->string('email', 190)->default('');
            $table->string('direccion', 255)->default('');
            $table->timestamp('actualizado_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('store_id', 'uq_configuracion_store');
            $table->index('account_id', 'idx_configuracion_account');
        });

        DB::table('configuracion_tenant')->insert([
            'account_id' => $accountId,
            'store_id' => $storeId,
            'nombre' => $legacySettings?->nombre ?? 'Drugstore',
            'telefono' => $legacySettings?->telefono ?? '',
            'email' => $legacySettings?->email ?? '',
            'direccion' => $legacySettings?->direccion ?? '',
            'actualizado_at' => $legacySettings?->actualizado_at ?? now(),
        ]);

        Schema::drop('configuracion');
        Schema::rename('configuracion_tenant', 'configuracion');

        $users = DB::table('usuario')->get(['idusuario', 'es_admin']);
        foreach ($users as $user) {
            DB::table('account_user')->insert([
                'account_id' => $accountId,
                'user_id' => $user->idusuario,
                'default_store_id' => $storeId,
                'role' => $user->es_admin ? 'owner' : 'staff',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('store_user')->insert([
                'store_id' => $storeId,
                'user_id' => $user->idusuario,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $settings = DB::table('configuracion')->orderBy('id')->first();

        Schema::drop('configuracion');
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
            'nombre' => $settings?->nombre ?? 'Drugstore',
            'telefono' => $settings?->telefono ?? '',
            'email' => $settings?->email ?? '',
            'direccion' => $settings?->direccion ?? '',
            'actualizado_at' => $settings?->actualizado_at ?? now(),
        ]);

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex('idx_ventas_context_fecha');
            $table->dropConstrainedForeignId('store_id');
            $table->dropConstrainedForeignId('account_id');
        });

        Schema::table('producto', function (Blueprint $table) {
            $table->dropIndex('idx_producto_account_estado');
            $table->dropUnique('uq_producto_account_codigo');
            $table->unique('codigo', 'uq_producto_codigo');
            $table->dropConstrainedForeignId('account_id');
        });

        Schema::table('cliente', function (Blueprint $table) {
            $table->dropIndex('idx_cliente_account_estado');
            $table->dropConstrainedForeignId('account_id');
        });

        Schema::dropIfExists('store_user');
        Schema::dropIfExists('account_user');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('accounts');
    }
};
