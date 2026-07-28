<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin SIMRS',
            'username' => 'superadmin',
            'password' => Hash::make('istimewa'),
            'role' => 'admin'
        ]);

        $radiologists = [
            'dr. Andi Pratama, Sp.Rad',
            'dr. Siti Rahayu, Sp.Rad',
            'dr. Budi Wijaya, Sp.Rad',
            'dr. Maya Kusuma, Sp.Rad',
            'dr. Rio Hakim, Sp.Rad',
        ];

        foreach ($radiologists as $i => $name) {
            User::create([
                'name' => $name,
                'username' => 'radiolog' . ($i + 1),
                'password' => Hash::make('password'),
                'role' => 'radiologist'
            ]);
        }

        $dokterPoli = [
            'dr. Hendra Gunawan',
            'dr. Lestari Dewi',
            'dr. Fajar Nugroho',
            'dr. Ayu Permata',
            'dr. Doni Saputra',
            'dr. Winda Sari',
            'dr. Bayu Pradana',
            'dr. Mega Lestari',
            'dr. Rina Wijaya',
            'dr. Eko Kurniawan',
            'dr. Sari Puspita',
            'dr. Budi Utomo',
            'dr. Cahaya Hikari',
            'dr. Dian Lestari',
            'dr. Rahmat Higayat',
            'dr. Denis Setiawan',
            'dr. Arif Wijaya',
            'dr. Fitri Handayani',
            'dr. Senku',
            'dr. Edi Santoso',
            'dr. Susi Susanti',
            'dr. Yanto',
        ];

        foreach ($dokterPoli as $i => $name) {
            User::create([
                'name' => $name,
                'username' => 'dokter' . ($i + 1),
                'password' => Hash::make('password'),
                'role' => 'dokter',
            ]);
        }
    }
}
