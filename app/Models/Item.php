<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    public const STATUS_WAREHOUSE = 'WAREHOUSE';
    public const STATUS_DISTRIBUTION = 'DISTRIBUTION';
    public const STATUS_CUSTOMER_RECEIVED = 'CUSTOMER_RECEIVED';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'item_code',
        'item_name',
        'category',
        'quantity',
        'supplier_id',
        'current_status',
        'sensitive_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class)->orderBy('event_time');
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_WAREHOUSE,
            self::STATUS_DISTRIBUTION,
            self::STATUS_CUSTOMER_RECEIVED,
        ];
    }
}
