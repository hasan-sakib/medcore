import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Can } from '@/Components/Can';
import { Modal } from '@/Components/Modal';
import type { PageProps } from '@/types';

const BASE = '/admin/facilities';

const WARD_TYPES = ['general', 'icu', 'maternity', 'pediatric', 'surgical', 'oncology', 'psychiatric'] as const;
const ROOM_TYPES = ['general', 'private', 'semi_private', 'icu', 'isolation'] as const;
const BED_TYPES = ['standard', 'icu', 'isolation', 'pediatric', 'bariatric', 'electric'] as const;
const BED_STATUSES = ['available', 'maintenance', 'reserved', 'cleaning'] as const;

interface FacilityRoom {
    id: number;
    ward_id: number;
    room_number: string;
    room_type: string;
    is_active: boolean;
    beds_count: number;
}

interface FacilityBed {
    id: number;
    ward_id: number;
    room_id: number | null;
    bed_number: string;
    bed_type: string;
    status: string;
    is_active: boolean;
    allocations_count: number;
}

interface FacilityWard {
    id: number;
    name: string;
    code: string;
    floor: string | null;
    ward_type: string;
    is_active: boolean;
    rooms: FacilityRoom[];
    beds: FacilityBed[];
}

interface Props extends PageProps {
    wards: FacilityWard[];
}

type ModalState =
    | { kind: 'ward'; ward: FacilityWard | null }
    | { kind: 'room'; wardId: number; room: FacilityRoom | null }
    | { kind: 'bed'; wardId: number; roomId: number | null; bed: FacilityBed | null };

const STATUS_STYLES: Record<string, string> = {
    available: 'bg-green-100 text-green-800',
    occupied: 'bg-red-100 text-red-800',
    maintenance: 'bg-yellow-100 text-yellow-800',
    reserved: 'bg-blue-100 text-blue-800',
    cleaning: 'bg-orange-100 text-orange-800',
};

const label = (v: string) => v.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

function Field({ name, error, children }: { name: string; error?: string; children: React.ReactNode }) {
    return (
        <div>
            <label className="form-label">{name}</label>
            {children}
            {error && <p className="form-error">{error}</p>}
        </div>
    );
}

function FormActions({ processing, onCancel }: { processing: boolean; onCancel: () => void }) {
    return (
        <div className="flex justify-end gap-2 pt-2">
            <button type="button" onClick={onCancel} className="btn-secondary">Cancel</button>
            <button type="submit" disabled={processing} className="btn-primary">Save</button>
        </div>
    );
}

function ActiveCheckbox({ checked, onChange }: { checked: boolean; onChange: (v: boolean) => void }) {
    return (
        <label className="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" checked={checked} onChange={e => onChange(e.target.checked)} className="rounded border-gray-300" />
            Active
        </label>
    );
}

function WardForm({ ward, onDone }: { ward: FacilityWard | null; onDone: () => void }) {
    const form = useForm({
        name: ward?.name ?? '',
        code: ward?.code ?? '',
        floor: ward?.floor ?? '',
        ward_type: ward?.ward_type ?? 'general',
        is_active: ward?.is_active ?? true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onDone };
        if (ward) form.patch(`${BASE}/wards/${ward.id}`, opts);
        else form.post(`${BASE}/wards`, opts);
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <Field name="Name" error={form.errors.name}>
                <input className="form-input" value={form.data.name} onChange={e => form.setData('name', e.target.value)} required />
            </Field>
            <div className="grid grid-cols-2 gap-3">
                <Field name="Code" error={form.errors.code}>
                    <input
                        className="form-input"
                        value={form.data.code}
                        onChange={e => form.setData('code', e.target.value.toUpperCase())}
                        required
                    />
                </Field>
                <Field name="Floor" error={form.errors.floor}>
                    <input className="form-input" value={form.data.floor} onChange={e => form.setData('floor', e.target.value)} />
                </Field>
            </div>
            <Field name="Type" error={form.errors.ward_type}>
                <select className="form-input" value={form.data.ward_type} onChange={e => form.setData('ward_type', e.target.value)}>
                    {WARD_TYPES.map(t => <option key={t} value={t}>{label(t)}</option>)}
                </select>
            </Field>
            {ward && <ActiveCheckbox checked={form.data.is_active} onChange={v => form.setData('is_active', v)} />}
            {form.errors.is_active && <p className="form-error">{form.errors.is_active}</p>}
            <FormActions processing={form.processing} onCancel={onDone} />
        </form>
    );
}

