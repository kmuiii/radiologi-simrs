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
            'email' => 'admin@techno.test',
            'password' => Hash::make('istimewa'),
            'role' => 'admin'
        ]);

        User::create([
            'name' => 'dr. Andi Pratama, Sp.Rad',
            'email' => 'andi.radiologi@indotechnomedic.test',
            'password' => Hash::make('password'),
            'role' => 'radiologist',
        ]);

        User::create([
            'name' => 'dr. Siti Rahayu, Sp.Rad',
            'email' => 'siti.radiologi@indotechnomedic.test',
            'password' => Hash::make('password'),
            'role' => 'radiologist',
        ]);
    }
}
