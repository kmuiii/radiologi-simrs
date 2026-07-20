<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[Fillable(['registrasi_id', 'pelayanan_id', 'patient_id', 'jenis_tindakan_id', 'referring_doctor_name', 'clinical_notes'])]
class Tindakan extends Model
{
    protected $table = 'tindakan';

    public function registrasi()
    {
        return $this->belongsTo(Registrasi::class);
    }

    public function pelayanan()
    {
        return $this->belongsTo(Pelayanan::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function jenisTindakan()
    {
        return $this->belongsTo(JenisTindakan::class);
    }

    public function detailTindakan()
    {
        return $this->hasMany(DetailTindakan::class);
    }

    /**
     * Status agregat dari seluruh detail_tindakan
     */
    
    protected function status():Attribute
    {
        return Attribute::make(
            get: function (){
                $statuses = $this->detailTindakan->pluck('status');

                if ($statuses->isEmpty()) {
                    return 'pending';
                }

                return $statuses->every(fn($s) => $s === 'completed')
                    ? 'completed'
                    : ($statuses->contains('in_progress') ? 'in_progress' : 'pending');
            }
        );
    }

}
