<?php

namespace Tests\Unit\Product;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_search_by_name()
    {
        Product::factory()->create(['name' => 'PHP Course']);
        Product::factory()->create(['name' => 'JavaScript Guide']);
        Product::factory()->create(['name' => 'Python Tutorial']);

        $results = Product::search('php')->get();
        $this->assertEquals(1, $results->count());
        $this->assertEquals('PHP Course', $results->first()->name);
    }

    public function test_product_search_by_description()
    {
        Product::factory()->create([
            'name' => 'Course A',
            'description' => 'Learn PHP programming'
        ]);
        Product::factory()->create([
            'name' => 'Course B',
            'description' => 'Learn JavaScript'
        ]);

        $results = Product::search('php')->get();
        $this->assertEquals(1, $results->count());
        $this->assertEquals('Course A', $results->first()->name);
    }

    public function test_product_search_is_case_insensitive()
    {
        Product::factory()->create(['name' => 'PHP Course']);

        $lowerResults = Product::search('php')->get();
        $upperResults = Product::search('PHP')->get();
        $mixedResults = Product::search('PhP')->get();

        $this->assertEquals(1, $lowerResults->count());
        $this->assertEquals(1, $upperResults->count());
        $this->assertEquals(1, $mixedResults->count());
    }

    public function test_product_search_with_partial_match()
    {
        Product::factory()->create(['name' => 'Advanced PHP Course']);
        Product::factory()->create(['name' => 'PHP for Beginners']);
        Product::factory()->create(['name' => 'JavaScript Basics']);

        $results = Product::search('php')->get();
        $this->assertEquals(2, $results->count());
    }

    public function test_product_search_with_no_results()
    {
        Product::factory()->create(['name' => 'PHP Course']);
        Product::factory()->create(['name' => 'JavaScript Guide']);

        $results = Product::search('python')->get();
        $this->assertEquals(0, $results->count());
    }

    public function test_product_search_with_multiple_terms()
    {
        Product::factory()->create([
            'name' => 'Advanced PHP Course',
            'description' => 'Learn advanced PHP programming'
        ]);
        Product::factory()->create([
            'name' => 'PHP Basics',
            'description' => 'Introduction to PHP'
        ]);
        Product::factory()->create([
            'name' => 'JavaScript Course',
            'description' => 'Advanced JS programming'
        ]);

        $results = Product::search('advanced php')->get();
        $this->assertEquals(1, $results->count());
        $this->assertEquals('Advanced PHP Course', $results->first()->name);
    }
}
