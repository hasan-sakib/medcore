import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';

interface Doctor {
    id: number;
    name: string;
    specialty: string | null;
    bio: string | null;
    avatar_url: string | null;
    tenant_id: number;
    tenant?: { id: number; name: string; slug: string; city: string | null };
}

interface Hospital { id: number; name: string; }

interface Props {
    doctors: { data: Doctor[]; current_page: number; last_page: number; total: number };
    hospitals: Hospital[];
    specialties: string[];
    filters: { search?: string; hospital_id?: string; specialty?: string };
}

function Avatar({ name, url }: { name: string; url: string | null }) {
    if (url) return <img src={url} alt={name} className="w-14 h-14 rounded-full object-cover" />;
    const initials = name.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase();
    return (
        <div className="w-14 h-14 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-semibold text-base">
            {initials}
        </div>
    );
}

export default function PublicDoctorsIndex({ doctors, hospitals, specialties, filters }: Props) {
    const [form, setForm] = useState({
        search: filters.search ?? '',
        hospital_id: filters.hospital_id ?? '',
        specialty: filters.specialty ?? '',
    });

    const applyFilters = () => {
        const params: Record<string, string> = {};
        if (form.search) params.search = form.search;
        if (form.hospital_id) params.hospital_id = form.hospital_id;
        if (form.specialty) params.specialty = form.specialty;
        router.get('/doctors', params, { preserveState: true });
    };

    const clearFilters = () => {
        setForm({ search: '', hospital_id: '', specialty: '' });
        router.get('/doctors');
    };

    const hasFilters = Object.values(filters).some(Boolean);

    return (
        <PublicLayout>
            <Head title="Find a Doctor — MedCore" />

            {/* Header */}
            <div className="bg-gradient-to-r from-indigo-700 to-purple-700 text-white">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 py-10">
                    <h1 className="text-2xl font-bold mb-4">Find a Doctor</h1>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <input
                            type="text"
                            placeholder="Name or specialty…"
                            value={form.search}
                            onChange={e => setForm(f => ({ ...f, search: e.target.value }))}
                            onKeyDown={e => e.key === 'Enter' && applyFilters()}
                            className="rounded-xl px-4 py-2.5 text-gray-900 text-sm outline-none"
                        />
                        <select
                            value={form.hospital_id}
                            onChange={e => setForm(f => ({ ...f, hospital_id: e.target.value }))}
                            className="rounded-xl px-4 py-2.5 text-gray-900 text-sm outline-none"
                        >
                            <option value="">All Hospitals</option>
                            {hospitals.map(h => <option key={h.id} value={h.id}>{h.name}</option>)}
                        </select>
                        <select
                            value={form.specialty}
                            onChange={e => setForm(f => ({ ...f, specialty: e.target.value }))}
                            className="rounded-xl px-4 py-2.5 text-gray-900 text-sm outline-none"
                        >
                            <option value="">All Specialties</option>
                            {specialties.map(s => <option key={s} value={s}>{s}</option>)}
                        </select>
                    </div>
                    <div className="mt-3 flex gap-2">
                        <button onClick={applyFilters} className="bg-white text-indigo-700 font-semibold px-5 py-2 rounded-xl text-sm hover:bg-indigo-50">
                            Search
                        </button>
                        {hasFilters && (
                            <button onClick={clearFilters} className="text-indigo-200 underline text-sm">Clear filters</button>
                        )}
                    </div>
                </div>
            </div>

            <div className="max-w-5xl mx-auto px-4 sm:px-6 py-10">
                <p className="text-sm text-gray-500 mb-5">{doctors.total} doctor{doctors.total !== 1 ? 's' : ''} found</p>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {doctors.data.map(doc => (
                        <div key={doc.id} className="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
                            <div className="flex items-start gap-3">
                                <Avatar name={doc.name} url={doc.avatar_url} />
                                <div className="min-w-0">
                                    <p className="font-semibold text-gray-900">Dr. {doc.name}</p>
                                    {doc.specialty && <p className="text-xs text-indigo-600 font-medium mt-0.5">{doc.specialty}</p>}
                                    {doc.tenant && (
                                        <Link href={`/hospitals/${doc.tenant.slug}`} className="text-xs text-gray-400 hover:text-blue-600 mt-0.5 block">
                                            🏥 {doc.tenant.name}{doc.tenant.city ? `, ${doc.tenant.city}` : ''}
                                        </Link>
                                    )}
                                </div>
                            </div>
                            {doc.bio && <p className="text-xs text-gray-500 leading-relaxed line-clamp-3">{doc.bio}</p>}
                            <Link
                                href={`/book-appointment?doctor_id=${doc.id}&hospital_id=${doc.tenant_id}`}
                                className="block w-full text-center bg-emerald-600 text-white text-xs font-semibold py-2 rounded-lg hover:bg-emerald-700 transition-colors"
                            >
                                Book Appointment
                            </Link>
                        </div>
                    ))}
                </div>

                {doctors.data.length === 0 && (
                    <div className="text-center py-16">
                        <p className="text-4xl mb-3">👨‍⚕️</p>
                        <p className="text-gray-600 font-medium">No doctors found</p>
                        <p className="text-sm text-gray-400 mt-1">Try adjusting your filters</p>
                    </div>
                )}

                {/* Pagination */}
                {doctors.last_page > 1 && (
                    <div className="mt-8 flex justify-center gap-2">
                        {Array.from({ length: doctors.last_page }, (_, i) => i + 1).map(p => (
                            <button
                                key={p}
                                onClick={() => router.get('/doctors', { ...filters, page: String(p) }, { preserveState: true })}
                                className={`w-9 h-9 text-sm rounded-lg ${p === doctors.current_page ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'}`}
                            >
                                {p}
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
