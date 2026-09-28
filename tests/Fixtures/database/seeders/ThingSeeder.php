<?php

namespace Database\Seeders;

use App\Models\Thing;
use Illuminate\Database\Seeder;

class ThingSeeder extends Seeder
{
    public function run(): void
    {
        Thing::factory()->count(3)->create();
        Thing::create(['title' => 'Seeded thing']);
    }
}
