<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PrescriptionEnquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Staff-only prescription register. Files go to the PRIVATE disk and can only be downloaded
 * through an authorised, logged request. Nothing here approves, dispenses or advises
 * automatically: a person with the "verify" permission must review and verify each one.
 */
class PrescriptionController extends Controller
{
    /** status => statuses it can move to (always a manual action by a staff member) */
    private const FLOW = [
        'received' => ['under_review', 'rejected'],
        'under_review' => ['verified', 'rejected'],
        'verified' => ['completed', 'under_review'],
        'rejected' => ['under_review'],
        'completed' => [],
    ];

    private function disabled()
    {
        return view('admin.partials.disabled', [
            'title' => 'Prescriptions', 'what' => 'Prescription workflow', 'setting' => 'prescriptions_enabled',
            'text' => 'Handling prescriptions has legal requirements. This staff-only register stays off until the owner or a registered pharmacist confirms the rules that apply. There is no public upload on the website.',
        ]);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', PrescriptionEnquiry::class);
        if (! feature('prescriptions')) {
            return $this->disabled();
        }

        $query = PrescriptionEnquiry::query()->withCount('media')->latest('id');
        if (array_key_exists((string) $request->input('status'), PrescriptionEnquiry::STATUSES)) {
            $query->where('status', $request->input('status'));
        }
        if (($q = trim((string) $request->input('q'))) !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(fn ($w) => $w->where('reference', 'like', $like)->orWhere('name', 'like', $like)->orWhere('phone', 'like', $like));
        }

        return view('admin.prescriptions.index', ['rows' => $query->paginate(20)->withQueryString()]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', PrescriptionEnquiry::class);
        if (! feature('prescriptions')) {
            return $this->disabled();
        }

        return view('admin.prescriptions.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', PrescriptionEnquiry::class);
        abort_unless(feature('prescriptions'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:8192'],
        ]);

        $rx = DB::transaction(function () use ($request, $data) {
            $rx = PrescriptionEnquiry::query()->create([
                'name' => $data['name'], 'phone' => $data['phone'] ?? null, 'email' => $data['email'] ?? null,
                'notes' => $data['notes'] ?? null, 'status' => 'received', 'created_by' => auth()->id(),
            ]);
            foreach ($request->file('attachments', []) as $file) {
                $rx->addMedia($file)->usingName('Attachment')->usingFileName(Str::uuid().'.'.$file->extension())->toMediaCollection('attachments');
            }

            return $rx;
        });
        ActivityLog::record('created', $rx, "Prescription {$rx->reference} registered");

        return redirect()->route('admin.prescriptions.show', $rx->id)->with('success', "Registered as {$rx->reference}. It still needs review by a pharmacist.");
    }

    public function show(PrescriptionEnquiry $prescription)
    {
        $this->authorize('view', $prescription);
        abort_unless(feature('prescriptions'), 404);
        ActivityLog::record('viewed', $prescription, "Prescription {$prescription->reference} viewed");

        $prescription->load(['verifier', 'creator']);
        $trail = ActivityLog::query()->with('user')->where('subject_type', PrescriptionEnquiry::class)->where('subject_id', $prescription->id)->latest('id')->limit(30)->get();

        return view('admin.prescriptions.show', [
            'rx' => $prescription,
            'files' => $prescription->getMedia('attachments'),
            'next' => self::FLOW[$prescription->status] ?? [],
            'trail' => $trail,
        ]);
    }

    public function update(Request $request, PrescriptionEnquiry $prescription)
    {
        $this->authorize('update', $prescription);
        abort_unless(feature('prescriptions'), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(PrescriptionEnquiry::STATUSES))],
            'staff_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $from = $prescription->status;
        $to = $data['status'];

        if ($to !== $from) {
            if (! in_array($to, self::FLOW[$from] ?? [], true)) {
                return back()->with('error', 'A prescription cannot move from '.PrescriptionEnquiry::STATUSES[$from].' to '.PrescriptionEnquiry::STATUSES[$to].'.');
            }
            if ($to === 'verified' && ! $request->user()->can('prescriptions.verify')) {
                abort(403, 'Only an authorised pharmacist can verify a prescription.');
            }
            if ($to === 'rejected' && trim((string) ($data['staff_notes'] ?? $prescription->staff_notes)) === '') {
                return back()->withInput()->with('error', 'Add a note explaining why it was rejected.');
            }
        }

        $prescription->staff_notes = $data['staff_notes'] ?? $prescription->staff_notes;
        $prescription->status = $to;
        if ($to !== $from) {
            if ($to === 'verified') {
                $prescription->verified_by = auth()->id();
                $prescription->verified_at = now();
            } elseif ($from === 'verified') {
                $prescription->verified_by = null;
                $prescription->verified_at = null;
            }
        }
        $prescription->save();
        if ($to !== $from) {
            ActivityLog::record('status_changed', $prescription, "Prescription {$prescription->reference}: {$from} to {$to}");
        }

        return back()->with('success', 'Prescription updated.');
    }

    public function destroy(PrescriptionEnquiry $prescription)
    {
        $this->authorize('delete', $prescription);
        abort_unless(feature('prescriptions'), 403);
        ActivityLog::record('deleted', $prescription, "Prescription {$prescription->reference} deleted");
        $prescription->delete();

        return redirect()->route('admin.prescriptions.index')->with('success', 'Prescription removed from the register.');
    }

    public function download(Request $request, PrescriptionEnquiry $prescription, Media $media)
    {
        abort_unless($request->user()->can('prescriptions.download'), 403);
        abort_unless(feature('prescriptions'), 404);
        abort_unless($media->model_type === PrescriptionEnquiry::class && (int) $media->model_id === (int) $prescription->id && $media->collection_name === 'attachments', 404);

        ActivityLog::record('downloaded', $prescription, "Prescription {$prescription->reference} file downloaded");

        return response()->download($media->getPath(), 'prescription-'.$prescription->reference.'-'.$media->id.'.'.pathinfo($media->file_name, PATHINFO_EXTENSION), [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
