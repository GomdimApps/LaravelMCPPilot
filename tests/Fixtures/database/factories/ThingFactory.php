<?php

namespace Database\Factories;

use App\Models\Thing;
use Illuminate\Database\Eloquent\Factories\Factory;

class ThingFactory extends Factory
{
    protected $model = Thing::class;

    public function definition(): array
    {
        return ['title' => 'fixture'];
    }
}
