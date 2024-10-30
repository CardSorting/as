<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'cover_image',
        'file_path',
        'is_published',
        'user_id',
        'slug',
        'views'
    ];

    protected $casts = [
        'price' => 'float',
        'is_published' => 'boolean',
        'views' => 'integer'
    ];

    protected $appends = [
        'formatted_price',
        'url'
    ];

    const ALLOWED_FILE_TYPES = [
        'pdf', 'doc', 'docx', 'zip', 'rar',
        'mp3', 'mp4', 'mov', 'avi',
        'jpg', 'jpeg', 'png', 'gif'
    ];

    const ALLOWED_IMAGE_TYPES = [
        'jpg', 'jpeg', 'png', 'gif'
    ];

    const MAX_FILE_SIZE = 51200; // 50MB in KB
    const MAX_IMAGE_SIZE = 5120; // 5MB in KB

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                if (empty($product->name)) {
                    throw new InvalidArgumentException('Product name is required.');
                }
                $product->slug = static::generateUniqueSlug($product->name);
            }
        });

        static::saving(function ($product) {
            if ($product->isDirty('price') && $product->price < 0) {
                throw new InvalidArgumentException('Product price cannot be negative.');
            }

            if ($product->isDirty('name') && empty($product->name)) {
                throw new InvalidArgumentException('Product name is required.');
            }
        });

        static::deleting(function ($product) {
            // Delete associated files
            if ($product->file_path) {
                Storage::disk('public')->delete($product->file_path);
            }
            if ($product->cover_image) {
                Storage::disk('public')->delete($product->cover_image);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function recent_orders(): HasMany
    {
        return $this->orders()
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc');
    }

    public function generateFilePath(UploadedFile $file): string
    {
        return 'products/' . $this->id . '/' . $file->hashName();
    }

    public function generateCoverImagePath(UploadedFile $file): string
    {
        return 'products/' . $this->id . '/cover/' . $file->hashName();
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    public function getPriceInCentsAttribute(): int
    {
        return (int) round($this->price * 100);
    }

    public function getUrlAttribute(): string
    {
        return url('/products/' . $this->slug);
    }

    public function isValidFile(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $size = $file->getSize() / 1024; // Convert to KB

        return in_array($extension, self::ALLOWED_FILE_TYPES) &&
            $size <= self::MAX_FILE_SIZE;
    }

    public function isValidCoverImage(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $size = $file->getSize() / 1024; // Convert to KB

        return in_array($extension, self::ALLOWED_IMAGE_TYPES) &&
            $size <= self::MAX_IMAGE_SIZE;
    }

    protected static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();
        
        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function getCompletedOrdersCountAttribute(): int
    {
        return $this->orders()->where('status', 'completed')->count();
    }

    public function getTotalRevenueAttribute(): float
    {
        return $this->orders()->where('status', 'completed')->sum('amount');
    }

    public function getAverageOrderValueAttribute(): float
    {
        $completedOrders = $this->completed_orders_count;
        return $completedOrders > 0 ? $this->total_revenue / $completedOrders : 0;
    }

    public function scopePriceRange($query, float $min, float $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    public function scopeOrderByPopularity($query)
    {
        return $query->select('products.*')
            ->selectRaw('COUNT(CASE WHEN orders.status = ? THEN 1 END) as completed_count', ['completed'])
            ->leftJoin('orders', 'products.id', '=', 'orders.product_id')
            ->groupBy('products.id')
            ->orderByDesc('completed_count');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function incrementViews(): void
    {
        $this->increment('views');
    }
}
