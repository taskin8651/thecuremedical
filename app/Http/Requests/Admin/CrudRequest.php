<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Generic Form Request used by every CRUD module.
 * Validation rules and labels come from the module controller's field definitions.
 */
class CrudRequest extends FormRequest
{
    private function recordId(): mixed
    {
        $route = $this->route();
        if ($route->parameter('record') !== null) {
            return $route->parameter('record');
        }

        // Laravel resource routes name the parameter after the resource (product,
        // brand, etc.), while the shared CRUD request historically expected "record".
        foreach ($route->parameters() as $value) {
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    public function authorize(): bool
    {
        $controller = $this->route()->getController();
        $id = $this->recordId();

        if ($id) {
            return Gate::allows('update', $controller->findRecord($id));
        }

        return Gate::allows('create', $controller->modelClass());
    }

    public function rules(): array
    {
        $controller = $this->route()->getController();
        $id = $this->recordId();

        return $controller->validationRules($id ? $controller->findRecord($id) : null);
    }

    public function attributes(): array
    {
        $controller = $this->route()->getController();
        $id = $this->recordId();

        return $controller->fieldLabels($id ? $controller->findRecord($id) : null);
    }
}
