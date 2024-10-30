<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraphs(2, true),
            'price' => $this->faker->randomFloat(2, 5, 100),
            'cover_image' => 'covers/' . $this->faker->uuid . '.jpg',
            'file_path' => 'products/' . $this->faker->uuid . '.pdf',
            'is_published' => $this->faker->boolean(80),
        ];
    }

    public function published(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_published' => true,
            ];
        });
    }

    public function unpublished(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_published' => false,
            ];
        });
    }
}
