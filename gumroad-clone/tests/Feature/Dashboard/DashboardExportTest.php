<?php

namespace Tests\Feature\Dashboard;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_export_functionality()
    {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $seller->id]);
        Order::factory()->count(5)->create([
            'product_id' => $product->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($seller)
                        ->get(route('orders.sales', ['export' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv');
        $response->assertHeader('Content-Disposition', 'attachment; filename=sales.csv');
    }
}
