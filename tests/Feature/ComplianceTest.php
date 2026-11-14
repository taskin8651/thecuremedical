<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PrescriptionEnquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\Helpers;
use Tests\TestCase;

class ComplianceTest extends TestCase
{
    use RefreshDatabase, Helpers;

    private function owner(): User
    {
        $this->seedRoles();
        $u = User::query()->create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('x'), 'is_active' => true]);
        $u->assignRole('Super Admin');

        return $u;
    }

    public function test_ordering_payments_and_prescriptions_are_off_by_default(): void
    {
        $this->assertFalse(feature('ordering'));
        $this->assertFalse(feature('payments'));
        $this->assertFalse(feature('prescriptions'));
        $owner = $this->owner();
        $this->actingAs($owner)->get('/admin/orders')->assertOk()->assertSee('switched off');
        $this->actingAs($owner)->get('/admin/payments')->assertOk()->assertSee('switched off');
        $this->actingAs($owner)->get('/admin/prescriptions')->assertOk()->assertSee('switched off');
        $this->actingAs($owner)->post('/admin/orders', [])->assertForbidden();
        $this->actingAs($owner)->post('/admin/prescriptions', ['name' => 'A'])->assertForbidden();
    }

    public function test_only_super_admin_can_enable_ordering_and_only_with_confirmation(): void
    {
        $owner = $this->owner();
        $mgr = $this->staff('Manager');
        $payload = ['tab' => 'features', 'inventory_enabled' => 1, 'expiry_alert_days' => 60, 'ordering_enabled' => 1, 'payments_enabled' => 0, 'prescriptions_enabled' => 0, 'payment_methods' => 'Cash'];

        $this->actingAs($mgr)->put('/admin/settings', $payload + ['confirm_compliance' => 1])->assertForbidden();
        $this->assertFalse(feature('ordering'));

        $this->actingAs($owner)->put('/admin/settings', $payload)->assertSessionHas('error');
        $this->assertFalse(feature('ordering'));

        $this->actingAs($owner)->put('/admin/settings', $payload + ['confirm_compliance' => 1])->assertSessionHas('success');
        $this->assertTrue(feature('ordering'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'feature_enabled']);
    }

    public function test_prescription_only_products_cannot_be_ordered(): void
    {
        $this->enable('ordering');
        $owner = $this->owner();
        $rx = $this->product(['name' => 'Rx item', 'requires_prescription' => true, 'stock_quantity' => 10]);
        $res = $this->actingAs($owner)->post('/admin/orders', [
            'items' => [['product_id' => $rx->id, 'quantity' => 1]], 'shipping_name' => 'A', 'shipping_phone' => '9999999999', 'shipping_address' => 'Somewhere',
        ]);
        $res->assertSessionHasErrors('items');
        $this->assertSame(0, Order::query()->count());
    }

    public function test_order_confirm_deducts_stock_and_cancel_returns_it(): void
    {
        $this->enable('ordering');
        $owner = $this->owner();
        $p = $this->product(['stock_quantity' => 0]);
        app(\App\Services\InventoryService::class)->move($p, 'in', 10, ['batch_number' => 'B1', 'expiry_date' => now()->addYear()->toDateString()]);
        $this->actingAs($owner)->post('/admin/orders', [
            'items' => [['product_id' => $p->id, 'quantity' => 3]], 'shipping_name' => 'A', 'shipping_phone' => '9999999999', 'shipping_address' => 'Somewhere',
        ])->assertRedirect();
        $order = Order::query()->firstOrFail();
        $this->assertSame(10, $p->fresh()->stock_quantity);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/status", ['status' => 'confirmed'])->assertSessionHas('success');
        $this->assertSame(7, $p->fresh()->stock_quantity);

        $this->actingAs($owner)->post("/admin/orders/{$order->id}/status", ['status' => 'cancelled'])->assertSessionHas('success');
        $this->assertSame(10, $p->fresh()->stock_quantity);
    }

    public function test_prescription_files_are_private_and_need_download_permission(): void
    {
        $this->enable('prescriptions');
        Storage::fake('local');
        $pharm = $this->staff('Pharmacist');
        $support = $this->staff('Support Staff');

        $this->actingAs($pharm)->post('/admin/prescriptions', [
            'name' => 'Patient', 'attachments' => [UploadedFile::fake()->image('rx.jpg')],
        ])->assertRedirect();
        $rx = PrescriptionEnquiry::query()->firstOrFail();
        $this->assertSame('received', $rx->status);
        $media = $rx->getFirstMedia('attachments');
        $this->assertSame('local', $media->disk);

        $this->actingAs($support)->get("/admin/prescriptions/{$rx->id}")->assertForbidden();
        $this->actingAs($support)->get("/admin/prescriptions/{$rx->id}/files/{$media->id}")->assertForbidden();
        $this->actingAs($pharm)->get("/admin/prescriptions/{$rx->id}/files/{$media->id}")->assertOk();
        $this->assertDatabaseHas('activity_logs', ['action' => 'downloaded']);
    }

    public function test_prescription_is_never_auto_verified_and_needs_the_verify_permission(): void
    {
        $this->enable('prescriptions');
        $pharm = $this->staff('Pharmacist');
        $this->actingAs($pharm)->post('/admin/prescriptions', ['name' => 'Patient'])->assertRedirect();
        $rx = PrescriptionEnquiry::query()->firstOrFail();
        $this->assertNull($rx->verified_by);

        $this->actingAs($pharm)->put("/admin/prescriptions/{$rx->id}", ['status' => 'verified'])->assertSessionHas('error'); // must be reviewed first
        $this->actingAs($pharm)->put("/admin/prescriptions/{$rx->id}", ['status' => 'under_review'])->assertSessionHas('success');
        $pharm->revokePermissionTo('prescriptions.verify');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $pharm->syncRoles([]);
        $pharm->givePermissionTo(['prescriptions.view', 'prescriptions.edit']);
        $this->actingAs($pharm->fresh())->put("/admin/prescriptions/{$rx->id}", ['status' => 'verified'])->assertForbidden();
    }

    public function test_payment_is_not_revenue_until_verified(): void
    {
        $this->enable('ordering', 'payments');
        $owner = $this->owner();
        $order = Order::query()->create(['order_number' => 'ORD-T1', 'grand_total' => 100, 'subtotal' => 100, 'status' => 'pending', 'payment_status' => 'unpaid']);
        $this->actingAs($owner)->post('/admin/payments', ['order_id' => $order->id, 'method' => 'Cash at store', 'amount' => 100, 'paid_at' => now()->subMinute()->format('Y-m-d\TH:i')])->assertRedirect();
        $pay = \App\Models\Payment::query()->firstOrFail();
        $this->assertSame('pending', $pay->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->actingAs($owner)->post("/admin/payments/{$pay->id}/verify")->assertSessionHas('success');
        $this->assertSame('paid', $order->fresh()->payment_status);
    }
}
