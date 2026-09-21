<?php

namespace Database\Factories;

use App\Domains\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'telefono' => fake()->phoneNumber(),
            'direccion' => fake()->address(),
            'usuario_id' => null,
            'estado' => true,
        ];
    }
}
