<?php

namespace App\Http\Controllers;

use App\Models\ClinicalNote;
use App\Models\Encounter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClinicalNoteController extends Controller
{
    public function store(Request $request, Encounter $encounter): RedirectResponse
    {
        $this->authorize('create', ClinicalNote::class);

        $validated = $request->validate([
            'note_type' => ['required', 'in:soap,progress,procedure,discharge_summary,referral'],
            'subjective' => ['nullable', 'string'],
            'objective' => ['nullable', 'string'],
            'assessment' => ['nullable', 'string'],
            'plan' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
        ]);

        $note = new ClinicalNote($validated + ['author_id' => auth()->id()]);

        // Use setPhiField for encrypted fields so they store correctly
        foreach (['subjective', 'objective', 'assessment', 'plan', 'body'] as $field) {
            if (isset($validated[$field])) {
                $note->$field = $validated[$field];
            }
        }

        $encounter->clinicalNotes()->save($note);

        return back()->with('success', 'Note saved.');
    }

    public function update(Request $request, Encounter $encounter, ClinicalNote $clinicalNote): RedirectResponse
    {
        $this->authorize('update', $clinicalNote);

        $validated = $request->validate([
            'subjective' => ['nullable', 'string'],
            'objective' => ['nullable', 'string'],
            'assessment' => ['nullable', 'string'],
            'plan' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'is_signed' => ['boolean'],
        ]);

        if (! empty($validated['is_signed'])) {
            $validated['signed_at'] = now();
        }

        $clinicalNote->update($validated);

        return back()->with('success', 'Note updated.');
    }

    public function destroy(Encounter $encounter, ClinicalNote $clinicalNote): RedirectResponse
    {
        $this->authorize('delete', $clinicalNote);
        $clinicalNote->delete();

        return back()->with('success', 'Note removed.');
    }
}
