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
                1. Susun Impression dalam Bahasa Indonesia, mengikuti gaya baku laporan radiologi: kalimat/frasa singkat dan padat, BUKAN paragraf naratif panjang.
                2. Setiap poin ditulis di baris terpisah, masing-masing diakhiri tanda titik.
                3. Sebutkan temuan utama (kelainan, jika ada) di baris pertama, diikuti temuan pendukung/negatif relevan di baris berikutnya.
                4. Jangan membuat diagnosis baru yang tidak didukung Findings.
                5. Jangan menambahkan interpretasi klinis di luar apa yang tertulis di Findings.
                6. Gunakan istilah medis radiologi yang baku dan singkat.

                Contoh gaya penulisan yang diharapkan:

                Kasus Normal:
                Foto toraks dalam batas normal.
                Tidak tampak kelainan radiologis pada jantung dan paru.

                Kasus Kelainan/Patologis:
                Gambaran radiologis sesuai dengan suspek pneumonia dextra.
                Tak tampak kardiomegali.

                Kasus Trauma:
                Fraktur transversal pada 1/3 tengah os femur sinistra.
                Tak tampak dislokasi sendi di sekitarnya.

                Kembalikan HANYA teks Impression sesuai format di atas (baris-baris kalimat singkat), tanpa judul, tanpa penjelasan tambahan, tanpa markdown.
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

                Tugas Anda, dalam Bahasa Indonesia:
                1. Periksa apakah Findings dan Impression sudah konsisten satu sama lain.
                2. Periksa apakah terdapat istilah medis yang kurang baku, sarankan perbaikannya.
                3. Periksa apakah terdapat typo/kesalahan penulisan.
                4. Jangan membuat diagnosis baru.
                5. Jangan mengubah isi laporan secara otomatis — hanya berikan rekomendasi.

                Aturan status untuk setiap kategori (consistency, terminology, typo):
                - "status": "ok" jika tidak ditemukan masalah pada kategori tersebut.
                - "status": "warning" jika ditemukan masalah yang perlu diperhatikan dokter.
                - "message" WAJIB diisi pada kedua kondisi: jika "ok", tulis konfirmasi singkat (misal "Findings dan Impression konsisten."); jika "warning", jelaskan masalahnya secara ringkas dan spesifik (sebutkan bagian yang bermasalah).

                Kembalikan HANYA dalam format JSON valid tanpa markdown code block, dengan struktur PERSIS seperti berikut (jangan tambah atau kurangi key):
                {
                "consistency": {"status": "ok", "message": "..."},
                "terminology": {"status": "ok", "message": "..."},
                "typo": {"status": "ok", "message": "..."},
                "recommendation": "..."
                }
                PROMPT,
            ]
        );
    }
}
