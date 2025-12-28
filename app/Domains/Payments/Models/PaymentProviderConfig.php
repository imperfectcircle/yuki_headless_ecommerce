<?php

namespace App\Domains\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProviderConfig extends Model
{
    protected $table = 'payment_providers';

    protected $fillable = [
        'code',
        'enabled',
        'position',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'position' => 'integer',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    public function toggle(): void
    {
        $this->update(['enabled' => !$this->enabled]);
    }
}
