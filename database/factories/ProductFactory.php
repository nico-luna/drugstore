<?php

namespace Database\Factories;

use App\Domains\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->ean13(),
            'descripcion' => fake()->words(3, true),
            'precio' => fake()->randomFloat(2, 1, 10000),
            'existencia' => fake()->numberBetween(0, 100),
            'controla_stock' => true,
            'usuario_id' => null,
            'estado' => true,
        ];
    }
}
