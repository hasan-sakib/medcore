<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Portal/Profile', [
            'patient' => $request->user()->patient,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $patient = $request->user()->patient;
        $patient->update(array_filter($data, fn ($v) => $v !== null));

        // Keep portal user email in sync if changed
        if (isset($data['email'])) {
            $request->user()->update(['email' => $data['email']]);
        }

        return back()->with('success', 'Profile updated.');
    }
}
