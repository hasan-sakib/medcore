import { ReactNode } from 'react';
import { Link } from '@inertiajs/react';

export default function PublicLayout({ children }: { children: ReactNode }) {
    return (
        <div className="min-h-screen flex flex-col bg-white">
            {/* Header */}
            <header className="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-sm">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                    <Link href="/" className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
                            <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <span className="font-bold text-lg text-gray-900">MedCore</span>
                    </Link>

                    <nav className="hidden md:flex items-center gap-6 text-sm font-medium text-gray-600">
                        <Link href="/hospitals" className="hover:text-blue-600 transition-colors">Hospitals</Link>
                        <Link href="/doctors" className="hover:text-blue-600 transition-colors">Doctors</Link>
                        <Link
                            href="/book-appointment"
                            className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Book Appointment
                        </Link>
                    </nav>

                    {/* Mobile nav */}
                    <div className="flex md:hidden items-center gap-3">
                        <Link href="/hospitals" className="text-sm text-gray-600 hover:text-blue-600">Hospitals</Link>
                        <Link href="/book-appointment" className="text-sm bg-blue-600 text-white px-3 py-1.5 rounded-lg">Book</Link>
                    </div>
                </div>
            </header>

            {/* Content */}
            <main className="flex-1">
                {children}
            </main>

            {/* Footer */}
            <footer className="bg-gray-900 text-gray-400 mt-16">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 py-10 grid grid-cols-2 md:grid-cols-4 gap-6 text-sm">
                    <div className="col-span-2 md:col-span-1">
                        <p className="font-semibold text-white text-base mb-2">MedCore</p>
                        <p className="text-xs leading-relaxed">Connecting patients with quality healthcare providers across the network.</p>
                    </div>
                    <div>
                        <p className="font-semibold text-white mb-2">Explore</p>
                        <ul className="space-y-1">
                            <li><Link href="/hospitals" className="hover:text-white transition-colors">Hospitals</Link></li>
                            <li><Link href="/doctors" className="hover:text-white transition-colors">Doctors</Link></li>
                            <li><Link href="/book-appointment" className="hover:text-white transition-colors">Book Appointment</Link></li>
                        </ul>
                    </div>
                    <div>
                        <p className="font-semibold text-white mb-2">For Staff</p>
                        <ul className="space-y-1">
                            <li><Link href="/login" className="hover:text-white transition-colors">Staff Login</Link></li>
                            <li><Link href="/portal" className="hover:text-white transition-colors">Patient Portal</Link></li>
                        </ul>
                    </div>
                    <div>
                        <p className="font-semibold text-white mb-2">Platform</p>
                        <ul className="space-y-1">
                            <li><span>Multi-Tenant ERP</span></li>
                            <li><span>HIPAA-Aligned</span></li>
                        </ul>
                    </div>
                </div>
                <div className="border-t border-gray-800 text-center py-4 text-xs">
                    © {new Date().getFullYear()} MedCore. All rights reserved.
                </div>
            </footer>
        </div>
    );
}
