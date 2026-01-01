<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Models\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $attribute_id
 * @property string $code
 * @property string $label
 * @property int $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Attribute $attribute
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttributeOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttributeOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttributeOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttributeOption whereAttributeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttributeOption whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttributeOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttributeOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttributeOption whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttributeOption wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttributeOption whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AttributeOption extends Model
{
    protected $fillable = [
        'attribute_id',
        'code',
        'label',
        'position'
    ];

    protected $casts = [
        'attribute_id' => 'integer',
        'position' => 'integer',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
