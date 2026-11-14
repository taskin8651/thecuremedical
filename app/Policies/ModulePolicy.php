<?php

namespace App\Policies;

use App\Models\User;

/**
 * Base policy. Each concrete policy only declares which permission module it guards:
 * "products" => products.view / .create / .edit / .delete
 * Super Admin is granted everything by Gate::before() in AuthServiceProvider.
 */
abstract class ModulePolicy
{
    protected string $module;

    public function viewAny(User $user): bool
    {
        return $user->can($this->module.'.view');
    }

    public function view(User $user, mixed $model = null): bool
    {
        return $user->can($this->module.'.view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->module.'.create');
    }

    public function update(User $user, mixed $model = null): bool
    {
        return $user->can($this->module.'.edit');
    }

    public function delete(User $user, mixed $model = null): bool
    {
        return $user->can($this->module.'.delete');
    }
}
