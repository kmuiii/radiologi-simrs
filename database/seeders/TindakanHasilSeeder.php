<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TindakanHasil;
use App\Models\DetailTindakan;

class TindakanHasilSeeder extends Seeder
{

    // Sample findings and impressions for seeding
    protected array $findingsSamples = [
        "Cor: Ukuran dan bentuk jantung dalam batas normal.\nPulmo: Tampak infiltrat di perihiler kanan.\nPleura: Tidak tampak penebalan maupun efusi pleura.\nDiafragma: Kedua diafragma licin, sinus costophrenicus tajam.",
        "Hepar: Ukuran normal, permukaan rata, tepi tajam.\nLien: Tidak membesar.\nGinjal: Bentuk dan ukuran kedua ginjal normal, tidak tampak batu.\nVesica Urinaria: Dinding tidak menebal.",
        "Tulang Cranium: Tidak tampak fraktur.\nSutura: Dalam batas normal.\nSella Tursica: Bentuk dan ukuran normal.\nSinus Paranasal: Tidak tampak perselubungan.",
    ];

    protected array $impressionSamples = [
        'Infiltrat perihiler kanan, susp. proses spesifik, cor dalam batas normal.',
        'Tidak tampak kelainan radiologis pada organ abdomen yang tervisualisasi.',
        'Tidak tampak fraktur maupun kelainan pada cranium.',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // pilih  50 dari 70 detail_tindakan untuk diberi hasil (status = completed)
        $selected = DetailTindakan::inRandomOrder()->limit(50)->get();

        // buat TindakanHasil untuk setiap detail_tindakan yang dipilih
        foreach ($selected as $detail) {
            $idx = array_rand($this->findingsSamples);  // ambil index acak untuk findings dan impression

            TindakanHasil::create([
                'detail_tindakan_id' => $detail->id,
                'report_template_id' => $detail->examinationType->reportTemplates()->first()?->id,
                'template_snapshot' => $detail->examinationType->reportTemplates()->first()?->template_content,
                'findings' => $this->findingsSamples[$idx],
                'impression' => $this->impressionSamples[$idx],
                'impression_source' => collect(['manual', 'ai_generated', 'ai_edited'])->random(),
            ]);

            $detail->update(['status' => 'completed']); // update status detail_tindakan menjadi completed
        }

        // update status detail_tindakan yang tidak dipilih menjadi pending atau in_progress
        DetailTindakan::whereNotIn('id', $selected->pluck('id'))
            ->get() // ambil semua detail_tindakan yang tidak dipilih
            ->each(fn ($d) => $d->update(['status' => collect(['pending', 'in_progress'])->random()])); // update status menjadi pending atau in_progress secara acak
    }
}
