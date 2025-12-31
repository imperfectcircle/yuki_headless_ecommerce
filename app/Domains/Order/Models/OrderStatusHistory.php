<?php

namespace App\Domains\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $table = 'order_status_history';

    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'note',
        'user_id',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'user_id' => 'integer',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================
    
    /**
     * Get the order that owns the status history.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who made the status change.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================
    
    /**
     * Get human-readable description of the status change
     */
    public function getDescription(): string
    {
        return "Status changed from {$this->from_status} to {$this->to_status}";
    }

    /**
     * Check if this was a manual status change (has a user)
     */
    public function isManual(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Check if this was an automated status change
     */
    public function isAutomated(): bool
    {
        return is_null($this->user_id);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================
    
    /**
     * Scope: Get only manual status changes
     */
    public function scopeManual($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope: Get only automated status changes
     */
    public function scopeAutomated($query)
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope: Get changes to a specific status
     */
    public function scopeToStatus($query, string $status)
    {
        return $query->where('to_status', $status);
    }

    /**
     * Scope: Get changes from a specific status
     */
    public function scopeFromStatus($query, string $status)
    {
        return $query->where('from_status', $status);
    }
}
