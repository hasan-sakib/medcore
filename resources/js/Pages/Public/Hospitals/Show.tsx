import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

interface Hospital {
    id: number;
    name: string;
    slug: string;
    tagline: string | null;
    description: string | null;
    city: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    website: string | null;
    logo_url: string | null;
    features: string[] | null;
}

interface Department { id: number; name: string; description: string | null; }
interface Doctor { id: number; name: string; specialty: string | null; bio: string | null; avatar_url: string | null; }

interface Props {
    hospital: Hospital;
    departments: Department[];
    doctors: Doctor[];
}

function Avatar({ name, url }: { name: string; url: string | null }) {
    if (url) return <img src={url} alt={name} className="w-14 h-14 rounded-full object-cover" />;
    const initials = name.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase();
    return (
        <div className="w-14 h-14 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold text-lg">
            {initials}
        </div>
    );
}

export default function PublicHospitalShow({ hospital, departments, doctors }: Props) {
    return (
        <PublicLayout>
            <Head title={`${hospital.name} — MedCore`} />

            {/* Hero */}
            <div className="bg-gradient-to-r from-blue-700 to-indigo-700 text-white">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 py-10 flex items-start gap-6">
                    <div className="w-20 h-20 flex-shrink-0 rounded-2xl bg-white/10 flex items-center justify-center text-4xl">
                        {hospital.logo_url ? <img src={hospital.logo_url} alt="" className="w-14 h-14 object-contain" /> : '🏥'}
                    </div>
                    <div>
                        <h1 className="text-2xl md:text-3xl font-extrabold">{hospital.name}</h1>
                        {hospital.city && <p className="text-blue-200 mt-0.5">📍 {hospital.address ?? hospital.city}</p>}
                        {hospital.tagline && <p className="text-blue-100 text-lg mt-1">{hospital.tagline}</p>}
                        <Link
                            href={`/book-appointment?hospital_id=${hospital.id}`}
                            className="mt-4 inline-block bg-emerald-500 text-white font-semibold px-5 py-2 rounded-xl hover:bg-emerald-400 transition-colors"
                        >
                            Book an Appointment
                        </Link>
                    </div>
                </div>
            </div>

            <div className="max-w-5xl mx-auto px-4 sm:px-6 py-10 space-y-10">
                {/* About + contact */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="md:col-span-2 space-y-3">
                        <h2 className="font-bold text-gray-900 text-lg">About</h2>
                        <p className="text-sm text-gray-600 leading-relaxed">
                            {hospital.description ?? 'Information about this hospital is not yet available.'}
                        </p>
                        {(hospital.features ?? []).length > 0 && (
                            <div className="flex flex-wrap gap-2 pt-1">
                                {(hospital.features ?? []).map(f => (
                                    <span key={f} className="bg-blue-50 text-blue-700 text-xs font-medium px-3 py-1 rounded-full">{f}</span>
                                ))}
                            </div>
                        )}
                    </div>
                    <div className="bg-gray-50 rounded-xl p-4 text-sm space-y-2">
                        <h3 className="font-semibold text-gray-900">Contact</h3>
                        {hospital.phone && (
                            <p className="text-gray-600">📞 <a href={`tel:${hospital.phone}`} className="hover:text-blue-600">{hospital.phone}</a></p>
                        )}
                        {hospital.email && (
                            <p className="text-gray-600">✉️ <a href={`mailto:${hospital.email}`} className="hover:text-blue-600">{hospital.email}</a></p>
                        )}
                        {hospital.website && (
                            <p className="text-gray-600">🌐 <a href={hospital.website} target="_blank" rel="noopener" className="hover:text-blue-600">{hospital.website}</a></p>
                        )}
                        {hospital.address && <p className="text-gray-600">📍 {hospital.address}</p>}
                    </div>
                </div>

                {/* Departments */}
                {departments.length > 0 && (
                    <div>
                        <h2 className="font-bold text-gray-900 text-lg mb-4">Departments & Services</h2>
                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            {departments.map(d => (
                                <div key={d.id} className="bg-white rounded-xl border border-gray-200 p-3">
                                    <p className="font-medium text-gray-900 text-sm">{d.name}</p>
                                    {d.description && <p className="text-xs text-gray-400 mt-0.5 line-clamp-2">{d.description}</p>}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Doctors */}
                {doctors.length > 0 && (
                    <div>
                        <h2 className="font-bold text-gray-900 text-lg mb-4">Our Doctors</h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            {doctors.map(doc => (
                                <div key={doc.id} className="bg-white rounded-xl border border-gray-200 p-4 flex gap-3">
                                    <Avatar name={doc.name} url={doc.avatar_url} />
                                    <div className="min-w-0">
                                        <p className="font-semibold text-gray-900 text-sm">Dr. {doc.name}</p>
                                        {doc.specialty && <p className="text-xs text-blue-600 font-medium mt-0.5">{doc.specialty}</p>}
                                        {doc.bio && <p className="text-xs text-gray-500 mt-1 line-clamp-2">{doc.bio}</p>}
                                        <Link
                                            href={`/book-appointment?hospital_id=${hospital.id}&doctor_id=${doc.id}`}
                                            className="mt-2 inline-block text-xs text-emerald-600 font-medium hover:underline"
                                        >
                                            Book with Dr. {doc.name.split(' ')[0]}
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
