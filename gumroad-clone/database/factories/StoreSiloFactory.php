<?php

namespace Database\Factories;

use App\Models\StoreSilo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreSiloFactory extends Factory
{
    protected $model = StoreSilo::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'store_domain' => $this->faker->unique()->slug,
            'subscription_tier' => 'basic',
            'payment_status' => 'active',
            'monthly_fee' => 29.99,
            'subscription_limits' => [
                'storage_mb' => 1000,
                'products' => 10,
                'monthly_revenue_cap' => 50000.00
            ],
            'next_billing_date' => now()->addMonth(),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    /**
     * Indicate that the store is suspended.
     */
    public function suspended(): self
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'suspended'
        ]);
    }

    /**
     * Indicate that the store is on professional tier.
     */
    public function professional(): self
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'professional',
            'monthly_fee' => 99.99,
            'subscription_limits' => [
                'storage_mb' => 5000,
                'products' => 50,
                'monthly_revenue_cap' => 200000.00
            ]
        ]);
    }

    /**
     * Indicate that the store is on enterprise tier.
     */
    public function enterprise(): self
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'enterprise',
            'monthly_fee' => 299.99,
            'subscription_limits' => [
                'storage_mb' => null, // Unlimited
                'products' => null,   // Unlimited
                'monthly_revenue_cap' => null // Unlimited
            ]
        ]);
    }
}