function RoomForm({ wardId, room, onDone }: { wardId: number; room: FacilityRoom | null; onDone: () => void }) {
    const form = useForm({
        ward_id: wardId,
        room_number: room?.room_number ?? '',
        room_type: room?.room_type ?? 'general',
        is_active: room?.is_active ?? true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onDone };
        if (room) form.patch(`${BASE}/rooms/${room.id}`, opts);
        else form.post(`${BASE}/rooms`, opts);
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <Field name="Room number" error={form.errors.room_number}>
                <input
                    className="form-input"
                    value={form.data.room_number}
                    onChange={e => form.setData('room_number', e.target.value.toUpperCase())}
                    required
                />
            </Field>
            <Field name="Type" error={form.errors.room_type}>
                <select className="form-input" value={form.data.room_type} onChange={e => form.setData('room_type', e.target.value)}>
                    {ROOM_TYPES.map(t => <option key={t} value={t}>{label(t)}</option>)}
                </select>
            </Field>
            {form.errors.ward_id && <p className="form-error">{form.errors.ward_id}</p>}
            {room && <ActiveCheckbox checked={form.data.is_active} onChange={v => form.setData('is_active', v)} />}
            {form.errors.is_active && <p className="form-error">{form.errors.is_active}</p>}
            <FormActions processing={form.processing} onCancel={onDone} />
        </form>
    );
}

function BedForm({
    ward,
    roomId,
    bed,
    onDone,
}: {
    ward: FacilityWard;
    roomId: number | null;
    bed: FacilityBed | null;
    onDone: () => void;
}) {
    const occupied = bed?.status === 'occupied';
    const form = useForm({
        ward_id: ward.id,
        room_id: bed ? bed.room_id : roomId,
        bed_number: bed?.bed_number ?? '',
        bed_type: bed?.bed_type ?? 'standard',
        status: bed?.status ?? 'available',
        is_active: bed?.is_active ?? true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onDone };
        if (bed) form.patch(`${BASE}/beds/${bed.id}`, opts);
        else form.post(`${BASE}/beds`, opts);
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <Field name="Bed number" error={form.errors.bed_number}>
                <input
                    className="form-input"
                    value={form.data.bed_number}
                    onChange={e => form.setData('bed_number', e.target.value.toUpperCase())}
                    required
                />
            </Field>
            <Field name="Room" error={form.errors.room_id}>
                <select
                    className="form-input"
                    value={form.data.room_id ?? ''}
                    disabled={occupied}
                    onChange={e => form.setData('room_id', e.target.value ? Number(e.target.value) : null)}
                >
                    <option value="">No room</option>
                    {ward.rooms.map(r => <option key={r.id} value={r.id}>{r.room_number}</option>)}
                </select>
            </Field>
            <div className="grid grid-cols-2 gap-3">
                <Field name="Type" error={form.errors.bed_type}>
                    <select className="form-input" value={form.data.bed_type} onChange={e => form.setData('bed_type', e.target.value)}>
                        {BED_TYPES.map(t => <option key={t} value={t}>{label(t)}</option>)}
                    </select>
                </Field>
                <Field name="Status" error={form.errors.status}>
                    {occupied ? (
                        <input className="form-input bg-gray-50" value="Occupied" disabled readOnly />
                    ) : (
                        <select className="form-input" value={form.data.status} onChange={e => form.setData('status', e.target.value)}>
                            {BED_STATUSES.map(s => <option key={s} value={s}>{label(s)}</option>)}
                        </select>
                    )}
                </Field>
            </div>
            {bed && <ActiveCheckbox checked={form.data.is_active} onChange={v => form.setData('is_active', v)} />}
            {form.errors.is_active && <p className="form-error">{form.errors.is_active}</p>}
            <FormActions processing={form.processing} onCancel={onDone} />
        </form>
    );
}

function BedChip({ bed, onEdit, onDelete }: { bed: FacilityBed; onEdit: () => void; onDelete: () => void }) {
    return (
        <div className={`flex items-center gap-2 rounded-lg border border-gray-200 px-2.5 py-1.5 text-sm ${bed.is_active ? '' : 'opacity-60'}`}>
            <span className="font-mono text-xs text-gray-900">{bed.bed_number}</span>
            <span className="text-xs text-gray-500">{label(bed.bed_type)}</span>
            <span className={`rounded px-1.5 py-0.5 text-xs font-medium ${STATUS_STYLES[bed.status] ?? 'bg-gray-100 text-gray-600'}`}>
                {label(bed.status)}
            </span>
            {!bed.is_active && <span className="text-xs text-gray-400">Inactive</span>}
            <Can permission="wards.manage">
                <button onClick={onEdit} className="text-xs text-primary-600 hover:underline">Edit</button>
                <button onClick={onDelete} className="text-xs text-danger-600 hover:underline">Delete</button>
            </Can>
        </div>
    );
}

