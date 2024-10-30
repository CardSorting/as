<?php

namespace Tests\Unit\Product;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use InvalidArgumentException;
use Illuminate\Http\UploadedFile;

class ProductValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_requires_valid_user()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Product::factory()->create([
            'user_id' => 999999 // Non-existent user
        ]);
    }

    public function test_product_name_validation()
    {
        $product = Product::factory()->create(['name' => 'Test Product']);
        $this->assertEquals('Test Product', $product->name);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product name is required.');
        Product::factory()->create(['name' => null]);
    }

    public function test_product_name_cannot_be_empty()
    {
        $product = Product::factory()->create(['name' => 'Test Product']);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product name is required.');
        
        $product->name = '';
        $product->save();
    }

    public function test_product_file_type_validation()
    {
        $product = Product::factory()->create();

        // Valid file types
        $validFiles = [
            'document.pdf',
            'document.doc',
            'document.docx',
            'archive.zip',
            'archive.rar',
            'audio.mp3',
            'video.mp4',
            'video.mov',
            'video.avi',
            'image.jpg',
            'image.jpeg',
            'image.png',
            'image.gif'
        ];

        foreach ($validFiles as $filename) {
            $file = UploadedFile::fake()->create($filename, 100);
            $this->assertTrue($product->isValidFile($file), "Should accept {$filename}");
        }

        // Invalid file types
        $invalidFiles = [
            'script.php',
            'script.js',
            'data.csv',
            'executable.exe',
            'shell.sh'
        ];

        foreach ($invalidFiles as $filename) {
            $file = UploadedFile::fake()->create($filename, 100);
            $this->assertFalse($product->isValidFile($file), "Should reject {$filename}");
        }
    }

    public function test_product_file_size_validation()
    {
        $product = Product::factory()->create();

        // Test file size limits
        $smallFile = UploadedFile::fake()->create('document.pdf', 100); // 100KB
        $largeFile = UploadedFile::fake()->create('document.pdf', 60000); // 60MB

        $this->assertTrue($product->isValidFile($smallFile), 'Should accept small file');
        $this->assertFalse($product->isValidFile($largeFile), 'Should reject large file');

        // Test image size limits
        $smallImage = UploadedFile::fake()->create('image.jpg', 100); // 100KB
        $largeImage = UploadedFile::fake()->create('image.jpg', 6000); // 6MB

        $this->assertTrue($product->isValidCoverImage($smallImage), 'Should accept small image');
        $this->assertFalse($product->isValidCoverImage($largeImage), 'Should reject large image');
    }

    public function test_product_price_validation()
    {
        $product = Product::factory()->create(['price' => 99.99]);
        $this->assertEquals(99.99, $product->price);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product price cannot be negative.');
        $product->price = -10;
        $product->save();
    }

    public function test_product_slug_validation()
    {
        $product1 = Product::factory()->create(['name' => 'Test Product']);
        $product2 = Product::factory()->create(['name' => 'Test Product']);

        $this->assertNotEquals($product1->slug, $product2->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $product1->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $product2->slug);
    }

    public function test_product_url_generation()
    {
        $product = Product::factory()->create(['name' => 'Test Product']);
        
        $expectedUrl = url('/products/' . $product->slug);
        $this->assertEquals($expectedUrl, $product->url);
    }

    public function test_product_views_increment()
    {
        $product = Product::factory()->create(['views' => 0]);
        
        $product->incrementViews();
        $this->assertEquals(1, $product->views);

        $product->incrementViews();
        $this->assertEquals(2, $product->views);
    }
}
