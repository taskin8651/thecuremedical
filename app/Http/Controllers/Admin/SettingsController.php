<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One controller for the Settings screen and the Homepage manager. Both read their tabs and
 * field definitions from config/cure.php and save through the same validated path.
 */
class SettingsController extends Controller
{
    /** Switching these on needs a Super Admin and an explicit confirmation. */
    private const COMPLIANCE = ['ordering_enabled', 'payments_enabled', 'prescriptions_enabled'];

    public function show(Request $request)
    {
        abort_unless($request->user()->can('settings.view'), 403);

        return $this->render($request, 'settings');
    }

    public function homepage(Request $request)
    {
        abort_unless($request->user()->can('content.view'), 403);

        return $this->render($request, 'homepage');
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->can('settings.edit'), 403);

        return $this->save($request, 'settings');
    }

    public function updateHomepage(Request $request)
    {
        abort_unless($request->user()->can('content.edit'), 403);

        return $this->save($request, 'homepage');
    }

    private function tabs(string $scope): array
    {
        $tabs = config("cure.tabs.$scope");
        if ($scope === 'settings' && ! auth()->user()->can('settings.custom_code')) {
            unset($tabs['code']);
        }

        return $tabs;
    }

    private function render(Request $request, string $scope)
    {
        $tabs = $this->tabs($scope);
        $active = array_key_exists((string) $request->query('tab'), $tabs) ? $request->query('tab') : array_key_first($tabs);

        return view('admin.settings.show', [
            'scope' => $scope,
            'tabs' => $tabs,
            'active' => $active,
            'defs' => config('cure.settings'),
            'canEdit' => $request->user()->can($scope === 'settings' ? 'settings.edit' : 'content.edit'),
            'canToggleCompliance' => $request->user()->isSuperAdmin(),
            'compliance' => self::COMPLIANCE,
        ]);
    }

    private function save(Request $request, string $scope)
    {
        $tabs = $this->tabs($scope);
        $tab = (string) $request->input('tab');
        abort_unless(array_key_exists($tab, $tabs), 422);

        $defs = config('cure.settings');
        $keys = array_values(array_filter($tabs[$tab][1], fn ($k) => isset($defs[$k])));
        $rules = [];
        foreach ($keys as $k) {
            $rules[$k] = $defs[$k][3];
        }
        $validated = $request->validate($rules);

        // Compliance switches: Super Admin only, with an explicit confirmation when turning one on.
        $turnedOn = [];
        foreach (array_intersect($keys, self::COMPLIANCE) as $k) {
            $new = $request->boolean($k);
            $old = Settings::bool($k);
            if ($new !== $old) {
                abort_unless($request->user()->isSuperAdmin(), 403, 'Only a Super Admin can change this setting.');
                if ($new) {
                    $turnedOn[] = $k;
                }
            }
        }
        if ($turnedOn && ! $request->boolean('confirm_compliance')) {
            return back()->withInput()->with('error', 'Tick the confirmation box to enable ordering, payments or prescriptions.');
        }

        $changed = [];
        foreach ($keys as $k) {
            [$type] = $defs[$k];
            $old = Settings::all()[$k] ?? null;

            if ($type === 'image') {
                $file = $request->file($k);
                if ($file) {
                    $path = $file->storeAs('settings', Str::uuid().'.'.$file->extension(), 'public');
                    $this->forgetFile($old);
                    Settings::set($k, $path, $scope);
                    $changed[] = $k;
                } elseif ($request->boolean('remove_'.$k) && $old) {
                    $this->forgetFile($old);
                    Settings::set($k, null, $scope);
                    $changed[] = $k;
                }
                continue;
            }

            $value = $type === 'checkbox' ? ($request->boolean($k) ? '1' : '0') : ($validated[$k] ?? null);
            if ($type === 'color' && $value) {
                $value = strtoupper($value);
            }
            if ((string) $old !== (string) $value) {
                Settings::set($k, $value, $scope);
                $changed[] = $k;
            }
        }
        Settings::flush();

        if ($changed) {
            // Script and code values are never copied into the log.
            $safe = array_values(array_diff($changed, ['header_scripts', 'footer_scripts', 'custom_css']));
            ActivityLog::record('settings_updated', null, 'Updated '.($scope === 'homepage' ? 'homepage' : 'settings').' ('.$tabs[$tab][0].')', ['keys' => $safe, 'code_changed' => count($safe) !== count($changed)]);
            foreach ($turnedOn as $k) {
                ActivityLog::record('feature_enabled', null, 'ENABLED '.$defs[$k][1].' after owner confirmation');
            }
        }

        return redirect()->route($scope === 'homepage' ? 'admin.content.homepage' : 'admin.settings.show', ['tab' => $tab])
            ->with('success', $changed ? 'Saved.' : 'Nothing changed.');
    }

    private function forgetFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
