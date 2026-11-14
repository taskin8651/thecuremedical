<?php

namespace App\Http\Controllers\Admin;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;

/** Only add reviews that real customers have given. Do not invent testimonials. */
class TestimonialController extends CrudController
{
    protected string $modelClass = Testimonial::class;
    protected string $route = 'admin.testimonials';
    protected string $title = 'Testimonials';
    protected string $singular = 'Testimonial';
    protected array $with = ['media'];
    protected array $search = ['name', 'review'];
    protected string $orderBy = 'sort_order';
    protected string $orderDir = 'asc';

    protected function columns(): array
    {
        return [
            ['label' => '', 'type' => 'image', 'value' => fn ($r) => $r->getFirstMediaUrl('photo', 'thumb') ?: null],
            ['label' => 'Customer', 'key' => 'name', 'link' => true],
            ['label' => 'Rating', 'value' => fn ($r) => str_repeat('★', (int) $r->rating).str_repeat('☆', 5 - (int) $r->rating)],
            ['label' => 'Review', 'value' => fn ($r) => \Illuminate\Support\Str::limit($r->review, 80)],
            ['label' => 'Source', 'key' => 'source'],
            ['label' => 'Order', 'key' => 'sort_order'],
            ['label' => 'Status', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Visible', 'no' => 'Hidden'],
        ];
    }

    protected function bulkActions(): array
    {
        return ['activate' => ['label' => 'Show'], 'deactivate' => ['label' => 'Hide'], 'delete' => ['label' => 'Delete', 'ability' => 'delete']];
    }

    protected function fields(?Model $model = null): array
    {
        return [
            ['name' => 'name', 'label' => 'Customer name', 'rules' => 'required|string|max:120'],
            ['name' => 'source', 'rules' => 'nullable|string|max:60', 'placeholder' => 'Google review', 'help' => 'Where the review came from.'],
            ['name' => 'review', 'type' => 'textarea', 'rows' => 4, 'col' => 12, 'rules' => 'required|string|max:1200', 'help' => 'Use the customer\'s own words from a real review.'],
            ['name' => 'rating', 'type' => 'select', 'options' => [5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star'], 'default' => 5, 'col' => 3, 'rules' => 'required|integer|between:1,5'],
            ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'default' => 0, 'col' => 3, 'rules' => 'required|integer|min:0|max:9999'],
            ['name' => 'is_active', 'label' => 'Visible on website', 'type' => 'checkbox', 'default' => true, 'col' => 3, 'rules' => 'boolean'],
            ['name' => 'photo', 'label' => 'Profile image (optional)', 'type' => 'image', 'collection' => 'photo'],
        ];
    }
}
