<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportTemplate extends Model
{
    protected $fillable = [
        'examination_type_id',
        'template_name',
        'template_content',
        'is_default'
    ];

    protected $casts = [
        'template_content' => 'array',
        'is_default' => 'boolean'
    ];

    public function examinationType()
    {
        return $this->belongsTo(ExaminationType::class);
    }

    public function tindakanHasil()
    {
        return $this->hasMany(TindakanHasil::class);
    }
}
