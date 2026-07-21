<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ReportTemplate;
use App\Models\ExaminationType;

class ReportTemplateSeeder extends Seeder
{

    protected array $detailedSections = [
        'THX-PA' => ['Cor', 'Pulmo', 'Pleura', 'Diafragma'],
        'ABD-AP' => ['Hepar', 'Lien', 'Ginjal', 'Vesica Urinaria', 'Psoas Line'],
        'CRN-APLAT' => ['Tulang Cranium', 'Sutura', 'Sella Tursica', 'Sinus Paranasal'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $examinationTypes = ExaminationType::all();

        foreach ($examinationTypes as $type) {
            $sections = $this->detailedSections[$type->code] ?? ['Deskripsi Umum'];

            ReportTemplate::create([
                'examination_type_id' => $type->id,
                'template_name' => $type->name . ' (Default)',
                'template_content' => [
                    'sections' => $sections,
                    'default_findings_structure' => implode("\n", array_map(fn ($s) => "{$s}: ", $sections)),
                ],
                'is_default' => true,
            ]);
        }
    }
}
