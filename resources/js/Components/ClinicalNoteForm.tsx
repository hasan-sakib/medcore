import { useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import type { ClinicalNote } from '@/types';

type NoteType = ClinicalNote['note_type'];

const NOTE_TYPES: { value: NoteType; label: string }[] = [
    { value: 'soap', label: 'SOAP' },
    { value: 'progress', label: 'Progress' },
    { value: 'procedure', label: 'Procedure' },
    { value: 'discharge_summary', label: 'Discharge Summary' },
    { value: 'referral', label: 'Referral' },
];

interface Props {
    encounterId: number;
    existingNote?: ClinicalNote;
    onSuccess?: () => void;
}

interface NoteData {
    note_type: NoteType;
    subjective: string;
    objective: string;
    assessment: string;
    plan: string;
    body: string;
}

function TextArea({ label, name, form }: {
    label: string;
    name: keyof NoteData;
    form: ReturnType<typeof useForm<NoteData>>;
}) {
    return (
        <div>
            <label className="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">{label}</label>
            <textarea
                value={form.data[name] as string}
                onChange={e => form.setData(name, e.target.value)}
                rows={3}
                className="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
            />
            {form.errors[name] && <p className="mt-0.5 text-xs text-danger-600">{form.errors[name]}</p>}
        </div>
    );
}

export function ClinicalNoteForm({ encounterId, existingNote, onSuccess }: Props) {
    const form = useForm<NoteData>({
        note_type: existingNote?.note_type ?? 'soap',
        subjective: existingNote?.subjective ?? '',
        objective:  existingNote?.objective ?? '',
        assessment: existingNote?.assessment ?? '',
        plan:       existingNote?.plan ?? '',
        body:       existingNote?.body ?? '',
    });

    const isSoap = form.data.note_type === 'soap';

    const submit: FormEventHandler = e => {
        e.preventDefault();
        const url = existingNote
            ? `/encounters/${encounterId}/clinical-notes/${existingNote.id}`
            : `/encounters/${encounterId}/clinical-notes`;
        const method = existingNote ? 'patch' : 'post';

        form[method](url, { onSuccess: () => { if (!existingNote) form.reset(); onSuccess?.(); } });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <div className="flex gap-1 rounded bg-gray-100 p-1 w-fit">
                {NOTE_TYPES.map(({ value, label }) => (
                    <button
                        key={value}
                        type="button"
                        onClick={() => form.setData('note_type', value)}
                        className={`rounded px-3 py-1 text-xs font-medium transition-colors ${
                            form.data.note_type === value
                                ? 'bg-white shadow text-primary-700'
                                : 'text-gray-600 hover:text-gray-800'
                        }`}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {isSoap ? (
                <>
                    <TextArea label="Subjective (S)" name="subjective" form={form} />
                    <TextArea label="Objective (O)" name="objective" form={form} />
                    <TextArea label="Assessment (A)" name="assessment" form={form} />
                    <TextArea label="Plan (P)" name="plan" form={form} />
                </>
            ) : (
                <TextArea label="Note" name="body" form={form} />
            )}

            <button
                type="submit"
                disabled={form.processing}
                className="rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60"
            >
                {form.processing ? 'Saving…' : existingNote ? 'Update Note' : 'Save Note'}
            </button>
        </form>
    );
}
