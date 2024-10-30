<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductFileHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
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
