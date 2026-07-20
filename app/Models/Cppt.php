<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['patient_id', 'tanggal_jam', 'lokasi_layanan', 'profesi_pengisi', 'ppa', 'subjective', 'objective', 'assessment', 'plan'])]
class Cppt extends Model
{
    protected $table = 'cppt';

    protected $casts = ['tanggal_jam' => 'datetime'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
