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
            ['name' => 'Thorax AP', 'code' => 'THX-AP', 'price' => 150000],
            ['name' => 'Abdomen AP', 'code' => 'ABD-AP', 'price' => 200000],
            ['name' => 'Abdomen Polos (BNO)', 'code' => 'BNO', 'price' => 180000],
            ['name' => 'Cranium AP/Lateral', 'code' => 'CRN-APLAT', 'price' => 175000],
            ['name' => 'Pelvis AP', 'code' => 'PEL-AP', 'price' => 175000],
            ['name' => 'Vertebra Cervical', 'code' => 'VER-CERV', 'price' => 190000],
            ['name' => 'Vertebra Thoracal', 'code' => 'VER-THOR', 'price' => 190000],
            ['name' => 'Vertebra Lumbal', 'code' => 'VER-LUMB', 'price' => 190000],
            ['name' => 'Extremitas Atas', 'code' => 'EXT-ATAS', 'price' => 140000],
            ['name' => 'Extremitas Bawah', 'code' => 'EXT-BWH', 'price' => 140000],
            ['name' => 'Sinus Paranasal', 'code' => 'SIN-PARA', 'price' => 160000],
            ['name' => 'Mammografi', 'code' => 'MAMMO', 'price' => 350000],
            ['name' => 'IVP (Intravenous Pyelography)', 'code' => 'IVP', 'price' => 450000],
            ['name' => 'Cor Analysis (Thorax Khusus Jantung)', 'code' => 'THX-COR', 'price' => 160000],
            ['name' => 'Schedel AP/Lateral', 'code' => 'SCH-APLAT', 'price' => 175000],
            ['name' => 'Antebrachii AP/Lateral', 'code' => 'ANT-APLAT', 'price' => 140000],
        ];

        foreach ($types as $type) {
            ExaminationType::create($type);
        }
    }
}
