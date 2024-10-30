<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_order_belongs_to_user()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    public function test_order_belongs_to_product()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $order->product);
        $this->assertEquals($product->id, $order->product->id);
    }

    public function test_order_has_amount_as_float()
    {
        $order = Order::factory()->create(['amount' => 29.99]);

        $this->assertIsFloat($order->amount);
        $this->assertEquals(29.99, $order->amount);
    }

    public function test_order_has_valid_status()
    {
        $order = Order::factory()->create(['status' => 'pending']);
        $this->assertEquals('pending', $order->status);

        $order->status = 'completed';
        $order->save();
        $this->assertEquals('completed', $order->fresh()->status);
    }

    public function test_can_get_completed_orders()
    {
        Order::factory()->count(3)->create(['status' => 'completed']);
        Order::factory()->count(2)->create(['status' => 'pending']);

        $completedOrders = Order::where('status', 'completed')->get();

        $this->assertEquals(3, $completedOrders->count());
    }

    public function test_order_has_payment_id()
    {
        $paymentId = 'demo_' . uniqid();
        $order = Order::factory()->create(['payment_id' => $paymentId]);

        $this->assertEquals($paymentId, $order->payment_id);
    }

    public function test_user_can_access_their_orders()
    {
        $user = User::factory()->create();
        Order::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertEquals(3, $user->orders()->count());
    }

    public function test_product_can_access_its_orders()
    {
        $product = Product::factory()->create();
        Order::factory()->count(3)->create(['product_id' => $product->id]);

        $this->assertEquals(3, $product->orders()->count());
    }

    public function test_order_status_transition_validation()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        // Valid transition
        $this->assertTrue($order->canTransitionTo('completed'));
        
        // Invalid transitions
        $order->status = 'completed';
        $order->save();
        
        $this->assertFalse($order->canTransitionTo('pending'));
        $this->expectException(InvalidArgumentException::class);
        $order->status = 'invalid_status';
    }

    public function test_order_amount_formatting()
    {
        $order = Order::factory()->create(['amount' => 99.99]);

        $this->assertEquals('$99.99', $order->formatted_amount);
        $this->assertEquals(9999, $order->amount_in_cents);
    }

    public function test_order_amount_validation()
    {
        $this->expectException(InvalidArgumentException::class);
        Order::factory()->create(['amount' => -10.00]);
    }

    public function test_order_scope_by_date_range()
    {
        // Create orders with different dates
        Order::factory()->create(['created_at' => Carbon::now()->subDays(1)]);
        Order::factory()->create(['created_at' => Carbon::now()->subDays(5)]);
        Order::factory()->create(['created_at' => Carbon::now()->subDays(10)]);

        $recentOrders = Order::whereBetween('created_at', [
            Carbon::now()->subDays(7),
            Carbon::now()
        ])->get();

        $this->assertEquals(2, $recentOrders->count());
    }

    public function test_order_scope_by_amount_range()
    {
        Order::factory()->create(['amount' => 50.00]);
        Order::factory()->create(['amount' => 100.00]);
        Order::factory()->create(['amount' => 150.00]);

        $orders = Order::whereBetween('amount', [75, 125])->get();

        $this->assertEquals(1, $orders->count());
        $this->assertEquals(100.00, $orders->first()->amount);
    }

    public function test_order_download_validation()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $this->assertFalse($order->canDownload());

        $order->status = 'completed';
        $order->save();

        $this->assertTrue($order->canDownload());
    }

    public function test_order_refund_handling()
    {
        $order = Order::factory()->create([
            'status' => 'completed',
            'amount' => 100.00
        ]);

        $order->refund();

        $this->assertEquals('refunded', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->refunded_at);
    }

    public function test_order_date_accessors()
    {
        $order = Order::factory()->create();

        $this->assertInstanceOf(Carbon::class, $order->created_at);
        $this->assertInstanceOf(Carbon::class, $order->updated_at);
        $this->assertEquals(
            $order->created_at->format('Y-m-d'),
            $order->formatted_date
        );
    }

    public function test_order_statistics()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100.00]);

        // Create orders with different statuses
        Order::factory()->count(3)->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => 100.00,
            'status' => 'completed'
        ]);
        Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => 100.00,
            'status' => 'refunded'
        ]);
        Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => 100.00,
            'status' => 'pending'
        ]);

        $stats = Order::statistics();

        $this->assertEquals(5, $stats['total_orders']);
        $this->assertEquals(3, $stats['completed_orders']);
        $this->assertEquals(1, $stats['refunded_orders']);
        $this->assertEquals(300.00, $stats['total_revenue']);
        $this->assertEquals(100.00, $stats['average_order_value']);
    }

    public function test_order_scope_by_status()
    {
        Order::factory()->create(['status' => 'completed']);
        Order::factory()->create(['status' => 'pending']);
        Order::factory()->create(['status' => 'refunded']);

        $this->assertEquals(1, Order::completed()->count());
        $this->assertEquals(1, Order::pending()->count());
        $this->assertEquals(1, Order::refunded()->count());
    }

    public function test_order_recent_scope()
    {
        Order::factory()->create(['created_at' => Carbon::now()->subDays(1)]);
        Order::factory()->create(['created_at' => Carbon::now()->subDays(5)]);
        Order::factory()->create(['created_at' => Carbon::now()->subDays(10)]);

        $this->assertEquals(2, Order::recent()->count());
    }

    public function test_order_total_by_user()
    {
        $user = User::factory()->create();
        Order::factory()->count(3)->create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'status' => 'completed'
        ]);

        $this->assertEquals(300.00, Order::totalByUser($user->id));
    }

    public function test_order_number_generation()
    {
        $order = Order::factory()->create();

        $this->assertNotNull($order->order_number);
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-\d{4}$/', $order->order_number);
    }

    public function test_order_status_label()
    {
        $order = Order::factory()->create(['status' => 'completed']);

        $this->assertEquals('Completed', $order->status_label);
        
        $order->status = 'pending';
        $this->assertEquals('Pending', $order->status_label);
        
        $order->status = 'refunded';
        $this->assertEquals('Refunded', $order->status_label);
    }
}
