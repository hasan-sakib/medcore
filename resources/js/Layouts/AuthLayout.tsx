import { ReactNode } from 'react';

interface AuthLayoutProps {
    children: ReactNode;
    title?: string;
    description?: string;
}

export default function AuthLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="min-h-screen bg-gradient-to-br from-primary-900 via-primary-800 to-primary-700 flex items-center justify-center p-4">
            <div className="w-full max-w-md">
                {/* Logo / Brand */}
                <div className="text-center mb-8">
                    <div className="inline-flex items-center justify-center w-14 h-14 bg-white rounded-2xl shadow-lg mb-4">
                        <svg className="w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                                d="M4.5 12.5l2 2 4-4m0 0l4 4 4-4M9 12.5V7m0 0H6m3 0h3" />
                        </svg>
                    </div>
                    <h1 className="text-2xl font-bold text-white">MedCore</h1>
                    <p className="text-primary-200 text-sm mt-1">Healthcare ERP Platform</p>
                </div>

                {/* Card */}
                <div className="bg-white rounded-2xl shadow-2xl p-8">
                    {(title || description) && (
                        <div className="mb-6">
                            {title && <h2 className="text-xl font-semibold text-gray-900">{title}</h2>}
                            {description && <p className="text-sm text-gray-500 mt-1">{description}</p>}
                        </div>
                    )}
                    {children}
                </div>
            </div>
        </div>
    );
}
