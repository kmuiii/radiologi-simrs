<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Cppt;
use App\Models\Patient;

class CpptSeeder extends Seeder
{

    protected array $lokasiSamples = ['Poli Umum', 'IGD', 'Poli Penyakit Dalam', 'Rawat Inap Lt. 2', 'Poli Bedah'];
    protected array $profesiSamples = ['Dokter Umum', 'Dokter Spesialis', 'Perawat', 'Bidan'];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = Patient::all();
        $totalCppt = 120; // Total CPPT entries to create
        $distribution = $this->distribute($totalCppt, $patients->count());

        foreach ($patients as $index => $patient) {
            for ($i = 0; $i < $distribution[$index]; $i++) {
                Cppt::create([
                    'patient_id' => $patient->id,
                    'tanggal_jam' => now()->subDays(rand(0, 90))->subHours(rand(0, 23)),
                    'lokasi_layanan' => collect($this->lokasiSamples)->random(),
                    'profesi_pengisi' => collect($this->profesiSamples)->random(),
                    'ppa' => 'dr. ' . fake()->lastName(),
                    'subjective' => 'Pasien mengeluh ' . fake()->sentence(6),
                    'objective' => 'TD: ' . rand(100, 140) . '/' . rand(70, 90) . ' mmHg, Nadi: ' . rand(60, 100) . 'x/menit.',
                    'assessment' => fake()->sentence(8),
                    'plan' => 'Terapi lanjutan sesuai kondisi, evaluasi ulang bila diperlukan.',
                ]);
            }
        }
    }

    protected function distribute(int $total, int $buckets): array
    {
        $base = intdiv($total, $buckets);
        $remainder = $total % $buckets;

        $result = array_fill(0, $buckets, $base);
        for ($i = 0; $i < $remainder; $i++) {
            $result[$i]++;
        }

        shuffle($result);
        return $result;
    }
}
