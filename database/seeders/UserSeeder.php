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
    }
}
