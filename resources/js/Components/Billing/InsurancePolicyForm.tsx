import { Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { InsurancePolicy, Patient } from '@/types';

export type PatientRef = Pick<Patient, 'id' | 'mrn' | 'first_name' | 'last_name'>;

interface Props {
    /** Existing policy (edit mode). */
    policy?: InsurancePolicy;
    /** Patient the policy belongs to (fixed); when null in create mode the user searches for one. */
    patient: PatientRef | null;
}

const COVERAGE_TYPES = ['individual', 'family', 'group', 'corporate', 'government'];

function PatientPicker({ onPick }: { onPick: (p: PatientRef) => void }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<PatientRef[]>([]);

    useEffect(() => {
        const term = query.trim();
        if (!term) { setResults([]); return; }
        const controller = new AbortController();
        const t = setTimeout(async () => {
            try {
                const res = await fetch(`/billing/insurance-policies/patient-search?q=${encodeURIComponent(term)}`, {
                    signal: controller.signal,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.ok) setResults(await res.json());
            } catch {
                // aborted or network error: ignore
            }
        }, 300);
        return () => { clearTimeout(t); controller.abort(); };
    }, [query]);

    return (
        <div className="relative">
            <input type="text" className="form-input" placeholder="Search by MRN, name, phone or national ID…"
                value={query} onChange={e => setQuery(e.target.value)} />
            {results.length > 0 && (
                <ul className="absolute z-20 mt-1 w-full max-h-60 overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg text-sm">
                    {results.map(p => (
                        <li key={p.id}>
                            <button type="button" className="w-full px-3 py-2 text-left hover:bg-gray-50"
                                onClick={() => { onPick(p); setQuery(''); setResults([]); }}>
                                <span className="font-medium">{p.first_name} {p.last_name}</span>
                                <span className="ml-2 text-xs text-gray-500 font-mono">{p.mrn}</span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

export function InsurancePolicyForm({ policy, patient }: Props) {
    const [selected, setSelected] = useState<PatientRef | null>(patient);

    const form = useForm({
        patient_id:        patient ? String(patient.id) : '',
        provider_name:     policy?.provider_name ?? '',
        policy_number:     policy?.policy_number ?? '',
        group_number:      policy?.group_number ?? '',
        coverage_type:     policy?.coverage_type ?? 'individual',
        coverage_limit:    policy?.coverage_limit != null ? String(policy.coverage_limit) : '',
        copay_amount:      policy ? String(policy.copay_amount) : '0',
        deductible_amount: policy ? String(policy.deductible_amount) : '0',
        valid_from:        policy?.valid_from?.slice(0, 10) ?? new Date().toISOString().slice(0, 10),
        valid_until:       policy?.valid_until?.slice(0, 10) ?? '',
        is_active:         policy?.is_active ?? true,
    });

    const backHref = selected
        ? `/billing/insurance-policies?patient_id=${selected.id}`
        : '/billing/insurance-policies';

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (policy) {
            form.put(`/billing/insurance-policies/${policy.id}`);
        } else {
            form.post('/billing/insurance-policies');
        }
    };

    const pick = (p: PatientRef) => {
        setSelected(p);
        form.setData('patient_id', String(p.id));
    };

    return (
        <form onSubmit={submit} className="card p-6 space-y-4">
            <div>
                <label className="form-label">Patient</label>
                {selected ? (
                    <div className="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                        <span>
                            <span className="font-medium">{selected.first_name} {selected.last_name}</span>
                            <span className="ml-2 text-xs text-gray-500 font-mono">{selected.mrn}</span>
                        </span>
                        {!policy && !patient && (
                            <button type="button" className="text-xs text-primary-600 hover:underline"
                                onClick={() => { setSelected(null); form.setData('patient_id', ''); }}>
                                Change
                            </button>
                        )}
                    </div>
                ) : (
                    <PatientPicker onPick={pick} />
                )}
                {form.errors.patient_id && <p className="form-error">{form.errors.patient_id}</p>}
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <label className="form-label" htmlFor="ip-provider">Insurance provider</label>
                    <input id="ip-provider" type="text" className="form-input" maxLength={200} value={form.data.provider_name}
                        onChange={e => form.setData('provider_name', e.target.value)} required />
                    {form.errors.provider_name && <p className="form-error">{form.errors.provider_name}</p>}
                </div>
                <div>
                    <label className="form-label" htmlFor="ip-type">Coverage type</label>
                    <input id="ip-type" type="text" list="ip-coverage-types" className="form-input" maxLength={100}
                        value={form.data.coverage_type} onChange={e => form.setData('coverage_type', e.target.value)} />
                    <datalist id="ip-coverage-types">
                        {COVERAGE_TYPES.map(t => <option key={t} value={t} />)}
                    </datalist>
                    {form.errors.coverage_type && <p className="form-error">{form.errors.coverage_type}</p>}
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <label className="form-label" htmlFor="ip-number">Policy number</label>
                    <input id="ip-number" type="text" className="form-input font-mono" maxLength={100} value={form.data.policy_number}
                        onChange={e => form.setData('policy_number', e.target.value)} required />
                    {form.errors.policy_number && <p className="form-error">{form.errors.policy_number}</p>}
                </div>
                <div>
                    <label className="form-label" htmlFor="ip-group">Group number</label>
                    <input id="ip-group" type="text" className="form-input font-mono" maxLength={100} value={form.data.group_number}
                        onChange={e => form.setData('group_number', e.target.value)} />
                    {form.errors.group_number && <p className="form-error">{form.errors.group_number}</p>}
                </div>
            </div>

            <div className="grid grid-cols-3 gap-4">
                <div>
                    <label className="form-label" htmlFor="ip-limit">Coverage limit ($)</label>
                    <input id="ip-limit" type="number" step="0.01" min="0" className="form-input" placeholder="No limit"
                        value={form.data.coverage_limit} onChange={e => form.setData('coverage_limit', e.target.value)} />
                    {form.errors.coverage_limit && <p className="form-error">{form.errors.coverage_limit}</p>}
                </div>
                <div>
                    <label className="form-label" htmlFor="ip-copay">Copay ($)</label>
                    <input id="ip-copay" type="number" step="0.01" min="0" className="form-input"
                        value={form.data.copay_amount} onChange={e => form.setData('copay_amount', e.target.value)} />
                    {form.errors.copay_amount && <p className="form-error">{form.errors.copay_amount}</p>}
                </div>
                <div>
                    <label className="form-label" htmlFor="ip-deductible">Deductible ($)</label>
                    <input id="ip-deductible" type="number" step="0.01" min="0" className="form-input"
                        value={form.data.deductible_amount} onChange={e => form.setData('deductible_amount', e.target.value)} />
                    {form.errors.deductible_amount && <p className="form-error">{form.errors.deductible_amount}</p>}
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <label className="form-label" htmlFor="ip-from">Valid from</label>
                    <input id="ip-from" type="date" className="form-input" value={form.data.valid_from}
                        onChange={e => form.setData('valid_from', e.target.value)} required />
                    {form.errors.valid_from && <p className="form-error">{form.errors.valid_from}</p>}
                </div>
                <div>
                    <label className="form-label" htmlFor="ip-until">Valid until</label>
                    <input id="ip-until" type="date" className="form-input" min={form.data.valid_from || undefined}
                        value={form.data.valid_until} onChange={e => form.setData('valid_until', e.target.value)} />
                    {form.errors.valid_until && <p className="form-error">{form.errors.valid_until}</p>}
                </div>
            </div>

            <label className="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" checked={form.data.is_active}
                    onChange={e => form.setData('is_active', e.target.checked)}
                    className="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                Active
            </label>

            <div className="flex justify-end gap-3 pt-2">
                <Link href={backHref} className="btn-secondary">Cancel</Link>
                <button type="submit" disabled={form.processing} className="btn-primary">
                    {policy ? 'Save changes' : 'Add policy'}
                </button>
            </div>
        </form>
    );
}
