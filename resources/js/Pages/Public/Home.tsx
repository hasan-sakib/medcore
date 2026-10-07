import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

interface Hospital {
    id: number;
    name: string;
    slug: string;
    tagline: string | null;
    city: string | null;
    logo_url: string | null;
    features: string[] | null;
}

interface Props {
    hospitals: Hospital[];
}

const FEATURE_ICON: Record<string, string> = {
    ICU: '🏥', Emergency: '🚑', Pharmacy: '💊', Laboratory: '🔬',
    Surgery: '⚕️', Pediatrics: '👶', Cardiology: '❤️', Radiology: '📷',
};

export default function PublicHome({ hospitals }: Props) {
    return (
        <PublicLayout>
            <Head title="MedCore — Healthcare Network" />

            {/* Hero */}
            <section className="bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 text-white">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 py-20 md:py-28 text-center">
                    <h1 className="text-3xl md:text-5xl font-extrabold leading-tight mb-4">
                        Find the right care,<br className="hidden md:block" /> right when you need it.
                    </h1>
                    <p className="text-blue-100 text-lg md:text-xl max-w-2xl mx-auto mb-8">
                        Browse hospitals across the network, explore their specialties, and book an appointment — all in one place.
                    </p>
                    <div className="flex flex-col sm:flex-row gap-3 justify-center">
                        <Link href="/hospitals" className="bg-white text-blue-700 font-semibold px-6 py-3 rounded-xl hover:bg-blue-50 transition-colors">
                            Browse Hospitals
                        </Link>
                        <Link href="/doctors" className="bg-blue-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-blue-400 transition-colors border border-blue-400">
                            Find a Doctor
                        </Link>
                        <Link href="/book-appointment" className="bg-emerald-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-emerald-400 transition-colors">
                            Book Appointment
                        </Link>
                    </div>
                </div>
            </section>

            {/* Stats strip */}
            <section className="bg-white border-b border-gray-100">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 py-8 grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p className="text-3xl font-bold text-blue-600">{hospitals.length}</p>
                        <p className="text-sm text-gray-500 mt-0.5">Partner Hospitals</p>
                    </div>
                    <div>
                        <p className="text-3xl font-bold text-blue-600">24/7</p>
                        <p className="text-sm text-gray-500 mt-0.5">Emergency Services</p>
                    </div>
                    <div>
                        <p className="text-3xl font-bold text-blue-600">100%</p>
                        <p className="text-sm text-gray-500 mt-0.5">Digital Records</p>
                    </div>
                </div>
            </section>

            {/* Hospital cards */}
            <section className="max-w-6xl mx-auto px-4 sm:px-6 py-12">
                <div className="flex items-center justify-between mb-7">
                    <h2 className="text-xl font-bold text-gray-900">Hospitals in Our Network</h2>
                    <Link href="/hospitals" className="text-sm text-blue-600 hover:underline font-medium">View all →</Link>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    {hospitals.map(h => (
                        <Link
                            key={h.id}
                            href={`/hospitals/${h.slug}`}
                            className="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
                        >
                            <div className="h-32 bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
                                {h.logo_url
                                    ? <img src={h.logo_url} alt={h.name} className="h-16 object-contain" />
                                    : <span className="text-4xl">🏥</span>
                                }
                            </div>
                            <div className="p-4">
                                <h3 className="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">{h.name}</h3>
                                {h.city && <p className="text-xs text-gray-400 mt-0.5">📍 {h.city}</p>}
                                {h.tagline && <p className="text-sm text-gray-500 mt-1 line-clamp-2">{h.tagline}</p>}
                                {(h.features ?? []).length > 0 && (
                                    <div className="mt-2 flex flex-wrap gap-1">
                                        {(h.features ?? []).slice(0, 4).map(f => (
                                            <span key={f} className="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">
                                                {FEATURE_ICON[f] ?? '✦'} {f}
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </Link>
                    ))}
                </div>
            </section>

            {/* CTA banner */}
            <section className="bg-emerald-600 text-white">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 py-12 text-center">
                    <h2 className="text-2xl font-bold mb-3">Need to see a doctor?</h2>
                    <p className="text-emerald-100 mb-6">Fill in a quick request form and a hospital coordinator will reach out to confirm your appointment.</p>
                    <Link href="/book-appointment" className="bg-white text-emerald-700 font-semibold px-6 py-3 rounded-xl hover:bg-emerald-50 transition-colors">
                        Request an Appointment
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
