<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DetailTindakan;
use App\Models\Tindakan;
use App\Models\JenisTindakan;
use App\Models\ExaminationType;
use App\Models\User;

class DetailTindakanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua tindakan yang termasuk dalam jenis tindakan "Radiologi"
        $radiologiId = JenisTindakan::where('name', 'Radiologi')->first()->id;
        $radiologiTindakan = Tindakan::where('jenis_tindakan_id', $radiologiId)->get(); // 35 tindakan
        $examinationTypes = ExaminationType::all();
        $radiologists = User::where('role', 'radiologist')->pluck('id');

        // Tentukan total detail tindakan yang ingin dibuat
        $totalDetail = 70;
        $distribution = $this->distribute($totalDetail, $radiologiTindakan->count());

        $created = [];

        // Loop melalui setiap tindakan radiologi dan buat detail tindakan sesuai distribusi
        foreach ($radiologiTindakan as $index => $tindakan) {
            $countForThis = $distribution[$index];  // Jumlah detail tindakan untuk tindakan ini

            // Buat detail tindakan untuk tindakan ini
            for ($i = 0; $i < $countForThis; $i++) {
                $examType = $examinationTypes->random();

                $created[] = DetailTindakan::create([
                    'tindakan_id' => $tindakan->id,
                    'examination_type_id' => $examType->id,
                    'radiologist_id' => $radiologists->random(),
                    'price' => $examType->price,
                    'status' => 'pending', // status default
                ]);
            }
        }
    }

    /**
     * Distribusikan $total item ke $buckets secara merata dengan variasi acak,
     * memastikan totalnya tepat.
     */
    protected function distribute(int $total, int $buckets): array
    {
        $base = intdiv($total, $buckets);   // jumlah dasar per bucket
        $remainder = $total % $buckets; // sisa yang harus didistribusikan

        // Buat array dengan jumlah dasar, lalu tambahkan 1 ke beberapa bucket untuk sisa
        $result = array_fill(0, $buckets, $base);
        for ($i = 0; $i < $remainder; $i++) {
            $result[$i]++;
        }

        shuffle($result);   // acak distribusi untuk variasi
        return $result;
    }
}
