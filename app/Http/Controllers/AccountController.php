<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.show', [
            'notifications' => $request->user()->notifications()->latest()->paginate(10),
        ]);
    }

    public function readNotifications(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route('account')->with('status', 'Все уведомления отмечены как прочитанные.');
    }
}
