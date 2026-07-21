<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\JenisTindakan;

class JenisTindakanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Laboratorium', 'Radiologi', 'EKG', 'USG', 'Endoscopy'] as $name) {
            JenisTindakan::create(['name' => $name]);
        }
    }
}
