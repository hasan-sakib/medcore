<?php

namespace App\Http\Controllers;

use App\Models\Encounter;
use App\Models\Vital;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VitalController extends Controller
{
    public function store(Request $request, Encounter $encounter): RedirectResponse
    {
        $validated = $request->validate([
            'temperature_c' => ['nullable', 'numeric', 'between:30,45'],
            'pulse_bpm' => ['nullable', 'integer', 'between:20,250'],
            'bp_systolic' => ['nullable', 'integer', 'between:50,300'],
            'bp_diastolic' => ['nullable', 'integer', 'between:20,200'],
            'spo2_pct' => ['nullable', 'numeric', 'between:50,100'],
            'respiratory_rate' => ['nullable', 'integer', 'between:4,60'],
            'weight_kg' => ['nullable', 'numeric', 'between:0.5,500'],
            'height_cm' => ['nullable', 'numeric', 'between:20,250'],
            'glucose_mmol' => ['nullable', 'numeric', 'between:0,50'],
            'pain_scale' => ['nullable', 'integer', 'between:0,10'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Auto-compute BMI when both weight and height are provided
        if (isset($validated['weight_kg'], $validated['height_cm']) && $validated['height_cm'] > 0) {
            $heightM = $validated['height_cm'] / 100;
            $validated['bmi'] = round($validated['weight_kg'] / ($heightM ** 2), 1);
        }

        $encounter->vitals()->create($validated + [
            'patient_id' => $encounter->patient_id,
            'recorded_by' => auth()->id(),
            'recorded_at' => now(),
        ]);

        return back()->with('success', 'Vitals recorded.');
    }

    public function update(Request $request, Encounter $encounter, Vital $vital): RedirectResponse
    {
        $validated = $request->validate([
            'temperature_c' => ['nullable', 'numeric', 'between:30,45'],
            'pulse_bpm' => ['nullable', 'integer', 'between:20,250'],
            'bp_systolic' => ['nullable', 'integer', 'between:50,300'],
            'bp_diastolic' => ['nullable', 'integer', 'between:20,200'],
            'spo2_pct' => ['nullable', 'numeric', 'between:50,100'],
            'respiratory_rate' => ['nullable', 'integer', 'between:4,60'],
            'weight_kg' => ['nullable', 'numeric', 'between:0.5,500'],
            'height_cm' => ['nullable', 'numeric', 'between:20,250'],
            'glucose_mmol' => ['nullable', 'numeric', 'between:0,50'],
            'pain_scale' => ['nullable', 'integer', 'between:0,10'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (isset($validated['weight_kg'], $validated['height_cm']) && $validated['height_cm'] > 0) {
            $heightM = $validated['height_cm'] / 100;
            $validated['bmi'] = round($validated['weight_kg'] / ($heightM ** 2), 1);
        }

        $vital->update($validated);

        return back()->with('success', 'Vitals updated.');
    }
}
