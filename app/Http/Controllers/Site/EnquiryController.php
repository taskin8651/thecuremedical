<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Public enquiry and newsletter form. Spam is limited by a hidden honeypot field and the
 * throttle on the route. It is not a prescription upload and says so on the page.
 */
class EnquiryController extends Controller
{
    public function store(Request $request)
    {
        // Real visitors never fill the hidden field. Pretend success so bots learn nothing.
        if (filled($request->input('company_website'))) {
            return back()->with('enquiry_success', 'Thank you. We will get back to you soon.');
        }

        $isNewsletter = $request->input('type') === 'newsletter';
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Enquiry::TYPES))],
            'name' => [$isNewsletter ? 'nullable' : 'required', 'string', 'max:150'],
            'phone' => [$isNewsletter ? 'nullable' : 'required', 'regex:/^[0-9+\-\s]{10,15}$/'],
            'email' => [$isNewsletter ? 'required' : 'nullable', 'email:rfc', 'max:150'],
            'product_name' => ['nullable', 'string', 'max:200'],
            'message' => [$isNewsletter ? 'nullable' : 'required', 'string', 'min:10', 'max:1500'],
        ], [
            'phone.regex' => 'Enter a valid phone number (10 to 15 digits).',
            'message.min' => 'Please write at least 10 characters.',
        ]);

        if ($isNewsletter && Enquiry::query()->where('type', 'newsletter')->where('email', $data['email'])->exists()) {
            return back()->with('enquiry_success', 'You are already on the list. Thank you.');
        }

        Enquiry::query()->create([
            'type' => $data['type'],
            'name' => $data['name'] ?? 'Newsletter signup',
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'message' => strip_tags((string) ($data['message'] ?? '')),
            'status' => 'new',
            'source' => 'website',
            'ip_address' => $request->ip(),
        ]);

        return redirect(url()->previous().'#enquiry-done')->with('enquiry_success', $isNewsletter
            ? 'Thank you for subscribing.'
            : 'Thank you. We have your enquiry and will get back to you. For anything urgent, please call the store.');
    }
}
