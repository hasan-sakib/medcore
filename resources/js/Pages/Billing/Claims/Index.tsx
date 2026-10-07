import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, PaginatedResource, Claim, Invoice, Patient, InsurancePolicy } from '@/types';

type ClaimRow = Claim & {
    invoice: Pick<Invoice, 'id' | 'invoice_number' | 'total_amount'>;
    patient: Pick<Patient, 'id' | 'first_name' | 'last_name' | 'mrn'>;
    insurance_policy: Pick<InsurancePolicy, 'id' | 'provider_name' | 'policy_number'>;
};

interface Props extends PageProps {
    claims: PaginatedResource<ClaimRow>;
    filters: { status?: string };
}

const STATUS_CHIP: Record<string, string> = {
    draft:              'bg-gray-100 text-gray-600',
    submitted:          'bg-blue-100 text-blue-700',
    under_review:       'bg-yellow-100 text-yellow-700',
    approved:           'bg-green-100 text-green-700',
    partially_approved: 'bg-orange-100 text-orange-700',
    rejected:           'bg-red-100 text-red-700',
    paid:               'bg-emerald-100 text-emerald-700',
};

function UpdateStatusModal({ claim, onClose }: { claim: ClaimRow; onClose: () => void }) {
    const form = useForm<{ status: string; amount_approved: string; amount_paid: string; notes: string }>({
        status:          claim.status,
        amount_approved: String(claim.amount_approved ?? ''),
        amount_paid:     String(claim.amount_paid ?? ''),
        notes:           claim.notes ?? '',
    });

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div className="bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 space-y-4">
                <h2 className="font-semibold text-gray-900">Update Claim {claim.claim_number}</h2>
                <div>
                    <label className="form-label">Status</label>
                    <select className="form-input" value={form.data.status} onChange={e => form.setData('status', e.target.value)}>
                        {Object.keys(STATUS_CHIP).map(s => <option key={s} value={s}>{s.replace('_', ' ')}</option>)}
                    </select>
                </div>
                {['approved','partially_approved'].includes(form.data.status) && (
                    <div>
                        <label className="form-label">Amount Approved</label>
                        <input type="number" className="form-input" step="0.01" value={form.data.amount_approved} onChange={e => form.setData('amount_approved', e.target.value)} />
                    </div>
                )}
                {form.data.status === 'paid' && (
                    <div>
                        <label className="form-label">Amount Paid</label>
                        <input type="number" className="form-input" step="0.01" value={form.data.amount_paid} onChange={e => form.setData('amount_paid', e.target.value)} />
                    </div>
                )}
                <div>
                    <label className="form-label">Notes</label>
                    <textarea className="form-input" rows={2} value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} />
                </div>
                <div className="flex gap-2">
                    <button
                        onClick={() => form.patch(`/claims/${claim.id}/status`, { onSuccess: onClose, preserveScroll: true })}
                        disabled={form.processing}
                        className="btn-primary flex-1"
                    >
                        Update
                    </button>
                    <button onClick={onClose} className="btn-secondary flex-1">Cancel</button>
                </div>
            </div>
        </div>
    );
}

export default function ClaimsIndex({ claims, filters }: Props) {
    const [selectedClaim, setSelectedClaim] = useState<ClaimRow | null>(null);
    const [status, setStatus] = useState(filters.status ?? '');

    return (
        <AppLayout>
            <Head title="Insurance Claims" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Insurance Claims</h1>
                    <select
                        className="form-input w-48 text-sm"
                        value={status}
                        onChange={e => {
                            setStatus(e.target.value);
                            router.get('/claims', { status: e.target.value }, { replace: true, preserveScroll: true });
                        }}
                    >
                        <option value="">All statuses</option>
                        {Object.keys(STATUS_CHIP).map(s => <option key={s} value={s}>{s.replace('_', ' ')}</option>)}
                    </select>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                {['Claim #', 'Invoice', 'Patient', 'Provider / Policy', 'Claimed', 'Approved', 'Status', ''].map(h => (
                                    <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 text-sm">
                            {claims.data.map(claim => (
                                <tr key={claim.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 font-mono text-xs font-semibold text-primary-700">{claim.claim_number}</td>
                                    <td className="px-4 py-3 font-mono text-xs">{claim.invoice.invoice_number}</td>
                                    <td className="px-4 py-3">
                                        <div className="font-medium">{claim.patient.first_name} {claim.patient.last_name}</div>
                                        <div className="text-xs text-gray-400">{claim.patient.mrn}</div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div>{claim.insurance_policy.provider_name}</div>
                                        <div className="text-xs text-gray-400">{claim.insurance_policy.policy_number}</div>
                                    </td>
                                    <td className="px-4 py-3">${Number(claim.amount_claimed).toFixed(2)}</td>
                                    <td className="px-4 py-3">{claim.amount_approved ? `$${Number(claim.amount_approved).toFixed(2)}` : '—'}</td>
                                    <td className="px-4 py-3">
                                        <span className={`text-xs px-2 py-0.5 rounded-full capitalize font-medium ${STATUS_CHIP[claim.status] ?? ''}`}>
                                            {claim.status.replace('_', ' ')}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <button onClick={() => setSelectedClaim(claim)} className="text-xs text-primary-600 hover:underline">
                                            Update
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {claims.data.length === 0 && (
                        <p className="text-center py-10 text-sm text-gray-400">No claims found.</p>
                    )}
                </div>
            </div>

            {selectedClaim && (
                <UpdateStatusModal claim={selectedClaim} onClose={() => setSelectedClaim(null)} />
            )}
        </AppLayout>
    );
}
