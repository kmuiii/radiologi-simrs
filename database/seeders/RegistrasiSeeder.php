<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Registrasi;
use App\Models\Patient;

class RegistrasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = Patient::all(); // Ambil semua pasien yang sudah dibuat oleh PatientSeeder

        // 50 registasi: satu per pasien
        foreach ($patients as $i => $patient) {
            Registrasi::create([
                'patient_id' => $patient->id,
                'no_registrasi' =>'REG' . now()->format('Ymd') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),   // Format no_registrasi: REGYYYYMMDD-XXXX
                'tanggal_registrasi' => now()->subDays(rand(0, 60)),    // Tanggal registrasi acak dalam 60 hari terakhir
            ]);
        }

        // 10 registrasi tambahan: kunjungan berulang untuk 10 pasien acak
        $repeatPatients = $patients->random(10);
        foreach ($repeatPatients as $i => $patient) {
            Registrasi::create([
                'patient_id' => $patient->id,
                'no_registrasi' =>'REG' . now()->format('Ymd') . '-' . str_pad($i + 51, 4, '0', STR_PAD_LEFT),  // Mulai dari 51 untuk menghindari duplikasi no_registrasi
                'tanggal_registrasi' => now()->subDays(rand(0, 30)),    // Tanggal registrasi acak dalam 30 hari terakhir
            ]);
        }
    }
}
