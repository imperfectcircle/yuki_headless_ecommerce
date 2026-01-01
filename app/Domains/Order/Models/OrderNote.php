<?php

namespace App\Domains\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $order_id
 * @property string $note
 * @property bool $is_internal
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Domains\Order\Models\Order $order
 * @property-read User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote byUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote customerVisible()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote internal()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote recent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereIsInternal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereUserId($value)
 * @mixin \Eloquent
 */
class OrderNote extends Model
{
    protected $fillable = [
        'order_id',
        'note',
        'is_internal',
        'user_id',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'is_internal' => 'boolean',
        'user_id' => 'integer',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    /**
     * Get the order that owns the note.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who created the note.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================
    
    /**
     * Check if note is visible to customers
     */
    public function isVisibleToCustomer(): bool
    {
        return !$this->is_internal;
    }

    /**
     * Check if note is internal only
     */
    public function isInternalOnly(): bool
    {
        return $this->is_internal;
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================
    
    /**
     * Scope: Get only internal notes
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope: Get only customer-visible notes
     */
    public function scopeCustomerVisible($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope: Get notes by specific user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Recent notes first
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
