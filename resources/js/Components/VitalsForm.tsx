import { useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

interface Props {
    encounterId: number;
    onSuccess?: () => void;
}

interface VitalsData {
    temperature_c: string;
    pulse_bpm: string;
    bp_systolic: string;
    bp_diastolic: string;
    spo2_pct: string;
    respiratory_rate: string;
    weight_kg: string;
    height_cm: string;
    glucose_mmol: string;
    pain_scale: string;
    notes: string;
}

function Field({ label, name, form, unit, min, max, step = '0.1' }: {
    label: string;
    name: keyof VitalsData;
    form: ReturnType<typeof useForm<VitalsData>>;
    unit?: string;
    min?: string;
    max?: string;
    step?: string;
}) {
    return (
        <div>
            <label className="block text-xs font-medium text-gray-600 mb-0.5">{label}</label>
            <div className="flex items-center gap-1">
                <input
                    type="number"
                    step={step}
                    min={min}
                    max={max}
                    value={form.data[name]}
                    onChange={e => form.setData(name, e.target.value)}
                    className="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                />
                {unit && <span className="text-xs text-gray-500 whitespace-nowrap">{unit}</span>}
            </div>
            {form.errors[name] && <p className="mt-0.5 text-xs text-danger-600">{form.errors[name]}</p>}
        </div>
    );
}

export function VitalsForm({ encounterId, onSuccess }: Props) {
    const form = useForm<VitalsData>({
        temperature_c: '', pulse_bpm: '', bp_systolic: '', bp_diastolic: '',
        spo2_pct: '', respiratory_rate: '', weight_kg: '', height_cm: '',
        glucose_mmol: '', pain_scale: '', notes: '',
    });

    const submit: FormEventHandler = e => {
        e.preventDefault();
        form.post(`/encounters/${encounterId}/vitals`, {
            onSuccess: () => { form.reset(); onSuccess?.(); },
        });
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                <Field label="Temp" name="temperature_c" form={form} unit="°C" min="30" max="45" />
                <Field label="Pulse" name="pulse_bpm" form={form} unit="bpm" step="1" min="30" max="250" />
                <Field label="BP Systolic" name="bp_systolic" form={form} unit="mmHg" step="1" min="50" max="300" />
                <Field label="BP Diastolic" name="bp_diastolic" form={form} unit="mmHg" step="1" min="30" max="200" />
                <Field label="SpO₂" name="spo2_pct" form={form} unit="%" min="50" max="100" />
                <Field label="Resp. Rate" name="respiratory_rate" form={form} unit="/min" step="1" min="5" max="60" />
                <Field label="Weight" name="weight_kg" form={form} unit="kg" min="1" max="500" />
                <Field label="Height" name="height_cm" form={form} unit="cm" min="30" max="250" />
                <Field label="Glucose" name="glucose_mmol" form={form} unit="mmol/L" min="1" max="50" />
                <Field label="Pain Scale" name="pain_scale" form={form} unit="/10" step="1" min="0" max="10" />
            </div>
            <div>
                <label className="block text-xs font-medium text-gray-600 mb-0.5">Notes</label>
                <textarea
                    value={form.data.notes}
                    onChange={e => form.setData('notes', e.target.value)}
                    rows={2}
                    className="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                />
            </div>
            <button
                type="submit"
                disabled={form.processing}
                className="rounded bg-primary-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60"
            >
                {form.processing ? 'Saving…' : 'Record Vitals'}
            </button>
        </form>
    );
}
