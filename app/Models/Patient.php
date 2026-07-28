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

    protected static function booted()
    {
        static::creating(function ($item) {
            if (empty($item->mrn)) {
                $item->mrn = static::generateMrn();
            }
        });
    }

    // Generate No Medical Record
    protected static function generateMrn(): string
    {
        do {
            $candidate = 'RM-' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('mrn', $candidate)->exists());

        return $candidate;
    }

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
