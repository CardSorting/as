<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_index_displays_published_products()
    {
        $publishedProducts = Product::factory()->count(3)->create(['is_published' => true]);
        Product::factory()->count(2)->create(['is_published' => false]);

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertViewHas('products');
        foreach ($publishedProducts as $product) {
            $response->assertSee($product->name);
        }
    }

    public function test_show_displays_product_details()
    {
        $product = Product::factory()->create(['is_published' => true]);

        $response = $this->get(route('products.show', $product));

        $response->assertStatus(200);
        $response->assertSee($product->name);
        $response->assertSee($product->description);
        $response->assertSee(number_format($product->price, 2));
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

        $product = Product::where('name', $productData['name'])->first();
        $response->assertRedirect(route('products.show', $product));
    }

    public function test_user_can_edit_own_product()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        
        $updatedData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'price' => 149.99,
            'is_published' => true
        ];

        $response = $this->actingAs($user)
                        ->put(route('products.update', $product), $updatedData);

        $response->assertRedirect(route('products.show', $product));
        $this->assertDatabaseHas('products', $updatedData + ['id' => $product->id]);
    }

    public function test_user_cannot_edit_others_product()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
                        ->get(route('products.edit', $product));

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_product()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                        ->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_user_cannot_delete_others_product()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
                        ->delete(route('products.destroy', $product));

        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_validation_rules_for_product_creation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                        ->post(route('products.store'), [
                            'name' => '',
                            'description' => '',
                            'price' => 'not-a-number'
                        ]);

        $response->assertSessionHasErrors(['name', 'description', 'price', 'file_path']);
    }

    public function test_unpublished_product_is_not_visible_to_guests()
    {
        $product = Product::factory()->create(['is_published' => false]);

        $response = $this->get(route('products.show', $product));

        $response->assertStatus(404);
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

    public function test_validation_rules_for_product_update()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                        ->put(route('products.update', $product), [
                            'name' => '',
                            'description' => '',
                            'price' => 'invalid-price'
                        ]);

        $response->assertSessionHasErrors(['name', 'description', 'price']);
    }

    public function test_user_can_publish_own_product()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'is_published' => false
        ]);

        $response = $this->actingAs($user)
                        ->put(route('products.update', $product), [
                            'name' => $product->name,
                            'description' => $product->description,
                            'price' => $product->price,
                            'is_published' => true
                        ]);

        $response->assertRedirect(route('products.show', $product));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_published' => true
        ]);
    }

    public function test_user_can_update_product_files()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        
        $newFile = UploadedFile::fake()->create('new-file.pdf', 100);
        $newCover = UploadedFile::fake()->image('new-cover.jpg');

        $response = $this->actingAs($user)
                        ->put(route('products.update', $product), [
                            'name' => $product->name,
                            'description' => $product->description,
                            'price' => $product->price,
                            'cover_image' => $newCover,
                            'file_path' => $newFile
                        ]);

        $response->assertRedirect(route('products.show', $product));
        Storage::disk('public')->assertExists('products/' . $product->id . '/' . $newFile->hashName());
        Storage::disk('public')->assertExists('products/' . $product->id . '/cover/' . $newCover->hashName());
    }

    public function test_product_files_are_optional_during_update()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);
        
        $originalFilePath = $product->file_path;
        $originalCoverPath = $product->cover_image;

        $response = $this->actingAs($user)
                        ->put(route('products.update', $product), [
                            'name' => 'Updated Name',
                            'description' => 'Updated Description',
                            'price' => 149.99
                        ]);

        $response->assertRedirect(route('products.show', $product));
        $this->assertEquals($originalFilePath, $product->fresh()->file_path);
        $this->assertEquals($originalCoverPath, $product->fresh()->cover_image);
    }
}
