<?php

namespace App\Domains\Pricing\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'symbol',
        'name',
        'precision',
        'is_active',
    ];

    protected $casts = [
        'precision' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function formatAmount(int $amount): string
    {
        $value = $amount / pow(10, $this->precision);
        return number_format($value, $this->precision);
    }

    public function format(int $amount): string
    {
        return $this->symbol . ' ' . $this->formatAmount($amount);
    }
}
