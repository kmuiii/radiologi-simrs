<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name'])]
class JenisTindakan extends Model
{
    protected $table = 'jenis_tindakan';

    public function tindakan()
    {
        return $this->hasMany(Tindakan::class);
    }
}
