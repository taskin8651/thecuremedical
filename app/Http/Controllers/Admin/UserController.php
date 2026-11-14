<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * Staff accounts. Guards: nobody can delete or deactivate themselves, only a Super Admin can
 * touch Super Admin accounts, and staff can only hand out roles that do not exceed their own access.
 */
class UserController extends CrudController
{
    protected string $modelClass = User::class;
    protected string $route = 'admin.users';
    protected string $title = 'Admin users';
    protected string $singular = 'User';
    protected array $with = ['roles'];
    protected array $search = ['name', 'email', 'phone'];

    protected function columns(): array
    {
        return [
            ['label' => 'Name', 'key' => 'name', 'link' => true],
            ['label' => 'Email', 'key' => 'email'],
            ['label' => 'Role', 'value' => fn ($r) => $r->roles->pluck('name')->implode(', ') ?: 'No role'],
            ['label' => 'Last sign-in', 'key' => 'last_login_at', 'type' => 'datetime'],
            ['label' => 'Status', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Active', 'no' => 'Disabled', 'tones' => ['danger']],
        ];
    }

    protected function filters(): array
    {
        return [['name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Disabled']]];
    }

    protected function bulkActions(): array
    {
        return ['activate' => ['label' => 'Enable'], 'deactivate' => ['label' => 'Disable']];
    }

    /** @return array<string,string> role name => role name that the signed-in user may assign */
    protected function assignableRoles(): array
    {
        $me = auth()->user();
        $mine = $me->getAllPermissions()->pluck('name');
        $out = [];
        foreach (Role::query()->with('permissions')->orderBy('name')->get() as $role) {
            if ($me->isSuperAdmin()) {
                $out[$role->name] = $role->name;
            } elseif ($role->name !== 'Super Admin' && $role->permissions->pluck('name')->diff($mine)->isEmpty()) {
                $out[$role->name] = $role->name;
            }
        }

        return $out;
    }

    protected function fields(?Model $model = null): array
    {
        $roles = $this->assignableRoles();
        $strong = Password::min(10)->letters()->mixedCase()->numbers();

        return [
            ['name' => 'name', 'rules' => 'required|string|max:120'],
            ['name' => 'email', 'type' => 'email', 'rules' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($model?->id)]],
            ['name' => 'phone', 'type' => 'tel', 'rules' => 'nullable|string|max:30'],
            [
                'name' => 'role', 'type' => 'select', 'options' => $roles, 'virtual' => true, 'empty' => 'Choose a role...',
                'value' => $model?->roles->first()?->name,
                'rules' => ['required', Rule::in(array_keys($roles))],
                'help' => 'Roles control what this person can see and do.',
            ],
            [
                'name' => 'password', 'type' => 'password', 'label' => $model ? 'New password (leave blank to keep)' : 'Password',
                'rules' => $model ? ['nullable', 'confirmed', $strong] : ['required', 'confirmed', $strong],
                'help' => 'At least 10 characters with upper and lower case letters and a number.',
            ],
            ['name' => 'password_confirmation', 'type' => 'password', 'label' => 'Confirm password', 'virtual' => true, 'rules' => 'nullable'],
            ['name' => 'is_active', 'label' => 'Account active', 'type' => 'checkbox', 'default' => true, 'rules' => 'boolean'],
        ];
    }

    private function guardTarget(User $target): ?string
    {
        $me = auth()->user();
        if ($target->isSuperAdmin() && ! $me->isSuperAdmin()) {
            return 'Only a Super Admin can manage a Super Admin account.';
        }

        return null;
    }

    public function edit($record)
    {
        $user = $this->findRecord($record);
        abort_if($this->guardTarget($user), 403);

        return parent::edit($record);
    }

    public function update(\App\Http\Requests\Admin\CrudRequest $request, $record)
    {
        $user = $this->findRecord($record);
        abort_if($this->guardTarget($user), 403);

        if ($user->id === auth()->id() && ! $request->boolean('is_active')) {
            return back()->withInput()->with('error', 'You cannot disable your own account.');
        }
        if ($user->isSuperAdmin() && $request->input('role') !== 'Super Admin' && $this->isLastSuperAdmin($user)) {
            return back()->withInput()->with('error', 'This is the only Super Admin. Create another one before changing this role.');
        }
        if ($user->isSuperAdmin() && ! $request->boolean('is_active') && $this->isLastSuperAdmin($user)) {
            return back()->withInput()->with('error', 'The only Super Admin cannot be disabled.');
        }

        return parent::update($request, $record);
    }

    protected function afterSave(Model $model, Request $request, bool $isNew): void
    {
        $role = $request->input('role');
        $before = $model->roles->pluck('name')->all();
        $model->syncRoles([$role]);
        if ($before !== [$role]) {
            ActivityLog::record('role_changed', $model, "{$model->name}: role set to {$role}", ['from' => $before, 'to' => $role]);
        }
        if (! $isNew && $request->filled('password')) {
            ActivityLog::record('password_reset_by_admin', $model, "Password changed for {$model->name}");
        }
        if (! $model->is_active) {
            // End any sessions the user still has open elsewhere on their next request.
            $model->forceFill(['remember_token' => null])->save();
        }
    }

    private function isLastSuperAdmin(User $user): bool
    {
        return User::role('Super Admin')->where('is_active', true)->where('id', '!=', $user->id)->doesntExist();
    }

    protected function blockDelete(Model $model): ?string
    {
        if ($model->id === auth()->id()) {
            return 'You cannot delete your own account.';
        }
        if ($this->guardTarget($model)) {
            return 'Only a Super Admin can delete a Super Admin account.';
        }
        if ($model->isSuperAdmin() && $this->isLastSuperAdmin($model)) {
            return 'The only Super Admin cannot be deleted.';
        }

        return null;
    }

    protected function applyBulk(string $action, Model $record): bool
    {
        if ($record->id === auth()->id() || $this->guardTarget($record) || ($record->isSuperAdmin() && $action === 'deactivate' && $this->isLastSuperAdmin($record))) {
            return false;
        }

        return parent::applyBulk($action, $record);
    }
}
