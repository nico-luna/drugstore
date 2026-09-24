<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('usuario', function (Blueprint $table) {
            $table->boolean('is_platform_admin')->default(false)->after('es_admin');
        });

        $firstAdminId = DB::table('usuario')
            ->where('es_admin', true)
            ->where('estado', true)
            ->orderBy('idusuario')
            ->value('idusuario');

        if ($firstAdminId !== null) {
            DB::table('usuario')->where('idusuario', $firstAdminId)->update(['is_platform_admin' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('is_platform_admin');
        });
        Schema::dropIfExists('password_reset_tokens');
    }
};
