<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PatientPortalAccountController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        if ($patient->portalUser()->exists()) {
            return back()->with('error', 'Portal account already exists for this patient.');
        }

        $email = $patient->email ?? "{$patient->mrn}@portal.local";

        if (User::withoutTenant()->where('email', $email)->exists()) {
            return back()->with('error', "Cannot create account: email '{$email}' is already in use.");
        }

        $tempPassword = Str::random(12);

        User::create([
            'name' => $patient->first_name.' '.$patient->last_name,
            'email' => $email,
            'password' => $tempPassword,
            'tenant_id' => $patient->tenant_id,
            'patient_id' => $patient->id,
        ]);

        return back()->with('success', "Portal account created. Login email: {$email} — Temporary password: {$tempPassword}");
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $patient->portalUser()->delete();

        return back()->with('success', 'Portal account removed.');
    }
}
