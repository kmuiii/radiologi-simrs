<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Support\Str;

#[Fillable(['patient_id', 'no_registrasi', 'tanggal_registrasi', 'alergi', 'penyakit_khusus', 'jenis_pasien', 'kelas_layanan', 'asal_pasien'])]
class Registrasi extends Model
{
    protected $table = 'registrasi';

    protected $casts = ['tanggal_registrasi' => 'datetime'];

    const JENIS_PASIEN_OPTIONS = [
        'Umum', 'SKTM', 'Prognas', 'Jasa Raharja', 'Program Keluarga Harapan (PKH)',
        'Program DM-TB', 'BPJS - PBI/KIS', 'BPJS - Mandiri', 'BPJS - Askes/Pensiunan', 'Lain-lain',
    ];

    const KELAS_LAYANAN_OPTIONS = ['Kelas I', 'Kelas II', 'Kelas III', 'VIP'];

    const ASAL_PASIEN_OPTIONS = [
        'Datang Sendiri', 'Puskesmas', 'RS Pemerintah', 'RS Swasta',
        'Dokter Praktik', 'Paramedis', 'Khusus Polisi', 'Lain-lain',
    ];
    
    protected static function booted()
    {
        static::creating(function ($item) {
            if (empty($item->no_registrasi)) {
                $item->no_registrasi = static::generateNoRegistrasi();
            }
        });
    }

    // Generate No Registrasi
    protected static function generateNoRegistrasi(): string
    {
        do {
            $candidate = 'REG-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (static::where('no_registrasi', $candidate)->exists());

        return $candidate;
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function pelayanan()
    {
        return $this->hasMany(Pelayanan::class);
    }

    public function tindakan()
    {
        return $this->hasMany(Tindakan::class);
    }
}
