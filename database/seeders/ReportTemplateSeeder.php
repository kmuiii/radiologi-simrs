<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ReportTemplate;
use App\Models\ExaminationType;

class ReportTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $thorax = ExaminationType::where('code', 'THX-PA')->first();
        ReportTemplate::create([
            'examination_type_id' => $thorax->id,
            'template_name' => 'Thorax Dewasa (Default)',
            'template_content' => [
                'sections' => ['Cor', 'Pulmo', 'Pleura', 'Diafragma'],
                'default_findings_structure' =>
                    "Cor: \nPulmo: \nPleura: \nDiafragma: ",
            ],
            'is_default' => true,
        ]);

        $abdomen = ExaminationType::where('code', 'ABD-AP')->first();
        ReportTemplate::create([
            'examination_type_id' => $abdomen->id,
            'template_name' => 'Abdomen Dewasa (Default)',
            'template_content' => [
                'sections' => ['Hepar', 'Lien', 'Ginjal', 'Vesica Urinaria', 'Psoas Line'],
                'default_findings_structure' =>
                    "Hepar: \nLien: \nGinjal: \nVesica Urinaria: \nPsoas Line: ",
            ],
            'is_default' => true,
        ]);

        $cranium = ExaminationType::where('code', 'CRN-APLAT')->first();
        ReportTemplate::create([
            'examination_type_id' => $cranium->id,
            'template_name' => 'Cranium Dewasa (Default)',
            'template_content' => [
                'sections' => ['Tulang Cranium', 'Sutura', 'Sella Tursica', 'Sinus Paranasal'],
                'default_findings_structure' =>
                    "Tulang Cranium: \nSutura: \nSella Tursica: \nSinus Paranasal: ",
            ],
            'is_default' => true,
        ]);
    }
}
