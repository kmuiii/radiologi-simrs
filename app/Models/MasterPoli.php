<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'code'])]
class MasterPoli extends Model
{
    protected $table = 'master_poli';

    public function pelayanan()
    {
        return $this->hasMany(Pelayanan::class);
    }
}
