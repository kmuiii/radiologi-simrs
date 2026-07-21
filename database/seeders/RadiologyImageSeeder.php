<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RadiologyImage;
use App\Models\TindakanHasil;
use App\Models\User;


class RadiologyImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hasilList = TindakanHasil::with('detailTindakan')->get();  //
        $uploaders = User::where('role', 'radiologist')->pluck('id');

        $totalImages = 140;
        $distribution = $this->distribute($totalImages, $hasilList->count());

        foreach ($hasilList as $index => $hasil) {
            for ($i = 0; $i < $distribution[$index]; $i++) {
                RadiologyImage::create([
                    'detail_tindakan_id' => $hasil->detail_tindakan_id,
                    'file_path' => 'radiology-images/dummy-' . uniqid() . '.jpg',
                    'uploaded_by' => $uploaders->random(),
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
