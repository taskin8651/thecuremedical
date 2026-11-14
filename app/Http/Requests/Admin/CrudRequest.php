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
    public function authorize(): bool
    {
        $controller = $this->route()->getController();
        $id = $this->route('record');

        if ($id) {
            return Gate::allows('update', $controller->findRecord($id));
        }

        return Gate::allows('create', $controller->modelClass());
    }

    public function rules(): array
    {
        $controller = $this->route()->getController();
        $id = $this->route('record');

        return $controller->validationRules($id ? $controller->findRecord($id) : null);
    }

    public function attributes(): array
    {
        $controller = $this->route()->getController();
        $id = $this->route('record');

        return $controller->fieldLabels($id ? $controller->findRecord($id) : null);
    }
}
