<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Support\Str;

#[Fillable(['no_order', 'tindakan_id','examination_type_id', 'radiologist_id', 'price', 'status'])]
class DetailTindakan extends Model
{

    protected $table = 'detail_tindakan';

    protected $casts = ['price' => 'decimal:2'];

    protected static function booted()
    {
        static::creating(function ($item) {
            if (empty($item->price)) {
                $item->price = $item->examinationType->price;
            }

            if (empty($item->no_order)) {
                $item->no_order = static::generateNoOrder();
            }
        });
    }

    protected static function generateNoOrder(): string
    {
        do {
            $candidate = 'RAD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (static::where('no_order', $candidate)->exists());

        return $candidate;
    }

    public function tindakan()
    {
        return $this->belongsTo(Tindakan::class);
    }

    public function examinationType()
    {
        return $this->belongsTo(ExaminationType::class);
    }

    public function radiologist()
    {
        return $this->belongsTo(User::class, 'radiologist_id');
    }

    public function hasil()
    {
        return $this->hasOne(TindakanHasil::class);
    }

    public function images()
    {
        return $this->hasMany(RadiologyImage::class);
    }
}
