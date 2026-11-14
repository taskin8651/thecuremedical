<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\Helpers;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase, Helpers;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->get('/admin/products')->assertRedirect(route('login'));
    }

    public function test_staff_can_sign_in_and_see_the_dashboard(): void
    {
        $this->staff('Manager');
        $this->post(route('login.attempt'), ['email' => 'manager@example.test', 'password' => self::PASSWORD])->assertRedirect();
        $this->get('/admin/dashboard')->assertOk()->assertSee('Dashboard');
    }

    public function test_wrong_password_and_inactive_accounts_are_rejected_with_one_message(): void
    {
        $this->staff('Manager');
        $this->staff('Pharmacist', ['is_active' => false]);
        $this->from(route('login'))->post(route('login.attempt'), ['email' => 'manager@example.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post(route('login.attempt'), ['email' => 'pharmacist@example.test', 'password' => self::PASSWORD])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $this->staff('Manager');
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.attempt'), ['email' => 'manager@example.test', 'password' => 'bad']);
        }
        $this->post(route('login.attempt'), ['email' => 'manager@example.test', 'password' => self::PASSWORD])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_roles_limit_what_staff_can_open(): void
    {
        $support = $this->staff('Support Staff');
        $this->actingAs($support)->get('/admin/users')->assertForbidden();
        $this->actingAs($support)->get('/admin/settings')->assertForbidden();
        $this->actingAs($support)->get('/admin/enquiries')->assertOk();

        $content = $this->staff('Content Manager');
        $this->actingAs($content)->get('/admin/blogs')->assertOk();
        $this->actingAs($content)->get('/admin/enquiries')->assertForbidden();
    }

    public function test_super_admin_can_open_everything_main(): void
    {
        $this->seedRoles();
        $admin = User::query()->create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('x'), 'is_active' => true]);
        $admin->assignRole('Super Admin');
        foreach (['dashboard', 'products', 'categories', 'brands', 'inventory', 'inventory/movement', 'inventory/history', 'inventory/batches', 'purchases', 'suppliers', 'enquiries', 'customers',
            'prescriptions', 'orders', 'payments', 'carts', 'coupons', 'shipping', 'content/homepage', 'content/about', 'content-blocks', 'services', 'gallery', 'blogs', 'testimonials', 'pages', 'faqs',
            'reports', 'activity', 'settings', 'users', 'roles', 'notifications', 'profile'] as $uri) {
            $this->actingAs($admin)->get('/admin/'.$uri)->assertOk();
        }
    }

    public function test_deactivated_staff_are_signed_out(): void
    {
        $u = $this->staff('Manager');
        $this->actingAs($u)->get('/admin/dashboard')->assertOk();
        $u->update(['is_active' => false]);
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_cannot_delete_self_or_last_super_admin(): void
    {
        $this->seedRoles();
        $admin = User::query()->create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => bcrypt('x'), 'is_active' => true]);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin)->delete('/admin/users/'.$admin->id)->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_role_editor_cannot_grant_permissions_it_does_not_hold(): void
    {
        $this->seedRoles();
        $mgr = $this->staff('Manager');
        $mgr->givePermissionTo(['roles.view', 'roles.create', 'roles.edit']);
        $this->actingAs($mgr)->post('/admin/roles', ['name' => 'Helper', 'permissions' => ['dashboard.view', 'users.delete', 'settings.custom_code']])->assertRedirect();
        $role = \Spatie\Permission\Models\Role::findByName('Helper');
        $this->assertTrue($role->hasPermissionTo('dashboard.view'));
        $this->assertFalse($role->hasPermissionTo('users.delete'));
        $this->assertFalse($role->hasPermissionTo('settings.custom_code'));
    }
}
