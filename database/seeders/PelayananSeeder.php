<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pelayanan;
use App\Models\Registrasi;
use App\Models\MasterPoli;

class PelayananSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $registrasiList = Registrasi::all();
        $poliList = MasterPoli::all();
        $created = [];

        // 60 pelayanan: satu per registrasi
        foreach ($registrasiList as $registrasi) {
            $created[] = Pelayanan::create([
                'registrasi_id' => $registrasi->id,
                'master_poli_id' => $poliList->random()->id,
                'rujukan_dari_pelayanan_id' => null,
                'status' => collect(['pending', 'in_progress', 'completed'])->random(),
            ]);
        }

        // 10 pelayanan tambahan: rujukan dari 10 pelayanan acak
        $refferralSources = collect($created)->random(10);
        foreach ($refferralSources as $source) {
            Pelayanan::create([
                'registrasi_id' => $source->registrasi_id,
                'master_poli_id' => $poliList->random()->id,
                'rujukan_dari_pelayanan_id' => $source->id,
                'status' => collect(['pending', 'in_progress', 'completed'])->random(),
            ]);
        }
    }
}
