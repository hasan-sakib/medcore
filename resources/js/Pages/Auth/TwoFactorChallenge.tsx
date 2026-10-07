import { FormEventHandler, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthLayout from '@/Layouts/AuthLayout';

export default function TwoFactorChallenge() {
    const [useRecovery, setUseRecovery] = useState(false);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        code: '',
        recovery_code: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/two-factor-challenge', { onFinish: () => reset('code', 'recovery_code') });
    };

    const switchMode = () => {
        clearErrors();
        reset('code', 'recovery_code');
        setUseRecovery(!useRecovery);
    };

    return (
        <AuthLayout
            title="Two-factor authentication"
            description={
                useRecovery
                    ? 'Enter one of your saved recovery codes.'
                    : 'Enter the 6-digit code from your authenticator app.'
            }
        >
            <Head title="Two-Factor Challenge" />

            <form onSubmit={submit} className="space-y-5">
                {useRecovery ? (
                    <div>
                        <label htmlFor="recovery_code" className="form-label">Recovery code</label>
                        <input
                            id="recovery_code"
                            type="text"
                            value={data.recovery_code}
                            className="form-input font-mono"
                            autoComplete="off"
                            autoFocus
                            onChange={(e) => setData('recovery_code', e.target.value)}
                        />
                        {errors.recovery_code && <p className="form-error">{errors.recovery_code}</p>}
                    </div>
                ) : (
                    <div>
                        <label htmlFor="code" className="form-label">Authentication code</label>
                        <input
                            id="code"
                            type="text"
                            inputMode="numeric"
                            pattern="[0-9 ]*"
                            maxLength={7}
                            value={data.code}
                            className="form-input font-mono tracking-widest"
                            autoComplete="one-time-code"
                            autoFocus
                            onChange={(e) => setData('code', e.target.value)}
                        />
                        {errors.code && <p className="form-error">{errors.code}</p>}
                    </div>
                )}

                <button type="submit" disabled={processing} className="btn-primary w-full">
                    {processing ? 'Verifying…' : 'Verify'}
                </button>

                <div className="flex items-center justify-between text-sm">
                    <button type="button" onClick={switchMode} className="text-primary-600 hover:text-primary-700">
                        {useRecovery ? 'Use authenticator code' : 'Use a recovery code'}
                    </button>
                    <Link href="/login" className="text-gray-500 hover:text-gray-700">Back to sign in</Link>
                </div>
            </form>
        </AuthLayout>
    );
}
