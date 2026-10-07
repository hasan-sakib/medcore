import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';

interface Hospital {
    id: number;
    name: string;
    slug: string;
    tagline: string | null;
    city: string | null;
    phone: string | null;
    logo_url: string | null;
    features: string[] | null;
    description: string | null;
}

interface Props {
    hospitals: Hospital[];
    search: string;
}

export default function PublicHospitalsIndex({ hospitals, search: initialSearch }: Props) {
    const [search, setSearch] = useState(initialSearch);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/hospitals', search ? { search } : {}, { preserveState: true });
    };

    return (
        <PublicLayout>
            <Head title="Hospitals — MedCore" />

            {/* Search header */}
            <div className="bg-gradient-to-r from-blue-700 to-indigo-700 text-white">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 py-10">
                    <h1 className="text-2xl font-bold mb-4">Find a Hospital</h1>
                    <form onSubmit={handleSearch} className="flex gap-2">
                        <input
                            type="text"
                            placeholder="Search by name, city, or specialty…"
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                            className="flex-1 rounded-xl px-4 py-2.5 text-gray-900 text-sm outline-none"
                        />
                        <button type="submit" className="bg-white text-blue-700 font-semibold px-5 py-2.5 rounded-xl hover:bg-blue-50">
                            Search
                        </button>
                        {initialSearch && (
                            <button
                                type="button"
                                onClick={() => { setSearch(''); router.get('/hospitals'); }}
                                className="text-white underline text-sm px-2"
                            >
                                Clear
                            </button>
                        )}
                    </form>
                </div>
            </div>

            <div className="max-w-6xl mx-auto px-4 sm:px-6 py-10">
                <p className="text-sm text-gray-500 mb-5">
                    {hospitals.length} hospital{hospitals.length !== 1 ? 's' : ''} found
                    {initialSearch && <> for "<strong>{initialSearch}</strong>"</>}
                </p>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {hospitals.map(h => (
                        <div key={h.id} className="bg-white rounded-xl border border-gray-200 p-5 flex gap-4 hover:shadow-md transition-shadow">
                            <div className="w-16 h-16 flex-shrink-0 rounded-xl bg-blue-50 flex items-center justify-center text-3xl">
                                {h.logo_url ? <img src={h.logo_url} alt="" className="w-12 h-12 object-contain" /> : '🏥'}
                            </div>
                            <div className="flex-1 min-w-0">
                                <Link href={`/hospitals/${h.slug}`} className="font-semibold text-gray-900 hover:text-blue-600 transition-colors">
                                    {h.name}
                                </Link>
                                {h.city && <p className="text-xs text-gray-400 mt-0.5">📍 {h.city}</p>}
                                {h.tagline && <p className="text-sm text-gray-500 mt-1 line-clamp-2">{h.tagline}</p>}
                                <div className="mt-2 flex flex-wrap gap-1">
                                    {(h.features ?? []).slice(0, 5).map(f => (
                                        <span key={f} className="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">{f}</span>
                                    ))}
                                </div>
                                <div className="mt-3 flex items-center gap-3">
                                    <Link href={`/hospitals/${h.slug}`} className="text-xs text-blue-600 font-medium hover:underline">
                                        View details →
                                    </Link>
                                    <Link href={`/book-appointment?hospital_id=${h.id}`} className="text-xs bg-emerald-600 text-white px-3 py-1 rounded-lg hover:bg-emerald-700">
                                        Book
                                    </Link>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {hospitals.length === 0 && (
                    <div className="text-center py-16">
                        <p className="text-4xl mb-3">🔍</p>
                        <p className="text-gray-600 font-medium">No hospitals found</p>
                        <p className="text-sm text-gray-400 mt-1">Try a different search term</p>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
