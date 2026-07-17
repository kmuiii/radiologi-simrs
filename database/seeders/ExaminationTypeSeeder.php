<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ExaminationType;

class ExaminationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Thorax PA', 'code' => 'THX-PA', 'price' => 150000],
            ['name' => 'Thorax Lateral', 'code' => 'THX-LAT', 'price' => 150000],
            ['name' => 'Abdomen AP', 'code' => 'ABD-AP', 'price' => 200000],
            ['name' => 'Cranium AP/Lateral', 'code' => 'CRN-APLAT', 'price' => 175000],
        ];

        foreach ($types as $type) {
            ExaminationType::create($type);
        }
    }
}
