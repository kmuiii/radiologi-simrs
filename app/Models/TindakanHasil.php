<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Fillable(['detail_tindakan_id', 'report_template_id', 'template_snapshot', 'findings', 'impression', 'impression_source'])]
class TindakanHasil extends Model
{
    protected $table = 'tindakan_hasil';

    protected $casts = [
        'template_snapshot' => 'array'
    ];

    public function detailTindakan()
    {
        return $this->belongsTo(DetailTindakan::class);
    }

    public function reportTemplate(){
        return $this->belongsTo(ReportTemplate::class);
    }
}
