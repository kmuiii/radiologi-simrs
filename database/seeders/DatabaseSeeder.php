<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            PatientSeeder::class,
            MasterPoliSeeder::class,
            JenisTindakanSeeder::class,
            ExaminationTypeSeeder::class,
            ReportTemplateSeeder::class,
            RegistrasiSeeder::class,
            PelayananSeeder::class,
            TindakanSeeder::class,
            DetailTindakanSeeder::class,
            TindakanHasilSeeder::class,
            RadiologyImageSeeder::class,
            CpptSeeder::class,
        ]);
    }
}
