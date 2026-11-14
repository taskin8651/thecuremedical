<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and the permission matrix. "Super Admin" is locked, built-in roles cannot be renamed
 * or deleted, and a person can only grant permissions they hold themselves.
 */
class RoleController extends Controller
{
    private const LOCKED = ['Super Admin'];

    private function systemRoles(): array
    {
        return array_merge(self::LOCKED, array_keys(config('cure.roles', [])));
    }

    public function index()
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index', [
            'roles' => Role::query()->withCount(['permissions', 'users'])->orderBy('name')->get(),
            'system' => $this->systemRoles(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.form', $this->formData(new Role));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Role::class);
        $data = $this->validated($request, null);
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        ActivityLog::record('created', $role, "Role \"{$role->name}\" created with ".count($data['permissions']).' permissions');

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role)
    {
        $this->authorize('update', $role);
        abort_if(in_array($role->name, self::LOCKED, true), 403, 'The Super Admin role cannot be edited.');

        return view('admin.roles.form', $this->formData($role));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);
        abort_if(in_array($role->name, self::LOCKED, true), 403, 'The Super Admin role cannot be edited.');

        $data = $this->validated($request, $role);
        $before = $role->permissions->pluck('name')->sort()->values()->all();
        if (! in_array($role->name, $this->systemRoles(), true)) {
            $role->update(['name' => $data['name']]);
        }
        // Permissions this person cannot grant stay exactly as they were.
        $mine = $request->user()->getAllPermissions()->pluck('name');
        $kept = $role->permissions->pluck('name')->diff($mine)->all();
        $role->syncPermissions(array_values(array_unique(array_merge($data['permissions'], $kept))));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $after = $role->fresh()->permissions->pluck('name')->sort()->values()->all();
        ActivityLog::record('updated', $role, "Role \"{$role->name}\" permissions updated", [
            'added' => array_values(array_diff($after, $before)), 'removed' => array_values(array_diff($before, $after)),
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);
        if (in_array($role->name, $this->systemRoles(), true)) {
            return back()->with('error', 'Built-in roles cannot be deleted.');
        }
        if ($role->users()->exists()) {
            return back()->with('error', 'Some users still have this role. Move them to another role first.');
        }
        ActivityLog::record('deleted', $role, "Role \"{$role->name}\" deleted");
        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }

    private function formData(Role $role): array
    {
        $me = auth()->user();
        $mine = $me->getAllPermissions()->pluck('name')->all();

        return [
            'role' => $role,
            'modules' => config('cure.modules'),
            'granted' => old('permissions', $role->exists ? $role->permissions->pluck('name')->all() : []),
            'mine' => $me->isSuperAdmin() ? null : $mine,
            'isSystem' => $role->exists && in_array($role->name, $this->systemRoles(), true),
        ];
    }

    private function validated(Request $request, ?Role $role): array
    {
        $isSystem = $role && in_array($role->name, $this->systemRoles(), true);
        $valid = Permission::query()->pluck('name')->all();
        $rules = [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($valid)],
        ];
        if (! $isSystem) {
            $rules['name'] = ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9 &\-]+$/', Rule::unique('roles', 'name')->ignore($role?->id), Rule::notIn(self::LOCKED)];
        }
        $data = $request->validate($rules, ['name.regex' => 'Use letters, numbers, spaces, & and - only.']);
        $perms = $data['permissions'] ?? [];

        // No privilege escalation: only grant what you hold yourself.
        if (! $request->user()->isSuperAdmin()) {
            $mine = $request->user()->getAllPermissions()->pluck('name')->all();
            $perms = array_values(array_intersect($perms, $mine));
        }
        $data['permissions'] = $perms;
        $data['name'] = $data['name'] ?? $role->name;

        return $data;
    }
}
