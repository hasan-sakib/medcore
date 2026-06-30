import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, DoctorSchedule, Department } from '@/types';

const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

interface DoctorOption {
    id: number;
    name: string;
}

interface Props extends PageProps {
    schedules: DoctorSchedule[];
    doctors: DoctorOption[];
    departments: Department[];
}

export default function Index({ schedules, doctors, departments }: Props) {
    const [showAdd, setShowAdd] = useState(false);

    const form = useForm({
        user_id: '',
        department_id: '',
        day_of_week: '1',
        start_time: '08:00',
        end_time: '16:00',
        slot_duration: '15',
        max_patients: '20',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/admin/doctor-schedules', { onSuccess: () => { form.reset(); setShowAdd(false); } });
    };

    const destroy = (id: number) => {
        if (confirm('Remove this schedule?')) {
            router.delete(`/admin/doctor-schedules/${id}`);
        }
    };

    const groupedByDoctor = schedules.reduce<Record<number, DoctorSchedule[]>>((acc, s) => {
        (acc[s.user_id] ??= []).push(s);
        return acc;
    }, {});

    return (
        <AppLayout>
            <Head title="Doctor Schedules" />
            <div className="max-w-4xl space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-gray-900">Doctor Schedules</h1>
                    <button
                        onClick={() => setShowAdd(v => !v)}
                        className="rounded bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700"
                    >
                        {showAdd ? 'Cancel' : '+ Add Schedule'}
                    </button>
                </div>

                {showAdd && (
                    <form onSubmit={submit} className="bg-white border border-gray-200 rounded-lg p-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div className="sm:col-span-2">
                            <label className="block text-xs font-medium text-gray-600 mb-1">Doctor</label>
                            <select
                                value={form.data.user_id}
                                onChange={e => form.setData('user_id', e.target.value)}
                                className="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"
                            >
                                <option value="">Select…</option>
                                {doctors.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
                            </select>
                            {form.errors.user_id && <p className="text-xs text-danger-600 mt-0.5">{form.errors.user_id}</p>}
                        </div>

                        <div className="sm:col-span-2">
                            <label className="block text-xs font-medium text-gray-600 mb-1">Department</label>
                            <select
                                value={form.data.department_id}
                                onChange={e => form.setData('department_id', e.target.value)}
                                className="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"
                            >
                                <option value="">None</option>
                                {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Day</label>
                            <select
                                value={form.data.day_of_week}
                                onChange={e => form.setData('day_of_week', e.target.value)}
                                className="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"
                            >
                                {DAYS.map((d, i) => <option key={i} value={i}>{d}</option>)}
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Start</label>
                            <input type="time" value={form.data.start_time}
                                onChange={e => form.setData('start_time', e.target.value)}
                                className="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">End</label>
                            <input type="time" value={form.data.end_time}
                                onChange={e => form.setData('end_time', e.target.value)}
                                className="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Slot (min)</label>
                            <input type="number" min="5" max="60" value={form.data.slot_duration}
                                onChange={e => form.setData('slot_duration', e.target.value)}
                                className="w-full rounded border border-gray-300 px-2 py-1.5 text-sm" />
                        </div>

                        <div className="sm:col-span-4 flex justify-end">
                            <button type="submit" disabled={form.processing}
                                className="rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60">
                                {form.processing ? 'Saving…' : 'Add Schedule'}
                            </button>
                        </div>
                    </form>
                )}

                {Object.entries(groupedByDoctor).map(([doctorId, doctorSchedules]) => {
                    const doctorName = doctorSchedules[0]?.doctor?.name ?? `Doctor #${doctorId}`;
                    return (
                        <div key={doctorId} className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <div className="px-4 py-3 border-b border-gray-100 bg-gray-50">
                                <p className="font-medium text-gray-900">{doctorName}</p>
                            </div>
                            <div className="grid grid-cols-7 min-h-12">
                                {DAYS.map((day, i) => {
                                    const daySchedules = doctorSchedules.filter(s => s.day_of_week === i);
                                    return (
                                        <div key={i} className="border-r border-gray-100 last:border-0 p-2">
                                            <p className="text-xs font-medium text-gray-400 mb-1">{day}</p>
                                            {daySchedules.length === 0 ? (
                                                <p className="text-xs text-gray-200">—</p>
                                            ) : daySchedules.map(s => (
                                                <div key={s.id} className="text-xs space-y-0.5">
                                                    <p className="text-gray-700">{s.start_time.slice(0, 5)}–{s.end_time.slice(0, 5)}</p>
                                                    <p className="text-gray-400">{s.slot_duration}min</p>
                                                    <button onClick={() => destroy(s.id)} className="text-danger-500 hover:underline">×</button>
                                                </div>
                                            ))}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    );
                })}

                {schedules.length === 0 && (
                    <p className="text-sm text-gray-400">No schedules configured</p>
                )}
            </div>
        </AppLayout>
    );
}
