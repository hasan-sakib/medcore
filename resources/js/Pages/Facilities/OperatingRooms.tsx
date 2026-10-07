import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Can } from '@/Components/Can';
import { Modal } from '@/Components/Modal';
import type { PageProps } from '@/types';

const BASE = '/admin/facilities/operating-rooms';

const OR_TYPES = ['general', 'cardiac', 'ortho', 'neuro', 'emergency', 'obstetric'] as const;
const OR_STATUSES = ['available', 'scheduled', 'in_use', 'maintenance'] as const;

interface AdminOperatingRoom {
    id: number;
    name: string;
    room_number: string;
    or_type: string;
    status: string;
    is_active: boolean;
    schedules_count: number;
    open_schedules_count: number;
}

interface Props extends PageProps {
    rooms: AdminOperatingRoom[];
}

const STATUS_STYLES: Record<string, string> = {
    available: 'bg-green-100 text-green-800',
    scheduled: 'bg-blue-100 text-blue-800',
    in_use: 'bg-red-100 text-red-800',
    maintenance: 'bg-yellow-100 text-yellow-800',
};

const label = (v: string) => v.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

function RoomForm({ room, onDone }: { room: AdminOperatingRoom | null; onDone: () => void }) {
    const form = useForm({
        name: room?.name ?? '',
        room_number: room?.room_number ?? '',
        or_type: room?.or_type ?? 'general',
        status: room?.status ?? 'available',
        is_active: room?.is_active ?? true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onDone };
        if (room) form.patch(`${BASE}/${room.id}`, opts);
        else form.post(BASE, opts);
    };

    return (
        <form onSubmit={submit} className="space-y-3">
            <div>
                <label className="form-label">Name</label>
                <input className="form-input" value={form.data.name} onChange={e => form.setData('name', e.target.value)} required />
                {form.errors.name && <p className="form-error">{form.errors.name}</p>}
            </div>
            <div className="grid grid-cols-2 gap-3">
                <div>
                    <label className="form-label">Room number</label>
                    <input
                        className="form-input"
                        value={form.data.room_number}
                        onChange={e => form.setData('room_number', e.target.value.toUpperCase())}
                        required
                    />
                    {form.errors.room_number && <p className="form-error">{form.errors.room_number}</p>}
                </div>
                <div>
                    <label className="form-label">Type</label>
                    <select className="form-input" value={form.data.or_type} onChange={e => form.setData('or_type', e.target.value)}>
                        {OR_TYPES.map(t => <option key={t} value={t}>{label(t)}</option>)}
                    </select>
                    {form.errors.or_type && <p className="form-error">{form.errors.or_type}</p>}
                </div>
            </div>
            {room && (
                <>
                    <div>
                        <label className="form-label">Status</label>
                        <select className="form-input" value={form.data.status} onChange={e => form.setData('status', e.target.value)}>
                            {OR_STATUSES.map(s => <option key={s} value={s}>{label(s)}</option>)}
                        </select>
                        {form.errors.status && <p className="form-error">{form.errors.status}</p>}
                    </div>
                    <label className="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            checked={form.data.is_active}
                            onChange={e => form.setData('is_active', e.target.checked)}
                            className="rounded border-gray-300"
                        />
                        Active
                    </label>
                    {form.errors.is_active && <p className="form-error">{form.errors.is_active}</p>}
                </>
            )}
            <div className="flex justify-end gap-2 pt-2">
                <button type="button" onClick={onDone} className="btn-secondary">Cancel</button>
                <button type="submit" disabled={form.processing} className="btn-primary">Save</button>
            </div>
        </form>
    );
}

export default function OperatingRooms({ rooms }: Props) {
    // undefined = closed, null = creating, object = editing
    const [editing, setEditing] = useState<AdminOperatingRoom | null | undefined>(undefined);
    const close = () => setEditing(undefined);

    const remove = (room: AdminOperatingRoom) => {
        if (confirm(`Delete operating room ${room.room_number}? This cannot be undone.`)) {
            router.delete(`${BASE}/${room.id}`, { preserveScroll: true });
        }
    };

    return (
        <AppLayout>
            <Head title="Manage Operating Rooms" />

            <div className="space-y-5">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Manage Operating Rooms</h1>
                    <Can permission="operating-rooms.manage">
                        <button onClick={() => setEditing(null)} className="btn-primary">+ Add Operating Room</button>
                    </Can>
                </div>

                <div className="card overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Room</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th className="px-4 py-3 text-left font-medium text-gray-500">Open procedures</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {rooms.map(room => (
                                <tr key={room.id} className={room.is_active ? '' : 'opacity-60'}>
                                    <td className="px-4 py-3 font-mono text-xs text-gray-700">{room.room_number}</td>
                                    <td className="px-4 py-3 font-medium text-gray-900">
                                        {room.name}
                                        {!room.is_active && <span className="ml-2 text-xs font-normal text-gray-400">Inactive</span>}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">{label(room.or_type)}</td>
                                    <td className="px-4 py-3">
                                        <span className={`rounded px-2 py-0.5 text-xs font-medium ${STATUS_STYLES[room.status] ?? 'bg-gray-100 text-gray-600'}`}>
                                            {label(room.status)}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">{room.open_schedules_count}</td>
                                    <td className="space-x-3 px-4 py-3 text-right">
                                        <Can permission="operating-rooms.manage">
                                            <button onClick={() => setEditing(room)} className="text-xs text-primary-600 hover:underline">Edit</button>
                                            <button onClick={() => remove(room)} className="text-xs text-danger-600 hover:underline">Delete</button>
                                        </Can>
                                    </td>
                                </tr>
                            ))}
                            {rooms.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-gray-500">No operating rooms yet.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {editing !== undefined && (
                <Modal title={editing ? 'Edit Operating Room' : 'Add Operating Room'} onClose={close}>
                    <RoomForm room={editing} onDone={close} />
                </Modal>
            )}
        </AppLayout>
    );
}
