<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MasterPoli;

class MasterPoliSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $poli = [
            ['name' => 'Poli Umum', 'code' => 'UMUM'],
            ['name' => 'Poli Gigi', 'code' => 'GIGI'],
            ['name' => 'Poli Anak', 'code' => 'ANAK'],
            ['name' => 'Poli Penyakit Dalam', 'code' => 'PDLM'],
            ['name' => 'Poli Bedah', 'code' => 'BEDAH'],
            ['name' => 'Poli Mata', 'code' => 'MATA'],
            ['name' => 'Poli THT', 'code' => 'THT'],
            ['name' => 'Poli Kulit dan Kelamin', 'code' => 'KULIT'],
        ];

        foreach ($poli as $p) {
            MasterPoli::create($p);
        }
    }
}
