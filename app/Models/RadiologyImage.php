<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['detail_tindakan_id', 'file_path', 'uploaded_by'])]
class RadiologyImage extends Model
{
    public function detailTindakan()
    {
        return $this->belongsTo(DetailTindakan::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
