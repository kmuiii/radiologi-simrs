<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'code', 'price'])]
class ExaminationType extends Model
{
    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function reportTemplates()
    {
        return $this->hasMany(ReportTemplate::class);
    }

    public function tindakan()
    {
        return $this->hasMany(Tindakan::class);
    }


}
