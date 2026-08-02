<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[Fillable(['registrasi_id', 'pelayanan_id', 'patient_id', 'jenis_tindakan_id',
    'referring_doctor_name', 'clinical_notes',
    'dokter_pembaca_id', 'operator_1_id', 'operator_2_id', 'operator_3_id', 'operator_4_id', 'operator_5_id',
    'status', 'tgl_datang', 'tgl_valid', 'tgl_periksa', 'tgl_selesai', 'tgl_serah', 'tanggal_jam_diterima', 'penerima', 'status_diterima'])]
class Tindakan extends Model
{
    protected $table = 'tindakan';

    protected $casts = [
        'tgl_datang' => 'datetime', 'tgl_valid' => 'datetime',
        'tgl_periksa' => 'datetime', 'tgl_selesai' => 'datetime', 'tgl_serah' => 'datetime',
        'tanggal_jam_diterima' => 'datetime',
    ];

    const STATUSES = ['order', 'datang', 'valid', 'proses', 'selesai', 'serah'];
    const STATUS_DITERIMA_OPTIONS = ['diterima', 'belum_diterima', 'konfirmasi'];

    public function registrasi() { 
        return $this->belongsTo(Registrasi::class);
    }

    public function pelayanan() { 
        return $this->belongsTo(Pelayanan::class);
    }
        
    public function patient() { 
        return $this->belongsTo(Patient::class); 
    }
        
    public function jenisTindakan() { 
        return $this->belongsTo(JenisTindakan::class); 
    }
        
    public function detailTindakan() { 
        return $this->hasMany(DetailTindakan::class); 
    }

    public function dokterPembaca() { 
        return $this->belongsTo(User::class, 'dokter_pembaca_id'); 
    }

    public function operator1() { 
        return $this->belongsTo(User::class, 'operator_1_id'); 
    }

    public function operator2() { 
        return $this->belongsTo(User::class, 'operator_2_id'); 
    }

    public function operator3() { 
        return $this->belongsTo(User::class, 'operator_3_id'); 
    }

    public function operator4() { 
        return $this->belongsTo(User::class, 'operator_4_id'); 
    }

    public function operator5() { 
        return $this->belongsTo(User::class, 'operator_5_id'); 
    }

}
