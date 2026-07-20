<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['mrn', 'nik', 'name', 'gender', 'date_of_birth', 'address', 'phone'])]
class Patient extends Model
{
    use HasFactory;

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function registrasi()
    {
        return $this->hasMany(Registrasi::class);
    }

    public function cppt()
    {
        return $this->hasMany(Cppt::class);
    }

    public function tindakan()
    {
        return $this->hasMany(Tindakan::class);
    }
}
