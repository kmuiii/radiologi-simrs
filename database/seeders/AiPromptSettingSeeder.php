<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AiPromptSetting;

class AiPromptSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AiPromptSetting::updateOrCreate(
            ['key' => 'impression_generator'],
            [
                'label' => 'AI Impression Generator',
                'required_tokens' => ['{{examination_type}}', '{{clinical_notes}}', '{{findings}}'],
                'prompt_text' => <<<PROMPT
                Anda adalah asisten dokter radiologi yang membantu menyusun bagian Impression (kesimpulan) laporan radiologi berdasarkan Findings yang diberikan.

                Jenis Pemeriksaan: {{examination_type}}
                Keterangan Klinis: {{clinical_notes}}
                Findings:
                {{findings}}

                Tugas Anda:
                1. Susun kalimat Impression yang ringkas berdasarkan pola pada Findings di atas.
                2. Jangan membuat diagnosis baru yang tidak didukung Findings.
                3. Jangan menambahkan interpretasi klinis di luar apa yang tertulis di Findings.
                4. Gunakan istilah medis radiologi yang baku dan singkat, sesuai gaya penulisan Impression pada laporan radiologi.

                Kembalikan hanya teks Impression, tanpa penjelasan tambahan.
                PROMPT,
            ]
        );

        AiPromptSetting::updateOrCreate(
            ['key' => 'ai_review'],
            [
                'label' => 'AI Report Quality Checker (Review)',
                'required_tokens' => ['{{findings}}', '{{impression}}'],
                'prompt_text' => <<<PROMPT
                Anda adalah seorang dokter radiologi senior yang bertugas melakukan review kualitas laporan radiologi.

                Findings:
                {{findings}}

                Impression:
                {{impression}}

                Tugas Anda:
                1. Periksa apakah Findings dan Impression sudah konsisten.
                2. Periksa apakah terdapat istilah medis yang kurang baku, sarankan perbaikannya.
                3. Periksa dan perbaiki typo jika ditemukan.
                4. Jangan membuat diagnosis baru.
                5. Jangan mengubah isi laporan secara otomatis, hanya berikan rekomendasi.

                Kembalikan HANYA dalam format JSON valid tanpa markdown code block, dengan struktur persis seperti berikut:
                {
                "consistency": "...",
                "terminology": "...",
                "typo": "...",
                "recommendation": "..."
                }
                PROMPT,
            ]
        );
    }
}
