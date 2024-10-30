<?php

namespace Tests\Feature\Product;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductCreationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_create_requires_authentication()
    {
        $response = $this->get(route('products.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_create_product()
    {
        $user = User::factory()->create();
        
        $file = UploadedFile::fake()->create('test-file.pdf', 100);
        $cover = UploadedFile::fake()->image('test-cover.jpg');

        $productData = [
            'name' => $this->faker->word,
            'description' => $this->faker->paragraph,
            'price' => 99.99,
            'cover_image' => $cover,
            'file_path' => $file
        ];

        $response = $this->actingAs($user)
                        ->post(route('products.store'), $productData);

        $this->assertDatabaseHas('products', [
            'name' => $productData['name'],
            'description' => $productData['description'],
            'price' => $productData['price'],
            'user_id' => $user->id
        ]);

        $product = \App\Models\Product::where('name', $productData['name'])->first();
        $response->assertRedirect(route('products.show', $product));
    }

    public function test_file_size_validation_for_product_creation()
    {
        $user = User::factory()->create();
        
        $oversizedFile = UploadedFile::fake()->create('large-file.pdf', 51200); // 50MB
        $cover = UploadedFile::fake()->image('test-cover.jpg');

        $response = $this->actingAs($user)
                        ->post(route('products.store'), [
                            'name' => $this->faker->word,
                            'description' => $this->faker->paragraph,
                            'price' => 99.99,
                            'cover_image' => $cover,
                            'file_path' => $oversizedFile
                        ]);

        $response->assertSessionHasErrors('file_path');
    }

    public function test_file_mime_type_validation()
    {
        $user = User::factory()->create();
        
        $invalidFile = UploadedFile::fake()->create('test.exe', 100);
        $cover = UploadedFile::fake()->image('test-cover.jpg');

        $response = $this->actingAs($user)
                        ->post(route('products.store'), [
                            'name' => $this->faker->word,
                            'description' => $this->faker->paragraph,
                            'price' => 99.99,
                            'cover_image' => $cover,
                            'file_path' => $invalidFile
                        ]);

        $response->assertSessionHasErrors('file_path');
    }
}
