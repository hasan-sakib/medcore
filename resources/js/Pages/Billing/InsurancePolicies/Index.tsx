import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Can } from '@/Components/Can';
import { Pagination } from '@/Components/Pagination';
import type { PageProps, PaginatedResource, InsurancePolicy, Patient } from '@/types';

type PatientRef = Pick<Patient, 'id' | 'mrn' | 'first_name' | 'last_name'>;
type PolicyRow = InsurancePolicy & { patient: PatientRef; is_current: boolean };

interface Props extends PageProps {
    policies: PaginatedResource<PolicyRow>;
    patient: PatientRef | null;
    filters: { search?: string; status?: string };
}

const money = (v: number | string) =>
    `$${Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function InsurancePoliciesIndex({ policies, patient, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    const applyFilters = () => {
        router.get(
            '/billing/insurance-policies',
            { search, status, ...(patient ? { patient_id: patient.id } : {}) },
            { preserveScroll: true, replace: true },
        );
    };

    const remove = (p: PolicyRow) => {
        if (confirm(`Remove policy ${p.policy_number}? If it already has claims it will be deactivated instead.`)) {
            router.delete(`/billing/insurance-policies/${p.id}`, { preserveScroll: true });
        }
    };

    const createHref = patient
        ? `/billing/insurance-policies/create?patient_id=${patient.id}`
        : '/billing/insurance-policies/create';

    return (
        <AppLayout>
            <Head title="Insurance Policies" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-gray-900">
                            {patient ? `Insurance: ${patient.first_name} ${patient.last_name}` : 'Insurance Policies'}
                        </h1>
                        {patient ? (
                            <p className="text-sm text-gray-500">
                                <span className="font-mono">{patient.mrn}</span>
                                {' · '}
                                <Link href="/billing/insurance-policies" className="text-primary-600 hover:underline">All patients</Link>
                            </p>
                        ) : (
                            <p className="text-sm text-gray-500">Patient insurance policies used for claims.</p>
                        )}
                    </div>
                    <Can permission="insurance-policies.manage">
                        <Link href={createHref} className="btn-primary text-sm">+ Add Policy</Link>
                    </Can>
                </div>

                <div className="flex flex-wrap gap-3">
                    <input type="text" className="form-input max-w-xs" placeholder="Search provider, policy # or MRN…"
                        value={search} onChange={e => setSearch(e.target.value)}
                        onKeyDown={e => e.key === 'Enter' && applyFilters()} />
                    <select className="form-input w-36" value={status} onChange={e => setStatus(e.target.value)}>
                        <option value="">Any status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <button onClick={applyFilters} className="btn-secondary">Filter</button>
                </div>

                <div className="card overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                {['Patient', 'Provider', 'Policy #', 'Coverage', 'Copay', 'Valid', 'Status', ''].map(h => (
                                    <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {policies.data.map(p => (
                                <tr key={p.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        <Link href={`/billing/insurance-policies?patient_id=${p.patient.id}`} className="text-sm font-medium text-gray-900 hover:underline">
                                            {p.patient.first_name} {p.patient.last_name}
                                        </Link>
                                        <div className="text-xs text-gray-400 font-mono">{p.patient.mrn}</div>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-900">{p.provider_name}</td>
                                    <td className="px-4 py-3 font-mono text-xs text-gray-700">
                                        {p.policy_number}
                                        {p.group_number && <div className="text-gray-400">Grp {p.group_number}</div>}
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-600">
                                        <span className="capitalize">{p.coverage_type}</span>
                                        <div className="text-xs text-gray-400">
                                            {p.coverage_limit !== null ? `Limit ${money(p.coverage_limit)}` : 'No limit'}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-sm">{money(p.copay_amount)}</td>
                                    <td className="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                                        {p.valid_from} → {p.valid_until ?? 'open'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                                            p.is_current ? 'bg-green-100 text-green-700'
                                                : p.is_active ? 'bg-yellow-100 text-yellow-700'
                                                : 'bg-gray-100 text-gray-500'
                                        }`}>
                                            {p.is_current ? 'Current' : p.is_active ? 'Not in period' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                                        <Can permission="insurance-policies.manage">
                                            <Link href={`/billing/insurance-policies/${p.id}/edit`} className="text-xs text-primary-600 hover:underline">Edit</Link>
                                            <button onClick={() => remove(p)} className="text-xs text-danger-600 hover:underline">Remove</button>
                                        </Can>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {policies.data.length === 0 && (
                        <p className="text-center py-10 text-sm text-gray-400">No insurance policies found.</p>
                    )}
                </div>

                <Pagination data={policies} />
            </div>
        </AppLayout>
    );
}
