<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\Helpers;
use Tests\TestCase;

class ProductBulkVerificationTest extends TestCase
{
    use Helpers, RefreshDatabase;

    public function test_confirmed_bulk_verification_covers_the_whole_catalogue(): void
    {
        $manager = $this->staff('Manager');
        $pharmacist = $this->staff('Pharmacist');
        $this->actingAs($pharmacist);
        $oldVerification = now()->subDay()->startOfSecond();
        $verified = $this->product(['is_verified' => true, 'verified_by' => $manager->id, 'verified_at' => $oldVerification]);
        $inactive = $this->product(['mrp' => null, 'stock_quantity' => 12, 'is_active' => false]);
        $deleted = $this->product();
        $deleted->delete();

        foreach (array_chunk(range(1, 439), 100) as $numbers) {
            DB::table('products')->insert(array_map(fn ($number) => [
                'name' => 'Catalogue Product '.$number, 'slug' => 'catalogue-product-'.$number,
            ], $numbers));
        }

        $form = route('admin.products.verify-all');
        $submit = route('admin.products.verify-all.store');
        $this->get(route('admin.products.index', ['q' => 'no matching products', 'page' => 3]))
            ->assertOk()->assertSee('Verify All Products');
        $this->get($form)->assertOk()->assertSee('440 unverified products');
        $this->from($form)->post($submit)->assertRedirect($form)->assertSessionHasErrors('confirmed');
        $this->assertSame(440, Product::where('is_verified', false)->count());

        $this->post($submit, ['confirmed' => 1])
            ->assertRedirect(route('admin.products.index'))->assertSessionHas('success', '440 products marked as verified.');
        $this->assertSame(440, Product::where('verified_by', $pharmacist->id)->whereNotNull('verified_at')->count());
        $this->assertFalse($deleted->fresh()->is_verified);
        $this->assertSame($manager->id, $verified->fresh()->verified_by);
        $this->assertTrue($oldVerification->equalTo($verified->fresh()->verified_at));
        $this->assertNull($inactive->fresh()->mrp);
        $this->assertFalse($inactive->fresh()->is_active);
        $this->assertSame(12, $inactive->fresh()->stock_quantity);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $pharmacist->id, 'action' => 'bulk', 'description' => 'Verified 440 products across the catalogue',
        ]);

        $this->post($submit, ['confirmed' => 1])->assertSessionHas('success', 'All products are already verified.');
        $this->get($form)->assertOk()->assertSee('All products are already verified.')->assertDontSee('Confirm and verify all');
    }

    public function test_staff_without_verify_permission_cannot_access_bulk_verification(): void
    {
        $user = $this->staff('Inventory Manager');
        $this->actingAs($user);

        $this->get(route('admin.products.index'))->assertOk()->assertDontSee('Verify All Products');
        $this->get(route('admin.products.verify-all'))->assertForbidden();
        $this->post(route('admin.products.verify-all.store'), ['confirmed' => 1])->assertForbidden();
    }
}
