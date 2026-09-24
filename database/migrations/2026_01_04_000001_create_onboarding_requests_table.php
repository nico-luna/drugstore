<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_requests', function (Blueprint $table) {
            $table->id();
            $table->string('business_name', 120);
            $table->string('contact_name', 120);
            $table->string('email', 190);
            $table->string('phone', 40)->default('');
            $table->unsignedSmallInteger('store_count')->default(1);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('new');
            $table->string('source_ip', 45)->default('');
            $table->timestamps();

            $table->index(['status', 'created_at'], 'idx_onboarding_status_created');
            $table->index('email', 'idx_onboarding_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_requests');
    }
};
