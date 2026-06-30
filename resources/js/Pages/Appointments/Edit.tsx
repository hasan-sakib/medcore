import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, Appointment } from '@/types';

interface Props extends PageProps {
    appointment: Appointment;
}

const STATUSES: Appointment['status'][] = ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'];

export default function Edit({ appointment: a }: Props) {
    const form = useForm({
        status: a.status,
        notes: a.notes ?? '',
        cancellation_reason: a.cancellation_reason ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.patch(`/appointments/${a.id}`);
    };

    const showCancellation = form.data.status === 'cancelled';

    return (
        <AppLayout>
            <Head title="Edit Appointment" />
            <div className="max-w-lg">
                <h1 className="text-xl font-semibold text-gray-900 mb-6">Update Appointment</h1>

                <form onSubmit={submit} className="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            value={form.data.status}
                            onChange={e => form.setData('status', e.target.value as Appointment['status'])}
                            className="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                        >
                            {STATUSES.map(s => (
                                <option key={s} value={s}>{s.replace(/_/g, ' ')}</option>
                            ))}
                        </select>
                    </div>

                    {showCancellation && (
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Cancellation Reason</label>
                            <textarea
                                value={form.data.cancellation_reason}
                                onChange={e => form.setData('cancellation_reason', e.target.value)}
                                rows={2}
                                className="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                            />
                        </div>
                    )}

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea
                            value={form.data.notes}
                            onChange={e => form.setData('notes', e.target.value)}
                            rows={3}
                            className="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                        />
                    </div>

                    <div className="flex gap-3 pt-2">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-60"
                        >
                            {form.processing ? 'Saving…' : 'Save Changes'}
                        </button>
                        <a href={`/appointments/${a.id}`} className="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
