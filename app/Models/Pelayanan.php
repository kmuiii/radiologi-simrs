<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['registrasi_id', 'master_poli_id', 'rujukan_dari_pelayanan_id', 'status'])]
class Pelayanan extends Model
{
    protected $table = 'pelayanan';

    public function registrasi()
    {
        return $this->belongsTo(Registrasi::class);
    
    }

    public function masterPoli()
    {
        return $this->belongsTo(MasterPoli::class);
    }

    public function rujukanDari()
    {
        return $this->belongsTo(Pelayanan::class, 'rujukan_dari_pelayanan_id');
    }

    public function rujukanKe()
    {
        return $this->hasMany(Pelayanan::class, 'rujukan_dari_pelayanan_id');
    }

    public function tindakan()
    {
        return $this->hasMany(Tindakan::class);
    }
}
