import { Head, useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Can } from '@/Components/Can';
import type { PageProps, OperatingRoom, OrSchedule, User } from '@/types';

interface Props extends PageProps {
    rooms: (OperatingRoom & { schedules: OrSchedule[] })[];
    surgeons: Pick<User, 'id' | 'name'>[];
    date: string;
}

const STATUS_COLORS: Record<string, string> = {
    available:  'bg-green-100 text-green-800',
    scheduled:  'bg-blue-100 text-blue-800',
    in_use:     'bg-red-100 text-red-800',
    maintenance:'bg-yellow-100 text-yellow-800',
};

const SCHED_STATUS_COLORS: Record<string, string> = {
    scheduled:   'border-blue-400 bg-blue-50',
    in_progress: 'border-red-400 bg-red-50',
    completed:   'border-green-400 bg-green-50',
    cancelled:   'border-gray-300 bg-gray-50 opacity-60',
};

export default function ORIndex({ rooms, surgeons, date, tenant }: Props) {
    const [showForm, setShowForm] = useState(false);
    const [liveSchedules, setLiveSchedules] = useState(rooms);

    const form = useForm({
        operating_room_id: '',
        surgeon_id: '',
        encounter_id: '',
        procedure_name: '',
        scheduled_start: date + 'T08:00',
        scheduled_end: date + 'T09:00',
        notes: '',
    });

    useEffect(() => {
        if (!window.Echo || !tenant?.id) return;

        const channel = window.Echo.private(`tenant.${tenant.id}.or`);

        channel.listen('.or.schedule.updated', (payload: OrSchedule) => {
            setLiveSchedules(prev =>
                prev.map(room => {
                    if (room.id !== payload.operating_room_id) return room;
                    const existing = room.schedules.find(s => s.id === payload.id);
                    const updated = existing
                        ? room.schedules.map(s => (s.id === payload.id ? { ...s, ...payload } : s))
                        : [...room.schedules, payload];
                    return { ...room, schedules: updated };
                })
            );
        });

        return () => { window.Echo.leave(`tenant.${tenant.id}.or`); };
    }, [tenant?.id]);

    return (
        <AppLayout>
            <Head title="Operating Rooms" />

            <div className="space-y-5">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Operating Room Schedule</h1>
                    <div className="flex items-center gap-3">
                        <input
                            type="date"
                            value={date}
                            onChange={e => { window.location.href = `/operating-rooms?date=${e.target.value}`; }}
                            className="text-sm rounded border border-gray-300 px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-primary-500"
                        />
                        <Can permission="or-schedules.manage">
                            <button onClick={() => setShowForm(v => !v)} className="btn-primary text-sm">
                                + Schedule Procedure
                            </button>
                        </Can>
                    </div>
                </div>

                {/* Schedule form */}
                {showForm && (
                    <div className="rounded-xl border border-gray-200 bg-white p-5">
                        <h2 className="font-semibold text-gray-900 mb-4">New Procedure</h2>
                        <form
                            onSubmit={e => {
                                e.preventDefault();
                                form.post('/operating-rooms/schedules', {
                                    onSuccess: () => { form.reset(); setShowForm(false); },
                                    preserveScroll: true,
                                });
                            }}
                            className="grid grid-cols-1 sm:grid-cols-2 gap-4"
                        >
                            <div>
                                <label className="form-label">Operating Room</label>
                                <select
                                    className="form-input"
                                    value={form.data.operating_room_id}
                                    onChange={e => form.setData('operating_room_id', e.target.value)}
                                    required
                                >
                                    <option value="">Select room…</option>
                                    {rooms.map(r => (
                                        <option key={r.id} value={r.id}>{r.name} ({r.room_number})</option>
                                    ))}
                                </select>
                                {form.errors.operating_room_id && <p className="form-error">{form.errors.operating_room_id}</p>}
                            </div>
                            <div>
                                <label className="form-label">Surgeon</label>
                                <select
                                    className="form-input"
                                    value={form.data.surgeon_id}
                                    onChange={e => form.setData('surgeon_id', e.target.value)}
                                    required
                                >
                                    <option value="">Select surgeon…</option>
                                    {surgeons.map(s => (
                                        <option key={s.id} value={s.id}>{s.name}</option>
                                    ))}
                                </select>
                                {form.errors.surgeon_id && <p className="form-error">{form.errors.surgeon_id}</p>}
                            </div>
                            <div className="sm:col-span-2">
                                <label className="form-label">Procedure Name</label>
                                <input
                                    type="text"
                                    className="form-input"
                                    value={form.data.procedure_name}
                                    onChange={e => form.setData('procedure_name', e.target.value)}
                                    required
                                />
                                {form.errors.procedure_name && <p className="form-error">{form.errors.procedure_name}</p>}
                            </div>
                            <div>
                                <label className="form-label">Start</label>
                                <input
                                    type="datetime-local"
                                    className="form-input"
                                    value={form.data.scheduled_start}
                                    onChange={e => form.setData('scheduled_start', e.target.value)}
                                    required
                                />
                            </div>
                            <div>
                                <label className="form-label">End</label>
                                <input
                                    type="datetime-local"
                                    className="form-input"
                                    value={form.data.scheduled_end}
                                    onChange={e => form.setData('scheduled_end', e.target.value)}
                                    required
                                />
                            </div>
                            <div className="sm:col-span-2 flex gap-2">
                                <button type="submit" disabled={form.processing} className="btn-primary">
                                    Schedule
                                </button>
                                <button type="button" onClick={() => setShowForm(false)} className="btn-secondary">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                )}

                {/* Room schedule cards */}
                <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                    {liveSchedules.map(room => (
                        <div key={room.id} className="rounded-xl border border-gray-200 bg-white overflow-hidden">
                            <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                <div>
                                    <p className="font-semibold text-sm text-gray-900">{room.name}</p>
                                    <p className="text-xs text-gray-500">{room.room_number} · {room.or_type}</p>
                                </div>
                                <span className={`text-xs font-medium px-2 py-0.5 rounded-full capitalize ${STATUS_COLORS[room.status] ?? ''}`}>
                                    {room.status.replace('_', ' ')}
                                </span>
                            </div>
                            <div className="p-3 space-y-2 min-h-[80px]">
                                {room.schedules.length === 0 && (
                                    <p className="text-xs text-gray-400 text-center py-4">No procedures scheduled</p>
                                )}
                                {room.schedules.map(sched => (
                                    <div
                                        key={sched.id}
                                        className={`rounded-lg border-l-4 px-3 py-2 ${SCHED_STATUS_COLORS[sched.status] ?? ''}`}
                                    >
                                        <div className="flex items-start justify-between gap-2">
                                            <p className="text-sm font-medium text-gray-900 leading-snug">{sched.procedure_name}</p>
                                            <span className="text-[10px] capitalize whitespace-nowrap text-gray-500 mt-0.5">
                                                {sched.status.replace('_', ' ')}
                                            </span>
                                        </div>
                                        <p className="text-xs text-gray-500 mt-0.5">
                                            {new Date(sched.scheduled_start).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                            {' – '}
                                            {new Date(sched.scheduled_end).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                        </p>
                                        {sched.surgeon && (
                                            <p className="text-xs text-gray-500">Dr. {sched.surgeon.name}</p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
