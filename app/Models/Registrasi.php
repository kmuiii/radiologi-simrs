<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['patient_id', 'no_registrasi', 'tanggal_registrasi'])]
class Registrasi extends Model
{
    protected $table = 'registrasi';

    protected $casts = ['tanggal_registrasi' => 'datetime'];

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
