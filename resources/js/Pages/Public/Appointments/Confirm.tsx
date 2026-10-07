import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

export default function AppointmentConfirm() {
    return (
        <PublicLayout>
            <Head title="Appointment Requested — MedCore" />

            <div className="max-w-lg mx-auto px-4 py-20 text-center">
                <div className="text-6xl mb-5">✅</div>
                <h1 className="text-2xl font-bold text-gray-900 mb-3">Request Submitted!</h1>
                <p className="text-gray-500 mb-6">
                    Thank you for reaching out. A hospital coordinator will contact you within 24 hours to confirm your appointment details.
                </p>
                <div className="flex justify-center gap-3">
                    <Link href="/" className="bg-blue-600 text-white font-semibold px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-colors text-sm">
                        Back to Home
                    </Link>
                    <Link href="/hospitals" className="border border-gray-300 text-gray-700 font-medium px-5 py-2.5 rounded-xl hover:bg-gray-50 transition-colors text-sm">
                        Browse Hospitals
                    </Link>
                </div>
            </div>
        </PublicLayout>
    );
}
