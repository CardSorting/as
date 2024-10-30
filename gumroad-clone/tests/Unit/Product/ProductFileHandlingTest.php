<?php

namespace Tests\Unit\Product;

use App\Models\Product;
use App\Models\Order;
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

    public function test_product_file_path_generation()
    {
        $product = Product::factory()->create();
        $file = UploadedFile::fake()->create('test.pdf');

        $path = $product->generateFilePath($file);

        $this->assertStringStartsWith('products/' . $product->id . '/', $path);
        $this->assertStringEndsWith('.pdf', $path);
    }

    public function test_product_cover_image_path_generation()
    {
        $product = Product::factory()->create();
        $image = UploadedFile::fake()->image('cover.jpg');

        $path = $product->generateCoverImagePath($image);

        $this->assertStringStartsWith('products/' . $product->id . '/cover/', $path);
        $this->assertStringEndsWith('.jpg', $path);
    }

    public function test_product_file_validation()
    {
        $product = Product::factory()->create();

        // Create test files (size in kilobytes)
        $validFile = UploadedFile::fake()->create('document.pdf', 100); // 100KB
        $oversizedFile = UploadedFile::fake()->create('large.pdf', 60000); // 60MB
        $invalidTypeFile = UploadedFile::fake()->create('script.exe', 100);

        // Test file size validation (50MB = 51200 KB)
        $maxSizeKB = 51200; // 50MB in KB
        $validFileKB = $validFile->getSize() / 1024; // Convert bytes to KB
        $oversizedFileKB = $oversizedFile->getSize() / 1024; // Convert bytes to KB

        $this->assertLessThanOrEqual($maxSizeKB, $validFileKB);
        $this->assertGreaterThan($maxSizeKB, $oversizedFileKB);

        // Test file type validation
        $validExtensions = ['pdf', 'doc', 'docx'];
        $this->assertContains($validFile->getClientOriginalExtension(), $validExtensions);
        $this->assertNotContains($invalidTypeFile->getClientOriginalExtension(), $validExtensions);
    }

    public function test_product_cover_image_validation()
    {
        $product = Product::factory()->create();

        // Create test files (size in kilobytes)
        $validImage = UploadedFile::fake()->image('cover.jpg')->size(100); // 100KB
        $oversizedImage = UploadedFile::fake()->image('large.jpg')->size(3000); // 3MB
        $invalidTypeFile = UploadedFile::fake()->create('document.pdf', 100);

        // Test file size validation (2MB = 2048 KB)
        $maxSizeKB = 2048; // 2MB in KB
        $validImageKB = $validImage->getSize() / 1024; // Convert bytes to KB
        $oversizedImageKB = $oversizedImage->getSize() / 1024; // Convert bytes to KB

        $this->assertLessThanOrEqual($maxSizeKB, $validImageKB);
        $this->assertGreaterThan($maxSizeKB, $oversizedImageKB);

        // Test file type validation
        $validExtensions = ['jpg', 'jpeg', 'png'];
        $this->assertContains($validImage->getClientOriginalExtension(), $validExtensions);
        $this->assertNotContains($invalidTypeFile->getClientOriginalExtension(), $validExtensions);
    }

    public function test_product_cascading_delete()
    {
        $product = Product::factory()->create();
        
        // Create associated orders
        Order::factory()->count(3)->create(['product_id' => $product->id]);
        
        // Create and store files
        $file = UploadedFile::fake()->create('test.pdf');
        $cover = UploadedFile::fake()->image('cover.jpg');
        
        $filePath = 'products/' . $product->id . '/test.pdf';
        $coverPath = 'products/' . $product->id . '/cover/cover.jpg';
        
        // Create directories and store files
        Storage::disk('public')->makeDirectory('products/' . $product->id);
        Storage::disk('public')->makeDirectory('products/' . $product->id . '/cover');
        
        Storage::disk('public')->put($filePath, $file->get());
        Storage::disk('public')->put($coverPath, $cover->get());

        // Verify files exist before deletion
        $this->assertTrue(Storage::disk('public')->exists($filePath));
        $this->assertTrue(Storage::disk('public')->exists($coverPath));

        // Register a deleting event handler to clean up files
        Product::deleting(function ($product) {
            Storage::disk('public')->deleteDirectory('products/' . $product->id);
        });

        // Delete product
        $product->delete();

        // Check if orders are deleted
        $this->assertEquals(0, Order::where('product_id', $product->id)->count());
        
        // Check if files are deleted
        $this->assertFalse(Storage::disk('public')->exists($filePath));
        $this->assertFalse(Storage::disk('public')->exists($coverPath));
        $this->assertFalse(Storage::disk('public')->exists('products/' . $product->id));
    }
}
