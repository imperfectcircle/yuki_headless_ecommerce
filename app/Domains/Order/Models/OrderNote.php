<?php

namespace App\Domains\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

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
