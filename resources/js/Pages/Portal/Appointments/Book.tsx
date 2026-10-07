import { Head, useForm, router } from '@inertiajs/react';
import PortalLayout from '@/Layouts/PortalLayout';
import type { PageProps, Department, User } from '@/types';

interface Props extends PageProps {
    departments: Pick<Department, 'id' | 'name'>[];
    doctors: Pick<User, 'id' | 'name'>[];
}

export default function BookAppointment({ departments, doctors }: Props) {
    const form = useForm({
        department_id: '',
        doctor_id: '',
        scheduled_at: '',
        reason: '',
    });

    const handleDeptChange = (deptId: string) => {
        form.setData('department_id', deptId);
        form.setData('doctor_id', '');
        if (deptId) {
            router.reload({ data: { department_id: deptId }, only: ['doctors'], preserveState: true });
        }
    };

    const minDateTime = () => {
        const d = new Date();
        d.setMinutes(d.getMinutes() + 30);
        return d.toISOString().slice(0, 16);
    };

    return (
        <PortalLayout>
            <Head title="Book Appointment" />

            <div className="max-w-lg space-y-6">
                <div>
                    <h1 className="text-xl font-semibold text-gray-900">Book an Appointment</h1>
                    <p className="text-sm text-gray-500 mt-0.5">Request a new appointment. You'll be notified once it's confirmed.</p>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                    <div>
                        <label className="form-label">Department</label>
                        <select
                            className="form-input"
                            value={form.data.department_id}
                            onChange={e => handleDeptChange(e.target.value)}
                            required
                        >
                            <option value="">Select department…</option>
                            {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
                        </select>
                        {form.errors.department_id && <p className="form-error">{form.errors.department_id}</p>}
                    </div>

                    {form.data.department_id && (
                        <div>
                            <label className="form-label">Doctor</label>
                            <select
                                className="form-input"
                                value={form.data.doctor_id}
                                onChange={e => form.setData('doctor_id', e.target.value)}
                                required
                            >
                                <option value="">Select doctor…</option>
                                {doctors.map(d => <option key={d.id} value={d.id}>Dr. {d.name}</option>)}
                            </select>
                            {form.errors.doctor_id && <p className="form-error">{form.errors.doctor_id}</p>}
                        </div>
                    )}

                    <div>
                        <label className="form-label">Preferred Date & Time</label>
                        <input
                            type="datetime-local"
                            className="form-input"
                            min={minDateTime()}
                            value={form.data.scheduled_at}
                            onChange={e => form.setData('scheduled_at', e.target.value)}
                            required
                        />
                        {form.errors.scheduled_at && <p className="form-error">{form.errors.scheduled_at}</p>}
                    </div>

                    <div>
                        <label className="form-label">Reason for Visit</label>
                        <textarea
                            className="form-input"
                            rows={3}
                            placeholder="Describe your symptoms or reason…"
                            value={form.data.reason}
                            onChange={e => form.setData('reason', e.target.value)}
                        />
                    </div>

                    <div className="flex gap-3 pt-1">
                        <button
                            type="button"
                            disabled={!form.data.department_id || !form.data.doctor_id || !form.data.scheduled_at || form.processing}
                            onClick={() => form.post('/portal/appointments')}
                            className="flex-1 rounded-lg bg-emerald-600 text-white font-medium py-2.5 text-sm hover:bg-emerald-700 disabled:opacity-50 transition-colors"
                        >
                            Request Appointment
                        </button>
                        <a href="/portal/appointments" className="btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </PortalLayout>
    );
}
