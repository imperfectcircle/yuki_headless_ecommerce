<?php

namespace App\Domains\Customer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_profile_id',
        'type',
        'name',
        'company',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'is_default',
    ];

    protected $casts = [
        'customer_profile_id' => 'integer',
        'is_default' => 'boolean',
    ];

    public function customerProfile()
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_profile_id');
    }

    public function toOrderSnapshot(): array
    {
        return [
            'name' => $this->name,
            'company' => $this->company,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'phone' => $this->phone,
        ];
    }

    public function getFormattedAttribute(): string
    {
        $lines = array_filter([
            $this->name,
            $this->company,
            $this->address_line_1,
            $this->address_line_2,
            "{$this->postal_code} {$this->city}",
            $this->state,
            $this->country,
        ]);

        return implode("\n", $lines);
    }

    public function scopeShipping($query)
    {
        return $query->where('type', 'shipping');
    }

    public function scopeBilling($query)
    {
        return $query->where('type', 'billing');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function setAsDefault(): void
    {
        $this->customerProfile
            ->addresses()
            ->where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
