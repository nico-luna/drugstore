<?php

namespace Database\Factories;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'correo' => fake()->unique()->safeEmail(),
            'usuario' => fake()->unique()->userName(),
            'clave' => Hash::make('ClaveSegura123!'),
            'es_admin' => false,
            'estado' => true,
        ];
    }
}
