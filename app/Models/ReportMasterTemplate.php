<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['code', 'template_name', 'uraian', 'is_active'])]
class ReportMasterTemplate extends Model
{
    protected $table = 'report_master_templates';

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted()
    {
        static::creating(function ($item) {
            if (empty($item->code)) {
                $item->code = static::generateCode();
            }
        });
    }

    protected static function generateCode(): string
    {
        $prefix = now()->format('ymd');
        $sequence = static::count();

        do {
            $sequence++;
            $candidate = $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
        } while (static::where('code', $candidate)->exists());

        return $candidate;
    }
}
