import { Head, useForm, router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

interface Hospital { id: number; name: string; slug: string; }
interface Department { id: number; name: string; }
interface Doctor { id: number; name: string; specialty: string | null; }

interface Props {
    hospitals: Hospital[];
    departments: Department[];
    doctors: Doctor[];
    prefill: { hospital_id?: string; department_id?: string; doctor_id?: string };
}

export default function PublicBookAppointment({ hospitals, departments, doctors, prefill }: Props) {
    const form = useForm({
        tenant_id:      prefill.hospital_id ?? '',
        department_id:  prefill.department_id ?? '',
        doctor_id:      prefill.doctor_id ?? '',
        patient_name:   '',
        patient_phone:  '',
        patient_email:  '',
        preferred_date: '',
        message:        '',
    });

    const handleHospitalChange = (val: string) => {
        form.setData(d => ({ ...d, tenant_id: val, department_id: '', doctor_id: '' }));
        if (val) {
            router.reload({ data: { hospital_id: val }, only: ['departments', 'doctors'] });
        }
    };

    const minDate = new Date();
    minDate.setDate(minDate.getDate() + 1);

    return (
        <PublicLayout>
            <Head title="Book Appointment — MedCore" />

            <div className="max-w-2xl mx-auto px-4 sm:px-6 py-12">
                <div className="mb-7">
                    <h1 className="text-2xl font-bold text-gray-900">Request an Appointment</h1>
                    <p className="text-gray-500 text-sm mt-1">Fill out the form below. A coordinator will contact you within 24 hours to confirm.</p>
                </div>

                <div className="bg-white rounded-2xl border border-gray-200 p-6 space-y-5">
                    {/* Hospital */}
                    <div>
                        <label className="form-label text-sm font-medium text-gray-700 block mb-1">Hospital <span className="text-red-500">*</span></label>
                        <select
                            className="form-input w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                            value={form.data.tenant_id}
                            onChange={e => handleHospitalChange(e.target.value)}
                        >
                            <option value="">Select a hospital…</option>
                            {hospitals.map(h => <option key={h.id} value={h.id}>{h.name}</option>)}
                        </select>
                        {form.errors.tenant_id && <p className="text-red-500 text-xs mt-1">{form.errors.tenant_id}</p>}
                    </div>

                    {form.data.tenant_id && (
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="text-sm font-medium text-gray-700 block mb-1">Department</label>
                                <select
                                    className="form-input w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm"
                                    value={form.data.department_id}
                                    onChange={e => form.setData('department_id', e.target.value)}
                                >
                                    <option value="">Any department</option>
                                    {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700 block mb-1">Doctor</label>
                                <select
                                    className="form-input w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm"
                                    value={form.data.doctor_id}
                                    onChange={e => form.setData('doctor_id', e.target.value)}
                                >
                                    <option value="">Any available doctor</option>
                                    {doctors.map(d => <option key={d.id} value={d.id}>Dr. {d.name}{d.specialty ? ` — ${d.specialty}` : ''}</option>)}
                                </select>
                            </div>
                        </div>
                    )}

                    <hr className="border-gray-100" />

                    {/* Patient info */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="col-span-2">
                            <label className="text-sm font-medium text-gray-700 block mb-1">Your Full Name <span className="text-red-500">*</span></label>
                            <input
                                type="text"
                                className="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="John Doe"
                                value={form.data.patient_name}
                                onChange={e => form.setData('patient_name', e.target.value)}
                            />
                            {form.errors.patient_name && <p className="text-red-500 text-xs mt-1">{form.errors.patient_name}</p>}
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700 block mb-1">Phone Number <span className="text-red-500">*</span></label>
                            <input
                                type="tel"
                                className="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="+1 (555) 000-0000"
                                value={form.data.patient_phone}
                                onChange={e => form.setData('patient_phone', e.target.value)}
                            />
                            {form.errors.patient_phone && <p className="text-red-500 text-xs mt-1">{form.errors.patient_phone}</p>}
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700 block mb-1">Email Address</label>
                            <input
                                type="email"
                                className="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="you@example.com"
                                value={form.data.patient_email}
                                onChange={e => form.setData('patient_email', e.target.value)}
                            />
                        </div>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-700 block mb-1">Preferred Date</label>
                        <input
                            type="date"
                            className="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                            min={minDate.toISOString().split('T')[0]}
                            value={form.data.preferred_date}
                            onChange={e => form.setData('preferred_date', e.target.value)}
                        />
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-700 block mb-1">Message / Reason for Visit</label>
                        <textarea
                            className="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                            rows={3}
                            placeholder="Describe your symptoms or reason for the visit…"
                            value={form.data.message}
                            onChange={e => form.setData('message', e.target.value)}
                        />
                    </div>

                    <button
                        type="button"
                        disabled={!form.data.tenant_id || !form.data.patient_name || !form.data.patient_phone || form.processing}
                        onClick={() => form.post('/book-appointment')}
                        className="w-full bg-emerald-600 text-white font-semibold py-3 rounded-xl hover:bg-emerald-700 disabled:opacity-50 transition-colors text-sm"
                    >
                        {form.processing ? 'Submitting…' : 'Submit Request'}
                    </button>

                    <p className="text-xs text-gray-400 text-center">
                        By submitting this form you agree to be contacted by the hospital regarding your appointment request.
                    </p>
                </div>
            </div>
        </PublicLayout>
    );
}
