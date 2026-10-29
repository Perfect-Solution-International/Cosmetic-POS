<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\SubscriptionPackage;
use App\Models\User;
use Database\Seeders\SystemDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected Branch $branch;
    protected Register $register;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(SystemDataSeeder::class);

        $this->admin = User::where('username', 'admin')->first();
        $this->cashier = User::where('username', 'cashier')->first();
        $this->branch = Branch::where('code', 'BR-COL-01')->first();
        $this->register = Register::where('branch_id', $this->branch->id)->first();

        $this->customer = Customer::create([
            'branch_id' => $this->branch->id,
            'name' => 'Kavindu Senarath',
            'phone' => '0779887766',
            'email' => 'kavindu@test.com',
        ]);
    }

    /** 1. Test Subscription Package Creation */
    public function test_subscription_package_creation(): void
    {
        $packageData = [
            'name' => 'Silver VIP Monthly Care',
            'code' => 'PKG-SILVER-7D',
            'price' => 4500.00,
            'billing_cycle' => 'monthly',
            'default_trial_days' => 7,
            'description' => 'VIP monthly care package with 7-day trial',
            'features' => "Priority Support\nFree Monthly Inspection",
        ];

        $response = $this->actingAs($this->admin)->post(route('subscriptions.packages.store'), $packageData);
        $response->assertRedirect(route('subscriptions.packages'));

        $this->assertDatabaseHas('subscription_packages', [
            'code' => 'PKG-SILVER-7D',
            'price' => 4500.00,
            'default_trial_days' => 7,
            'billing_cycle' => 'monthly',
        ]);
    }

    /** 2. Test Free Trial Enrollment with 3 Days, 7 Days and Custom Days */
    public function test_free_trial_enrollment_with_preset_and_custom_days(): void
    {
        $package = SubscriptionPackage::first();

        // A. 3-Day Free Trial via Register Staff
        $sub3Data = [
            'customer_id' => $this->customer->id,
            'package_id' => $package->id,
            'branch_id' => $this->branch->id,
            'plan_type' => 'free_trial',
            'trial_days' => 3,
            'sales_channel' => 'register',
            'sales_agent_id' => $this->cashier->id,
            'notes' => 'Client enrolled in 3-day evaluation trial',
        ];

        $response3 = $this->actingAs($this->cashier)->post(route('subscriptions.store'), $sub3Data);
        $response3->assertRedirect();

        $sub3 = Subscription::where('customer_id', $this->customer->id)
            ->where('trial_days', 3)
            ->first();

        $this->assertNotNull($sub3);
        $this->assertEquals('trial', $sub3->status);
        $this->assertTrue($sub3->is_free_trial);
        $this->assertEquals(3, $sub3->trial_days);
        $this->assertEquals('register', $sub3->sales_channel);
        $this->assertEquals($this->cashier->id, $sub3->sales_agent_id);
        $this->assertTrue($sub3->isTrialActive());

        // B. Custom Days (e.g. 5 days or 10 days) via Online Portal
        $customer2 = Customer::create([
            'branch_id' => $this->branch->id,
            'name' => 'Nadeesha Silva',
            'phone' => '0714455667',
        ]);

        $subCustomData = [
            'customer_id' => $customer2->id,
            'package_id' => $package->id,
            'branch_id' => $this->branch->id,
            'plan_type' => 'free_trial',
            'trial_days' => 5, // Custom days entered by user
            'sales_channel' => 'online', // Acquired online
            'notes' => 'Self-service web signup with custom 5-day trial',
        ];

        $responseCustom = $this->actingAs($this->admin)->post(route('subscriptions.store'), $subCustomData);
        $responseCustom->assertRedirect();

        $subCustom = Subscription::where('customer_id', $customer2->id)->first();
        $this->assertNotNull($subCustom);
        $this->assertEquals(5, $subCustom->trial_days);
        $this->assertEquals('online', $subCustom->sales_channel);
        $this->assertNull($subCustom->sales_agent_id); // No register staff for self-service online
    }

    /** 3. Test Sales Origin & Channel Tracking (Online vs Register) */
    public function test_sales_channel_tracking_online_vs_register(): void
    {
        $package = SubscriptionPackage::first();

        // 1. In-Store Register Sale
        $registerSub = Subscription::create([
            'subscription_no' => 'SUB-REG-001',
            'customer_id' => $this->customer->id,
            'package_id' => $package->id,
            'branch_id' => $this->branch->id,
            'status' => 'trial',
            'is_free_trial' => true,
            'trial_days' => 7,
            'trial_ends_at' => now()->addDays(7),
            'started_at' => now(),
            'price' => $package->price,
            'sales_channel' => 'register',
            'sales_agent_id' => $this->cashier->id,
        ]);

        // 2. Online Self-Service Sale
        $onlineSub = Subscription::create([
            'subscription_no' => 'SUB-ONL-002',
            'customer_id' => $this->customer->id,
            'package_id' => $package->id,
            'branch_id' => $this->branch->id,
            'status' => 'trial',
            'is_free_trial' => true,
            'trial_days' => 7,
            'trial_ends_at' => now()->addDays(7),
            'started_at' => now(),
            'price' => $package->price,
            'sales_channel' => 'online',
            'sales_agent_id' => null,
        ]);

        $this->assertEquals('register', $registerSub->sales_channel);
        $this->assertEquals($this->cashier->id, $registerSub->sales_agent_id);

        $this->assertEquals('online', $onlineSub->sales_channel);
        $this->assertNull($onlineSub->sales_agent_id);

        // Check Index filter by online
        $this->actingAs($this->admin)->get(route('subscriptions.index', ['sales_channel' => 'online']))
            ->assertStatus(200)
            ->assertSee('SUB-ONL-002');
    }

    /** 4. Test 1-Click Convert Free Trial to Paid Subscription */
    public function test_convert_free_trial_to_paid_subscription(): void
    {
        $package = SubscriptionPackage::first();

        $sub = Subscription::create([
            'subscription_no' => 'SUB-CONV-001',
            'customer_id' => $this->customer->id,
            'package_id' => $package->id,
            'branch_id' => $this->branch->id,
            'status' => 'trial',
            'is_free_trial' => true,
            'trial_days' => 7,
            'trial_ends_at' => now()->addDays(2),
            'started_at' => now()->subDays(5),
            'current_period_starts_at' => now()->subDays(5),
            'current_period_ends_at' => now()->addDays(2),
            'price' => $package->price,
            'sales_channel' => 'register',
            'sales_agent_id' => $this->cashier->id,
        ]);

        $convertData = [
            'payment_method' => 'card',
            'sales_channel' => 'register',
            'sales_agent_id' => $this->cashier->id,
        ];

        $response = $this->actingAs($this->cashier)->post(route('subscriptions.convert', $sub->id), $convertData);
        $response->assertRedirect(route('subscriptions.show', $sub->id));

        $sub->refresh();
        $this->assertEquals('active', $sub->status);
        $this->assertFalse($sub->is_free_trial);

        // Verify a real Sale was generated and linked to the subscription
        $this->assertDatabaseHas('sales', [
            'subscription_id' => $sub->id,
            'customer_id' => $this->customer->id,
            'sales_channel' => 'register',
            'grand_total' => $package->price,
            'payment_status' => 'paid',
        ]);
    }

    /** 5. Test Subscription Monthly Renewal and Cancellation */
    public function test_subscription_renewal_and_cancellation(): void
    {
        $package = SubscriptionPackage::first();

        $sub = Subscription::create([
            'subscription_no' => 'SUB-RENEW-001',
            'customer_id' => $this->customer->id,
            'package_id' => $package->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'is_free_trial' => false,
            'trial_days' => 0,
            'started_at' => now()->subMonth(),
            'current_period_starts_at' => now()->subMonth(),
            'current_period_ends_at' => now()->addDays(1),
            'price' => $package->price,
            'sales_channel' => 'register',
            'sales_agent_id' => $this->cashier->id,
        ]);

        // Renew for 1 month
        $renewResponse = $this->actingAs($this->cashier)->post(route('subscriptions.renew', $sub->id), [
            'payment_method' => 'cash',
        ]);
        $renewResponse->assertRedirect();

        $sub->refresh();
        $this->assertEquals('active', $sub->status);
        $this->assertTrue($sub->current_period_ends_at->isAfter(now()->addDays(20)));

        // Cancel
        $cancelResponse = $this->actingAs($this->admin)->post(route('subscriptions.cancel', $sub->id));
        $cancelResponse->assertRedirect();

        $this->assertEquals('cancelled', $sub->fresh()->status);
    }

    /** 6. Test Subscription Create Form View Renders Successfully */
    public function test_subscription_create_form_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('subscriptions.create'));
        $response->assertStatus(200);
        $response->assertSee('Enroll Subscription');
        $response->assertSee('Payment-Free Trial');
        $response->assertSee('Sales Origin / Acquisition Channel');
    }

    /** 7. Test Multi-Outlet Subscription with Per-Branch Add-on Pricing */
    public function test_multi_outlet_subscription_with_per_branch_add_on_pricing(): void
    {
        $package = SubscriptionPackage::create([
            'name' => 'Growth Multi-Outlet SaaS',
            'code' => 'PKG-MULTI-3MO',
            'price' => 5000.00,
            'billing_cycle' => 'quarterly',
            'included_branches' => 1,
            'price_per_additional_branch' => 1500.00,
            'default_trial_days' => 7,
            'is_active' => true,
        ]);

        $shopOwner = User::create([
            'name' => 'Kasun Perera',
            'username' => 'kasun_owner',
            'email' => 'kasun@merchant.com',
            'password' => bcrypt('password123'),
            'role' => 'shop_owner',
            'status' => 'active',
        ]);

        // Enroll with 3 outlets (1 base + 2 additional @ Rs. 1500 each => 5000 + 3000 = 8000)
        $postData = [
            'business_name' => 'Kasun Retail Chain',
            'shop_owner_id' => $shopOwner->id,
            'customer_id' => $this->customer->id,
            'package_id' => $package->id,
            'branch_id' => $this->branch->id,
            'allocated_branches' => 3,
            'plan_type' => 'paid',
            'sales_channel' => 'register',
            'sales_agent_id' => $this->cashier->id,
            'payment_method' => 'card',
        ];

        $response = $this->actingAs($this->admin)->post(route('subscriptions.store'), $postData);
        $response->assertRedirect();

        $sub = Subscription::where('business_name', 'Kasun Retail Chain')->first();
        $this->assertNotNull($sub);
        $this->assertEquals(3, $sub->allocated_branches);
        $this->assertEquals(5000.00, (float) $sub->base_price);
        $this->assertEquals(1500.00, (float) $sub->additional_branch_price);
        $this->assertEquals(8000.00, (float) $sub->total_price);
        $this->assertEquals('quarterly', $sub->billing_cycle);
        $this->assertEquals('active', $sub->status);

        // Verify that Sale invoice has Rs. 8000.00
        $sale = Sale::where('subscription_id', $sub->id)->first();
        $this->assertNotNull($sale);
        $this->assertEquals(8000.00, (float) $sale->grand_total);
    }

    /** 8. Test Multi-Outlet Master HQ Dashboard Renders */
    public function test_multi_outlet_master_hq_dashboard_renders(): void
    {
        $response = $this->actingAs($this->admin)->get(route('hq.index'));
        $response->assertStatus(200);
        $response->assertSee('Multi-Outlet Master Command');
        $response->assertSee('Branch & Outlet Performance Matrix', false);
        $response->assertSee('Central Inventory & Multi-Branch Stock Matrix', false);
    }

    /** 9. Test Branch Creation Enforces Subscription Quota */
    public function test_branch_creation_enforces_subscription_quota(): void
    {
        $package = SubscriptionPackage::create([
            'name' => 'Single Outlet Starter',
            'code' => 'PKG-SINGLE',
            'price' => 3000.00,
            'billing_cycle' => 'monthly',
            'included_branches' => 1,
            'price_per_additional_branch' => 1500.00,
            'default_trial_days' => 7,
            'is_active' => true,
        ]);

        $shopOwner = User::create([
            'name' => 'Nimal Retailer',
            'username' => 'nimal_owner',
            'email' => 'nimal@retailer.com',
            'password' => bcrypt('password123'),
            'role' => 'shop_owner',
            'status' => 'active',
        ]);

        // Active subscription with only 1 outlet allowed
        Subscription::create([
            'subscription_no' => 'SUB-QUOTA-TEST',
            'business_name' => 'Nimal Store',
            'shop_owner_id' => $shopOwner->id,
            'customer_id' => $this->customer->id,
            'package_id' => $package->id,
            'branch_id' => $this->branch->id,
            'allocated_branches' => 1,
            'status' => 'active',
            'is_free_trial' => false,
            'price' => 3000.00,
            'sales_channel' => 'register',
        ]);

        // Link existing branch to owner (1 of 1 used)
        $this->branch->update(['owner_id' => $shopOwner->id]);

        // Attempting to create a second branch as shop owner should fail quota check
        $branchData = [
            'name' => 'Nimal Outlet Two',
            'code' => 'BR-NIM-02',
            'type' => 'retail_store',
        ];

        $response = $this->actingAs($shopOwner)->post(route('branches.store'), $branchData);
        $response->assertSessionHasErrors('branch_quota');
        $this->assertDatabaseMissing('branches', ['code' => 'BR-NIM-02']);
    }
}

