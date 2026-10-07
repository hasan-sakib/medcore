import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Can } from '@/Components/Can';
import { StatusBadge } from '@/Components/StatusBadge';
import ExpiryBadge from '@/Components/ExpiryBadge';
import type { DispenseRecord, PageProps, Prescription } from '@/types';

type Encounter = { id: number; encounter_date?: string } | null;

interface Props extends PageProps {
    prescription: Prescription & {
        encounter?: Encounter;
        dispense_records?: DispenseRecord[];
        dispenseRecords?: DispenseRecord[];
    };
}

const formatDateTime = (value: string | null | undefined): string =>
    value ? new Date(value).toLocaleString() : '—';

export default function Show({ prescription }: Props) {
    const items = prescription.items ?? [];
    const records = prescription.dispense_records ?? prescription.dispenseRecords ?? [];
    const patient = prescription.patient;
    const canDispense = prescription.status === 'pending' || prescription.status === 'partially_filled';
    const dispenseHref = patient ? `/pharmacy/dispense?search=${encodeURIComponent(patient.mrn)}` : '/pharmacy/dispense';

    return (
        <AppLayout>
            <Head title={`Prescription #${prescription.id}`} />
            <div className="space-y-6">
                <div>
                    <Link href="/prescriptions" className="text-sm text-gray-500 hover:text-gray-700">← Prescriptions</Link>
                    <div className="mt-2 flex items-start justify-between gap-4">
                        <h1 className="flex items-center gap-3 text-2xl font-semibold text-gray-900">
                            Prescription #{prescription.id}
                            <StatusBadge status={prescription.status} />
                        </h1>
                        <Can permission="dispense-records.create">
                            {canDispense && (
                                <Link href={dispenseHref} className="btn-primary">Dispense</Link>
                            )}
                        </Can>
                    </div>
                </div>

                <div className="card grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p className="text-xs uppercase tracking-wide text-gray-500">Patient</p>
                        {patient ? (
                            <Link href={`/patients/${patient.id}`} className="mt-1 block text-sm font-medium text-primary-600 hover:underline">
                                {patient.first_name} {patient.last_name}
                            </Link>
                        ) : <p className="mt-1 text-sm">—</p>}
                        {patient && <p className="text-xs text-gray-400">{patient.mrn}</p>}
                    </div>
                    <div>
                        <p className="text-xs uppercase tracking-wide text-gray-500">Prescribed by</p>
                        <p className="mt-1 text-sm text-gray-900">{prescription.prescribedBy?.name ?? '—'}</p>
                    </div>
                    <div>
                        <p className="text-xs uppercase tracking-wide text-gray-500">Prescribed at</p>
                        <p className="mt-1 text-sm text-gray-900">{formatDateTime(prescription.prescribed_at)}</p>
                    </div>
                    <div>
                        <p className="text-xs uppercase tracking-wide text-gray-500">Expires at</p>
                        <p className="mt-1 text-sm text-gray-900">{formatDateTime(prescription.expires_at)}</p>
                    </div>
                    {prescription.encounter && (
                        <div>
                            <p className="text-xs uppercase tracking-wide text-gray-500">Encounter</p>
                            <Link href={`/encounters/${prescription.encounter.id}`} className="mt-1 block text-sm text-primary-600 hover:underline">
                                Encounter #{prescription.encounter.id}
                            </Link>
                        </div>
                    )}
                    {prescription.notes && (
                        <div className="sm:col-span-2 lg:col-span-3">
                            <p className="text-xs uppercase tracking-wide text-gray-500">Notes</p>
                            <p className="mt-1 whitespace-pre-line text-sm text-gray-900">{prescription.notes}</p>
                        </div>
                    )}
                </div>

                <div className="card overflow-hidden">
                    <div className="border-b px-6 py-4">
                        <h2 className="font-medium text-gray-900">Items</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-left text-xs text-gray-500">
                                <tr>
                                    <th className="px-4 py-2">Medicine</th>
                                    <th className="px-4 py-2">Dosage</th>
                                    <th className="px-4 py-2">Frequency</th>
                                    <th className="px-4 py-2">Duration</th>
                                    <th className="px-4 py-2">Prescribed</th>
                                    <th className="px-4 py-2">Dispensed</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y bg-white">
                                {items.map((item) => {
                                    const complete = item.quantity_dispensed >= item.quantity_prescribed;
                                    return (
                                        <tr key={item.id}>
                                            <td className="px-4 py-2 font-medium">
                                                {item.medicine?.name ?? `Medicine #${item.medicine_id}`}
                                                {item.medicine?.strength && <span className="ml-1 text-gray-400">{item.medicine.strength}</span>}
                                                {item.notes && <span className="block text-xs font-normal text-gray-400">{item.notes}</span>}
                                            </td>
                                            <td className="px-4 py-2">{item.dosage_instruction}</td>
                                            <td className="px-4 py-2">{item.frequency}</td>
                                            <td className="px-4 py-2 text-gray-500">{item.duration_days ? `${item.duration_days} days` : '—'}</td>
                                            <td className="px-4 py-2">{item.quantity_prescribed}</td>
                                            <td className="px-4 py-2">
                                                <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${complete ? 'bg-green-100 text-green-700' : item.quantity_dispensed > 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'}`}>
                                                    {item.quantity_dispensed} / {item.quantity_prescribed}
                                                </span>
                                            </td>
                                        </tr>
                                    );
                                })}
                                {items.length === 0 && (
                                    <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-400">No items.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="card overflow-hidden">
                    <div className="border-b px-6 py-4">
                        <h2 className="font-medium text-gray-900">Dispense history</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-left text-xs text-gray-500">
                                <tr>
                                    <th className="px-4 py-2">Dispensed at</th>
                                    <th className="px-4 py-2">Medicine</th>
                                    <th className="px-4 py-2">Batch</th>
                                    <th className="px-4 py-2">Expiry</th>
                                    <th className="px-4 py-2">Qty</th>
                                    <th className="px-4 py-2">By</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y bg-white">
                                {records.map((r) => (
                                    <tr key={r.id}>
                                        <td className="px-4 py-2 text-gray-500">{formatDateTime(r.dispensed_at)}</td>
                                        <td className="px-4 py-2">
                                            {items.find((i) => i.medicine_id === r.medicine_id)?.medicine?.name ?? `Medicine #${r.medicine_id}`}
                                        </td>
                                        <td className="px-4 py-2 font-mono text-xs">{r.batch?.batch_number ?? '—'}</td>
                                        <td className="px-4 py-2">
                                            {r.batch?.expiry_date ? <ExpiryBadge expiryDate={r.batch.expiry_date.slice(0, 10)} /> : '—'}
                                        </td>
                                        <td className="px-4 py-2">{r.quantity_dispensed}</td>
                                        <td className="px-4 py-2 text-gray-500">{r.dispensedBy?.name ?? '—'}</td>
                                    </tr>
                                ))}
                                {records.length === 0 && (
                                    <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-400">Nothing dispensed yet.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
