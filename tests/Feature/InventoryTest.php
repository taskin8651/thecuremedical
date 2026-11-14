<?php

namespace Tests\Feature;

use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\Helpers;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase, Helpers;

    public function test_stock_in_creates_a_batch_and_a_ledger_row(): void
    {
        $p = $this->product(['stock_quantity' => 0]);
        app(InventoryService::class)->move($p, 'in', 20, ['batch_number' => 'A1', 'expiry_date' => now()->addMonths(6)->toDateString(), 'reason' => 'purchase']);
        $this->assertSame(20, $p->fresh()->stock_quantity);
        $this->assertDatabaseHas('product_batches', ['product_id' => $p->id, 'batch_number' => 'A1', 'quantity' => 20]);
        $this->assertDatabaseHas('inventory_transactions', ['product_id' => $p->id, 'type' => 'in', 'quantity' => 20, 'balance_after' => 20]);
    }

    public function test_stock_out_uses_earliest_expiry_first_and_cannot_go_negative(): void
    {
        $p = $this->product(['stock_quantity' => 0]);
        $svc = app(InventoryService::class);
        $svc->move($p, 'in', 5, ['batch_number' => 'LATE', 'expiry_date' => now()->addYear()->toDateString()]);
        $svc->move($p, 'in', 5, ['batch_number' => 'SOON', 'expiry_date' => now()->addMonths(2)->toDateString()]);
        $svc->move($p, 'out', 6, ['reason' => 'damaged']);
        $this->assertDatabaseHas('product_batches', ['batch_number' => 'SOON', 'quantity' => 0]);
        $this->assertDatabaseHas('product_batches', ['batch_number' => 'LATE', 'quantity' => 4]);
        $this->expectException(ValidationException::class);
        $svc->move($p, 'out', 50, ['reason' => 'damaged']);
    }

    public function test_adjustment_sets_the_counted_quantity(): void
    {
        $p = $this->product(['stock_quantity' => 0]);
        $svc = app(InventoryService::class);
        $svc->move($p, 'in', 10, ['batch_number' => 'X']);
        $svc->move($p, 'adjustment', 7, ['reason' => 'stock_count']);
        $this->assertSame(7, $p->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_transactions', ['type' => 'adjustment', 'quantity' => -3, 'balance_after' => 7]);
    }

    public function test_ledger_rows_cannot_be_edited_or_deleted(): void
    {
        $p = $this->product(['stock_quantity' => 0]);
        app(InventoryService::class)->move($p, 'in', 1, ['batch_number' => 'X']);
        $row = InventoryTransaction::query()->firstOrFail();
        $this->expectException(\LogicException::class);
        $row->delete();
    }

    public function test_expired_stock_cannot_be_received_through_the_form(): void
    {
        $owner = $this->staff('Inventory Manager');
        $p = $this->product();
        $this->actingAs($owner)->post('/admin/inventory/movement', [
            'type' => 'in', 'product_id' => $p->id, 'quantity' => 5, 'reason' => 'purchase', 'batch_number' => 'OLD', 'expiry_date' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors('expiry_date');
    }
}
