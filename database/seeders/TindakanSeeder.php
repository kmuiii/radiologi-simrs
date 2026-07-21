<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tindakan;
use App\Models\JenisTindakan;
use App\Models\Pelayanan;

class TindakanSeeder extends Seeder
{

    /**
     * Daftar nama dokter yang merujuk tindakan.
     */
    protected array $referringDoctors = [
        'dr. Budi Santoso (Poli Paru)',
        'dr. Rina Wijaya (IGD)',
        'dr. Andi Kurniawan (Poli Umum)',
        'dr. Lestari Dewi (Poli Penyakit Dalam)',
        'dr. Hendra Gunawan (Poli Bedah)',
    ];

    /**
     * Sampel catatan klinis untuk tindakan.
     */
    protected array $clinicalNotesSamples = [
        'Batuk berkepanjangan 3 minggu, curiga TB paru.',
        'Nyeri perut kanan atas, curiga kolelitiasis.',
        'Medical check-up rutin.',
        'Nyeri punggung bawah kronis.',
        'Trauma jatuh, curiga fraktur.',
        'Sesak napas, evaluasi jantung dan paru.',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pelayananList = Pelayanan::all();
        $radiologiId = JenisTindakan::where('name', 'Radiologi')->first()->id;  // Ambil ID jenis tindakan Radiologi
        $otherJenisIds = JenisTindakan::where('name', '!=', 'Radiologi')->pluck('id');  // Ambil semua jenis tindakan selain Radiologi

        // pick 45 random pelayanan records from 70 for tindakan
        $selected = $pelayananList->random(45);
        
        foreach ($selected as $i => $pelayanan) {
            // 35 pertama: Radiologi (diimplementasikan penuh)
            // 10 sisanya: jenis lain (data referensi, belum ada modul detail)
            $jenisTindakanId = $i < 35 ? $radiologiId : $otherJenisIds->random();  

            Tindakan::create([
                'registrasi_id' => $pelayanan->registrasi_id,
                'pelayanan_id' => $pelayanan->id,
                'patient_id' => $pelayanan->registrasi->patient_id,
                'jenis_tindakan_id' => $jenisTindakanId,
                'referring_doctor_name' => collect($this->referringDoctors)->random(),
                'clinical_notes' => collect($this->clinicalNotesSamples)->random(),
            ]);
        }
    }
}
