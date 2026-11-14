<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        $user = $request->user();
        $query = $filter === 'unread' ? $user->unreadNotifications() : $user->notifications();

        return view('admin.notifications.index', [
            'notifications' => $query->latest()->paginate(20)->withQueryString(),
            'filter' => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    /** Marks one notification as read and goes to the page it points to. */
    public function open(Request $request, string $id)
    {
        $n = $request->user()->notifications()->findOrFail($id);
        $n->markAsRead();
        $url = $n->data['url'] ?? null;

        // Only follow links that stay inside this application.
        if ($url && str_starts_with($url, url('/'))) {
            return redirect($url);
        }

        return redirect()->route('admin.notifications.index');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
