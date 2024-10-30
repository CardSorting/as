<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'amount',
        'status',
        'payment_id',
        'refunded_at'
    ];

    protected $casts = [
        'amount' => 'float',
        'refunded_at' => 'datetime'
    ];

    const STATUSES = [
        'pending',
        'completed',
        'refunded'
    ];

    const ALLOWED_TRANSITIONS = [
        'pending' => ['completed', 'refunded'],
        'completed' => ['refunded'],
        'refunded' => []
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });

        static::saving(function ($order) {
            // Validate status transitions
            if ($order->isDirty('status')) {
                $oldStatus = $order->getOriginal('status');
                $newStatus = $order->status;

                if (!in_array($newStatus, self::STATUSES)) {
                    throw new InvalidArgumentException("Invalid status: {$newStatus}");
                }

                if ($oldStatus && !in_array($newStatus, self::ALLOWED_TRANSITIONS[$oldStatus])) {
                    throw new InvalidArgumentException("Cannot transition from {$oldStatus} to {$newStatus}");
                }
            }

            // Validate amount
            if ($order->isDirty('amount')) {
                if (!is_numeric($order->amount)) {
                    throw new InvalidArgumentException('Order amount must be numeric.');
                }

                if ($order->amount < 0) {
                    throw new InvalidArgumentException('Order amount cannot be negative.');
                }

                // Round to 2 decimal places
                $order->amount = round($order->amount, 2);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    public function getAmountInCentsAttribute(): int
    {
        return (int) round($this->amount * 100);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('Y-m-d');
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function canDownload(): bool
    {
        return $this->status === 'completed';
    }

    public function canTransitionTo(string $status): bool
    {
        if (!in_array($status, self::STATUSES)) {
            return false;
        }

        return in_array($status, self::ALLOWED_TRANSITIONS[$this->status] ?? []);
    }

    public function refund(): void
    {
        if ($this->status !== 'completed') {
            throw new InvalidArgumentException('Only completed orders can be refunded.');
        }

        $this->status = 'refunded';
        $this->refunded_at = now();
        $this->save();
    }

    protected static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(7));
    }

    public function scopeAmountRange($query, float $min, float $max)
    {
        return $query->whereBetween('amount', [$min, $max]);
    }

    public function scopeDateRange($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function setAmountAttribute($value)
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('Order amount must be numeric.');
        }

        $this->attributes['amount'] = round((float) $value, 2);
    }

    public static function statistics()
    {
        return [
            'total_orders' => static::count(),
            'completed_orders' => static::completed()->count(),
            'pending_orders' => static::pending()->count(),
            'refunded_orders' => static::refunded()->count(),
            'total_revenue' => static::completed()->sum('amount'),
            'average_order_value' => static::completed()->avg('amount') ?? 0,
            'recent_orders' => static::recent()->count(),
            'refund_rate' => static::count() > 0 
                ? (static::refunded()->count() / static::count()) * 100 
                : 0
        ];
    }

    public static function totalByUser()
    {
        return static::select('user_id')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_revenue')
            ->selectRaw('COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_orders')
            ->selectRaw('COUNT(CASE WHEN status = "refunded" THEN 1 END) as refunded_orders')
            ->groupBy('user_id')
            ->with('user:id,name,email')
            ->get()
            ->map(function ($stats) {
                return [
                    'user' => $stats->user,
                    'total_orders' => $stats->total_orders,
                    'total_revenue' => $stats->total_revenue ?? 0,
                    'completed_orders' => $stats->completed_orders,
                    'refunded_orders' => $stats->refunded_orders,
                    'average_order_value' => $stats->completed_orders > 0 
                        ? $stats->total_revenue / $stats->completed_orders 
                        : 0
                ];
            });
    }
}
