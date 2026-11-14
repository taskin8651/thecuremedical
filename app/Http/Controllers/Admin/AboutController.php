<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** The About page: story, mission, vision, values and a photo. Only write what is true of the shop. */
class AboutController extends Controller
{
    private function page(): Page
    {
        return Page::query()->firstOrCreate(['slug' => 'about'], ['title' => 'About us', 'is_active' => true, 'is_system' => true]);
    }

    public function edit(Request $request)
    {
        abort_unless($request->user()->can('content.view'), 403);

        return view('admin.content.about', ['page' => $this->page()->load('media')]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->can('content.edit'), 403);
        $page = $this->page();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'content' => ['nullable', 'string', 'max:20000'],
            'mission' => ['nullable', 'string', 'max:600'],
            'vision' => ['nullable', 'string', 'max:600'],
            'values' => ['nullable', 'string', 'max:1200'],
            'seo_title' => ['nullable', 'string', 'max:120'],
            'seo_description' => ['nullable', 'string', 'max:250'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $page->fill([
            'title' => $data['title'], 'content' => $data['content'] ?? null,
            'seo_title' => $data['seo_title'] ?? null, 'seo_description' => $data['seo_description'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'meta' => ['mission' => $data['mission'] ?? null, 'vision' => $data['vision'] ?? null, 'values' => $data['values'] ?? null],
        ])->save();

        if ($request->boolean('remove_image')) {
            $page->clearMediaCollection('image');
        }
        if ($file = $request->file('image')) {
            $page->addMedia($file)->usingFileName(Str::uuid().'.'.$file->extension())->toMediaCollection('image');
        }
        ActivityLog::record('updated', $page, 'About page updated');

        return back()->with('success', 'About page saved.');
    }
}
