import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Can } from '@/Components/Can';
import { useBedBoard } from '@/hooks/useBedBoard';
import type { PageProps, BedWithAllocation, WardWithBeds, Patient, Encounter } from '@/types';

interface Props extends PageProps {
    wards: WardWithBeds[];
}

const STATUS_COLORS: Record<string, string> = {
    available:   'bg-green-100 border-green-400 text-green-800',
    occupied:    'bg-red-100 border-red-400 text-red-800',
    maintenance: 'bg-yellow-100 border-yellow-400 text-yellow-800',
    reserved:    'bg-blue-100 border-blue-400 text-blue-800',
    cleaning:    'bg-orange-100 border-orange-400 text-orange-800',
};

const STATUS_DOT: Record<string, string> = {
    available:   'bg-green-500',
    occupied:    'bg-red-500',
    maintenance: 'bg-yellow-500',
    reserved:    'bg-blue-500',
    cleaning:    'bg-orange-500',
};

interface AdmitModalState {
    bed: BedWithAllocation;
}

interface DischargeModalState {
    bed: BedWithAllocation;
    allocationId: number;
}

export default function Board({ wards, auth, tenant }: Props) {
    const allInitialBeds = wards.flatMap(w => w.beds ?? []);
    const bedMap = useBedBoard(tenant?.id ?? 0, allInitialBeds);

    const [admitModal, setAdmitModal] = useState<AdmitModalState | null>(null);
    const [dischargeModal, setDischargeModal] = useState<DischargeModalState | null>(null);

    const admitForm = useForm({ bed_id: 0, patient_id: '', encounter_id: '', notes: '' });
    const dischargeForm = useForm({ discharge_reason: '' });

    const getBed = (bed: BedWithAllocation) => bedMap.get(bed.id) ?? bed;

    const summary = {
        available: [...bedMap.values()].filter(b => b.status === 'available').length,
        occupied: [...bedMap.values()].filter(b => b.status === 'occupied').length,
        maintenance: [...bedMap.values()].filter(b => b.status === 'maintenance' || b.status === 'cleaning').length,
        total: bedMap.size,
    };

    return (
        <AppLayout>
            <Head title="Bed Board" />

            <div className="space-y-6">
                {/* Header + summary */}
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Live Bed Board</h1>
                    <div className="flex items-center gap-1.5">
                        <span className="inline-block w-2 h-2 rounded-full bg-green-500" />
                        <span className="text-xs text-gray-600 mr-3">Real-time</span>
                        {Object.entries({ Available: summary.available, Occupied: summary.occupied, Other: summary.maintenance }).map(([k, v]) => (
                            <span key={k} className="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded-full">
                                {k}: <strong>{v}</strong>
                            </span>
                        ))}
                        <span className="text-xs bg-primary-50 text-primary-700 px-2 py-1 rounded-full">
                            Total: <strong>{summary.total}</strong>
                        </span>
                    </div>
                </div>

                {/* Legend */}
                <div className="flex flex-wrap gap-3">
                    {Object.entries(STATUS_DOT).map(([status, cls]) => (
                        <span key={status} className="flex items-center gap-1.5 text-xs text-gray-600 capitalize">
                            <span className={`w-2.5 h-2.5 rounded-full ${cls}`} />
                            {status}
                        </span>
                    ))}
                </div>

                {/* Wards */}
                {wards.map(ward => (
                    <div key={ward.id} className="rounded-xl border border-gray-200 bg-white overflow-hidden">
                        <div className="flex items-center justify-between px-5 py-3 bg-gray-50 border-b border-gray-200">
                            <div>
                                <span className="font-semibold text-gray-900">{ward.name}</span>
                                <span className="ml-2 text-xs text-gray-500">Floor {ward.floor ?? '—'} · {ward.ward_type}</span>
                            </div>
                            <div className="text-xs text-gray-500">
                                {ward.available_beds} available / {ward.total_beds} total
                            </div>
                        </div>

                        <div className="p-4 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2">
                            {(ward.beds ?? []).map(rawBed => {
                                const bed = getBed(rawBed);
                                const colors = STATUS_COLORS[bed.status] ?? STATUS_COLORS.available;
                                const isOccupied = bed.status === 'occupied';
                                return (
                                    <button
                                        key={bed.id}
                                        onClick={() => {
                                            if (isOccupied && bed.current_allocation) {
                                                setDischargeModal({ bed, allocationId: bed.current_allocation.id });
                                            } else if (bed.status === 'available') {
                                                admitForm.setData('bed_id', bed.id);
                                                setAdmitModal({ bed });
                                            } else if (bed.status === 'cleaning' || bed.status === 'maintenance') {
                                                router.post(`/beds/${bed.id}/available`, {}, { preserveScroll: true });
                                            }
                                        }}
                                        className={`relative rounded-lg border-2 p-2 text-left transition-all hover:shadow-sm ${colors}`}
                                    >
                                        <div className="font-mono text-xs font-bold">{bed.bed_number}</div>
                                        {isOccupied && bed.current_allocation?.patient && (
                                            <div className="mt-0.5 text-[10px] leading-tight truncate">
                                                {bed.current_allocation.patient.first_name} {bed.current_allocation.patient.last_name}
                                            </div>
                                        )}
                                        {!isOccupied && (
                                            <div className="mt-0.5 text-[10px] capitalize opacity-75">{bed.status}</div>
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </div>

            {/* Admit Modal */}
            {admitModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
                        <h2 className="text-lg font-semibold mb-4">
                            Admit Patient → Bed {admitModal.bed.bed_number}
                        </h2>
                        <form
                            onSubmit={e => {
                                e.preventDefault();
                                admitForm.post('/bed-allocations', {
                                    onSuccess: () => setAdmitModal(null),
                                    preserveScroll: true,
                                });
                            }}
                            className="space-y-3"
                        >
                            <div>
                                <label className="form-label">Patient MRN or ID</label>
                                <input
                                    type="text"
                                    className="form-input"
                                    placeholder="e.g. MRN-0001-A1B2C3D4 or patient ID"
                                    value={admitForm.data.patient_id}
                                    onChange={e => admitForm.setData('patient_id', e.target.value)}
                                    required
                                />
                                {admitForm.errors.patient_id && (
                                    <p className="form-error">{admitForm.errors.patient_id}</p>
                                )}
                            </div>
                            <div>
                                <label className="form-label">Encounter ID (optional)</label>
                                <input
                                    type="number"
                                    className="form-input"
                                    value={admitForm.data.encounter_id}
                                    onChange={e => admitForm.setData('encounter_id', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="form-label">Notes</label>
                                <textarea
                                    className="form-input"
                                    rows={2}
                                    value={admitForm.data.notes}
                                    onChange={e => admitForm.setData('notes', e.target.value)}
                                />
                            </div>
                            <div className="flex gap-2 pt-2">
                                <button type="submit" disabled={admitForm.processing} className="btn-primary flex-1">
                                    Admit
                                </button>
                                <button type="button" onClick={() => setAdmitModal(null)} className="btn-secondary flex-1">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Discharge Modal */}
            {dischargeModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
                        <h2 className="text-lg font-semibold mb-1">
                            Discharge from Bed {dischargeModal.bed.bed_number}
                        </h2>
                        {dischargeModal.bed.current_allocation?.patient && (
                            <p className="text-sm text-gray-500 mb-4">
                                Patient: {dischargeModal.bed.current_allocation.patient.first_name} {dischargeModal.bed.current_allocation.patient.last_name}
                            </p>
                        )}
                        <form
                            onSubmit={e => {
                                e.preventDefault();
                                dischargeForm.patch(`/bed-allocations/${dischargeModal.allocationId}/discharge`, {
                                    onSuccess: () => setDischargeModal(null),
                                    preserveScroll: true,
                                });
                            }}
                            className="space-y-3"
                        >
                            <div>
                                <label className="form-label">Discharge Reason</label>
                                <input
                                    type="text"
                                    className="form-input"
                                    placeholder="e.g. Recovered, Transferred, LAMA"
                                    value={dischargeForm.data.discharge_reason}
                                    onChange={e => dischargeForm.setData('discharge_reason', e.target.value)}
                                    required
                                />
                                {dischargeForm.errors.discharge_reason && (
                                    <p className="form-error">{dischargeForm.errors.discharge_reason}</p>
                                )}
                            </div>
                            <div className="flex gap-2 pt-2">
                                <button type="submit" disabled={dischargeForm.processing} className="btn-danger flex-1">
                                    Discharge
                                </button>
                                <button type="button" onClick={() => setDischargeModal(null)} className="btn-secondary flex-1">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
