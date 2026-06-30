import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { StatusBadge } from '@/Components/StatusBadge';
import { Can } from '@/Components/Can';
import { VitalsForm } from '@/Components/VitalsForm';
import { ClinicalNoteForm } from '@/Components/ClinicalNoteForm';
import type { PageProps, Encounter, ClinicalNote, Vital } from '@/types';

interface Props extends PageProps {
    encounter: Encounter;
}

function VitalRow({ vital }: { vital: Vital }) {
    const readings = [
        vital.temperature_c != null && `Temp ${vital.temperature_c}°C`,
        vital.pulse_bpm != null && `HR ${vital.pulse_bpm}bpm`,
        vital.bp_systolic != null && vital.bp_diastolic != null && `BP ${vital.bp_systolic}/${vital.bp_diastolic}`,
        vital.spo2_pct != null && `SpO₂ ${vital.spo2_pct}%`,
        vital.weight_kg != null && `${vital.weight_kg}kg`,
        vital.bmi != null && `BMI ${vital.bmi}`,
    ].filter(Boolean).join(' · ');

    return (
        <div className="flex justify-between items-start py-2 border-b border-gray-100 last:border-0 text-sm">
            <span className="text-gray-700">{readings || 'No measurements'}</span>
            <span className="text-xs text-gray-400 ml-4 whitespace-nowrap">
                {vital.recordedBy?.name} · {new Date(vital.recorded_at).toLocaleTimeString()}
            </span>
        </div>
    );
}

function NoteCard({ note, encounterId }: { note: ClinicalNote; encounterId: number }) {
    const [editing, setEditing] = useState(false);

    return (
        <div className="border border-gray-200 rounded-lg p-4 space-y-2">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <StatusBadge status={note.note_type} />
                    {note.is_signed && (
                        <span className="text-xs text-clinical-600 font-medium">✓ Signed</span>
                    )}
                </div>
                <div className="flex items-center gap-3 text-xs text-gray-400">
                    <span>{note.author?.name}</span>
                    {!note.is_signed && (
                        <Can permission="clinical-notes.edit">
                            <button onClick={() => setEditing(v => !v)} className="text-primary-600 hover:underline">
                                {editing ? 'Cancel' : 'Edit'}
                            </button>
                        </Can>
                    )}
                </div>
            </div>

            {editing ? (
                <ClinicalNoteForm
                    encounterId={encounterId}
                    existingNote={note}
                    onSuccess={() => { setEditing(false); router.reload(); }}
                />
            ) : (
                <div className="text-sm text-gray-700 space-y-2">
                    {note.note_type === 'soap' ? (
                        <>
                            {note.subjective && <p><span className="font-medium">S:</span> {note.subjective}</p>}
                            {note.objective && <p><span className="font-medium">O:</span> {note.objective}</p>}
                            {note.assessment && <p><span className="font-medium">A:</span> {note.assessment}</p>}
                            {note.plan && <p><span className="font-medium">P:</span> {note.plan}</p>}
                        </>
                    ) : (
                        <p>{note.body}</p>
                    )}
                </div>
            )}
        </div>
    );
}

export default function Show({ encounter: enc }: Props) {
    const [showVitalsForm, setShowVitalsForm] = useState(false);
    const [showNoteForm, setShowNoteForm] = useState(false);

    return (
        <AppLayout>
            <Head title={`Encounter — ${enc.encounter_date}`} />
            <div className="max-w-4xl space-y-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-gray-900">
                            Encounter — {enc.encounter_date}
                        </h1>
                        {enc.patient && (
                            <p className="text-sm text-gray-500 mt-0.5">
                                <Link href={`/patients/${enc.patient_id}`} className="hover:underline text-primary-600">
                                    {enc.patient.first_name} {enc.patient.last_name}
                                </Link>
                                <span className="font-mono text-xs ml-2">{enc.patient.mrn}</span>
                            </p>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <StatusBadge status={enc.encounter_type} />
                        <StatusBadge status={enc.status} />
                    </div>
                </div>

                {/* Chief Complaint */}
                {enc.chief_complaint && (
                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-3 text-sm text-yellow-800">
                        <span className="font-medium">Chief Complaint:</span> {enc.chief_complaint}
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left: Notes */}
                    <div className="lg:col-span-2 space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="font-medium text-gray-900">Clinical Notes</h2>
                            <Can permission="clinical-notes.create">
                                <button
                                    onClick={() => setShowNoteForm(v => !v)}
                                    className="rounded bg-primary-600 px-3 py-1 text-xs font-medium text-white hover:bg-primary-700"
                                >
                                    {showNoteForm ? 'Cancel' : '+ Add Note'}
                                </button>
                            </Can>
                        </div>

                        {showNoteForm && (
                            <div className="border border-primary-200 rounded-lg p-4 bg-primary-50">
                                <ClinicalNoteForm
                                    encounterId={enc.id}
                                    onSuccess={() => { setShowNoteForm(false); router.reload(); }}
                                />
                            </div>
                        )}

                        {(!enc.clinicalNotes || enc.clinicalNotes.length === 0) && !showNoteForm && (
                            <p className="text-sm text-gray-400">No notes yet</p>
                        )}
                        {enc.clinicalNotes?.map(note => (
                            <NoteCard key={note.id} note={note} encounterId={enc.id} />
                        ))}
                    </div>

                    {/* Right: Vitals */}
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="font-medium text-gray-900">Vitals</h2>
                            <Can permission="vitals.create">
                                <button
                                    onClick={() => setShowVitalsForm(v => !v)}
                                    className="rounded bg-primary-600 px-2 py-1 text-xs font-medium text-white hover:bg-primary-700"
                                >
                                    {showVitalsForm ? 'Cancel' : '+ Record'}
                                </button>
                            </Can>
                        </div>

                        {showVitalsForm && (
                            <div className="border border-primary-200 rounded-lg p-4 bg-primary-50">
                                <VitalsForm
                                    encounterId={enc.id}
                                    onSuccess={() => { setShowVitalsForm(false); router.reload(); }}
                                />
                            </div>
                        )}

                        <div className="bg-white border border-gray-200 rounded-lg p-3">
                            {(!enc.vitals || enc.vitals.length === 0) && (
                                <p className="text-sm text-gray-400">No vitals recorded</p>
                            )}
                            {enc.vitals?.map(v => <VitalRow key={v.id} vital={v} />)}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
