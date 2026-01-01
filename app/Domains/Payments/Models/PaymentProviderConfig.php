<?php

namespace App\Domains\Payments\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property bool $enabled
 * @property int $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentProviderConfig enabled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentProviderConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentProviderConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentProviderConfig ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentProviderConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentProviderConfig whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentProviderConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentProviderConfig whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentProviderConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentProviderConfig wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentProviderConfig whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
