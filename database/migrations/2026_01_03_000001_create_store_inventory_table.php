<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->decimal('price', 12, 2)->unsigned();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'product_id'], 'uq_store_inventory_product');
            $table->index(['account_id', 'store_id', 'is_available'], 'idx_inventory_context_available');
            $table->foreign('product_id', 'fk_store_inventory_product')
                ->references('codproducto')->on('producto')->cascadeOnDelete();
        });

        $stores = DB::table('stores')->get(['id', 'account_id']);
        foreach ($stores as $store) {
            DB::table('producto')
                ->where('account_id', $store->account_id)
                ->orderBy('codproducto')
                ->chunk(500, function ($products) use ($store): void {
                    $now = now();
                    $rows = $products->map(static fn ($product): array => [
                        'account_id' => $store->account_id,
                        'store_id' => $store->id,
                        'product_id' => $product->codproducto,
                        'price' => $product->precio,
                        'stock' => $product->existencia,
                        'is_available' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all();

                    if ($rows !== []) {
                        DB::table('store_inventory')->insert($rows);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_inventory');
    }
};