export default function Index({ wards }: Props) {
    const [modal, setModal] = useState<ModalState | null>(null);
    const close = () => setModal(null);

    const remove = (url: string, what: string) => {
        if (confirm(`Delete ${what}? This cannot be undone.`)) {
            router.delete(url, { preserveScroll: true });
        }
    };

    const modalWard = modal && modal.kind !== 'ward' ? wards.find(w => w.id === modal.wardId) : undefined;

    return (
        <AppLayout>
            <Head title="Facilities" />

            <div className="space-y-5">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Wards, Rooms &amp; Beds</h1>
                    <Can permission="wards.manage">
                        <button onClick={() => setModal({ kind: 'ward', ward: null })} className="btn-primary">+ Add Ward</button>
                    </Can>
                </div>

                {wards.length === 0 && (
                    <div className="card p-8 text-center text-sm text-gray-500">No wards yet. Add a ward to get started.</div>
                )}

                {wards.map(ward => {
                    const unassigned = ward.beds.filter(b => b.room_id === null);
                    return (
                        <section key={ward.id} className={`card ${ward.is_active ? '' : 'opacity-70'}`}>
                            <header className="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-3">
                                <div>
                                    <h2 className="font-semibold text-gray-900">
                                        {ward.name} <span className="font-mono text-xs text-gray-500">{ward.code}</span>
                                    </h2>
                                    <p className="text-xs text-gray-500">
                                        {label(ward.ward_type)}
                                        {ward.floor ? ` · Floor ${ward.floor}` : ''} · {ward.rooms.length} rooms · {ward.beds.length} beds
                                        {!ward.is_active && ' · Inactive'}
                                    </p>
                                </div>
                                <Can permission="wards.manage">
                                    <div className="flex flex-wrap gap-3 text-sm">
                                        <button onClick={() => setModal({ kind: 'room', wardId: ward.id, room: null })} className="text-primary-600 hover:underline">
                                            + Room
                                        </button>
                                        <button onClick={() => setModal({ kind: 'bed', wardId: ward.id, roomId: null, bed: null })} className="text-primary-600 hover:underline">
                                            + Bed
                                        </button>
                                        <button onClick={() => setModal({ kind: 'ward', ward })} className="text-gray-600 hover:underline">Edit</button>
                                        <button onClick={() => remove(`${BASE}/wards/${ward.id}`, `ward ${ward.name}`)} className="text-danger-600 hover:underline">
                                            Delete
                                        </button>
                                    </div>
                                </Can>
                            </header>

                            <div className="space-y-4 p-5">
                                {ward.rooms.map(room => (
                                    <div key={room.id} className={room.is_active ? '' : 'opacity-60'}>
                                        <div className="mb-2 flex items-center gap-3 text-sm">
                                            <span className="font-medium text-gray-800">Room {room.room_number}</span>
                                            <span className="text-xs text-gray-500">{label(room.room_type)}</span>
                                            {!room.is_active && <span className="text-xs text-gray-400">Inactive</span>}
                                            <Can permission="wards.manage">
                                                <button onClick={() => setModal({ kind: 'bed', wardId: ward.id, roomId: room.id, bed: null })} className="text-xs text-primary-600 hover:underline">
                                                    + Bed
                                                </button>
                                                <button onClick={() => setModal({ kind: 'room', wardId: ward.id, room })} className="text-xs text-gray-600 hover:underline">Edit</button>
                                                <button onClick={() => remove(`${BASE}/rooms/${room.id}`, `room ${room.room_number}`)} className="text-xs text-danger-600 hover:underline">
                                                    Delete
                                                </button>
                                            </Can>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            {ward.beds.filter(b => b.room_id === room.id).map(bed => (
                                                <BedChip
                                                    key={bed.id}
                                                    bed={bed}
                                                    onEdit={() => setModal({ kind: 'bed', wardId: ward.id, roomId: room.id, bed })}
                                                    onDelete={() => remove(`${BASE}/beds/${bed.id}`, `bed ${bed.bed_number}`)}
                                                />
                                            ))}
                                            {room.beds_count === 0 && <span className="text-xs text-gray-400">No beds</span>}
                                        </div>
                                    </div>
                                ))}

                                {unassigned.length > 0 && (
                                    <div>
                                        <p className="mb-2 text-sm font-medium text-gray-800">Beds without a room</p>
                                        <div className="flex flex-wrap gap-2">
                                            {unassigned.map(bed => (
                                                <BedChip
                                                    key={bed.id}
                                                    bed={bed}
                                                    onEdit={() => setModal({ kind: 'bed', wardId: ward.id, roomId: null, bed })}
                                                    onDelete={() => remove(`${BASE}/beds/${bed.id}`, `bed ${bed.bed_number}`)}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {ward.rooms.length === 0 && unassigned.length === 0 && (
                                    <p className="text-sm text-gray-400">No rooms or beds in this ward yet.</p>
                                )}
                            </div>
                        </section>
                    );
                })}
            </div>

            {modal?.kind === 'ward' && (
                <Modal title={modal.ward ? 'Edit Ward' : 'Add Ward'} onClose={close}>
                    <WardForm ward={modal.ward} onDone={close} />
                </Modal>
            )}
            {modal?.kind === 'room' && (
                <Modal title={modal.room ? 'Edit Room' : 'Add Room'} onClose={close}>
                    <RoomForm wardId={modal.wardId} room={modal.room} onDone={close} />
                </Modal>
            )}
            {modal?.kind === 'bed' && modalWard && (
                <Modal title={modal.bed ? 'Edit Bed' : 'Add Bed'} onClose={close}>
                    <BedForm ward={modalWard} roomId={modal.roomId} bed={modal.bed} onDone={close} />
                </Modal>
            )}
        </AppLayout>
    );
}
