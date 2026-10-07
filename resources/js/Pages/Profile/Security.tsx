import { FormEventHandler, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface Props extends PageProps {
    twoFactor: {
        enabled: boolean;
        confirmed_at: string | null;
        recovery_codes_remaining: number;
    };
    setup: { secret: string; otpauth_url: string; qr: string } | null;
    recoveryCodes: string[] | null;
}

export default function Security({ twoFactor, setup, recoveryCodes }: Props) {
    const confirmForm = useForm({ code: '' });
    const passwordForm = useForm({ password: '' });
    const [starting, setStarting] = useState(false);

    const start = () => {
        setStarting(true);
        router.post('/profile/security/two-factor', {}, { preserveScroll: true, onFinish: () => setStarting(false) });
    };

    const confirm: FormEventHandler = (e) => {
        e.preventDefault();
        confirmForm.post('/profile/security/two-factor/confirm', {
            preserveScroll: true,
            onSuccess: () => confirmForm.reset(),
        });
    };

    const regenerate: FormEventHandler = (e) => {
        e.preventDefault();
        passwordForm.post('/profile/security/two-factor/recovery-codes', {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    };

    const disable = () => {
        if (!confirmDisable()) return;
        passwordForm.delete('/profile/security/two-factor', {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    };

    const confirmDisable = () => window.confirm('Disable two-factor authentication for your account?');

    return (
        <AppLayout>
            <Head title="Security" />

            <div className="max-w-2xl space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900">Security</h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Protect your account with a time-based one-time code from an authenticator app.
                    </p>
                </div>

                {recoveryCodes && recoveryCodes.length > 0 && (
                    <div className="card p-6 border-amber-300 space-y-3">
                        <h2 className="font-medium text-gray-900">Save your recovery codes</h2>
                        <p className="text-sm text-gray-600">
                            Each code can be used once if you lose access to your authenticator. They will not be shown again.
                        </p>
                        <ul className="grid grid-cols-2 gap-2 font-mono text-sm bg-gray-50 rounded-lg p-4">
                            {recoveryCodes.map((code) => (
                                <li key={code}>{code}</li>
                            ))}
                        </ul>
                        <button
                            type="button"
                            className="btn-secondary"
                            onClick={() => navigator.clipboard?.writeText(recoveryCodes.join('\n'))}
                        >
                            Copy codes
                        </button>
                    </div>
                )}

                <div className="card p-6 space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="font-medium text-gray-900">Two-factor authentication</h2>
                        <span className={twoFactor.enabled ? 'badge-active' : 'badge-suspended'}>
                            {twoFactor.enabled ? 'Enabled' : 'Not enabled'}
                        </span>
                    </div>

                    {!twoFactor.enabled && !setup && (
                        <>
                            <p className="text-sm text-gray-600">
                                Once enabled, you will be asked for a 6-digit code every time you sign in.
                            </p>
                            <button onClick={start} disabled={starting} className="btn-primary">
                                {starting ? 'Preparing…' : 'Enable two-factor authentication'}
                            </button>
                        </>
                    )}

                    {!twoFactor.enabled && setup && (
                        <form onSubmit={confirm} className="space-y-4">
                            <p className="text-sm text-gray-600">
                                Scan this QR code with an authenticator app (Google Authenticator, Authy, 1Password…), then enter the
                                6-digit code it shows to finish setup.
                            </p>
                            <img src={setup.qr} alt="Two-factor QR code" className="w-48 h-48 border border-gray-200 rounded-lg" />
                            <div>
                                <p className="text-xs text-gray-500">Can't scan? Enter this key manually:</p>
                                <p className="font-mono text-sm break-all select-all">{setup.secret}</p>
                                <p className="font-mono text-xs text-gray-400 break-all select-all mt-1">{setup.otpauth_url}</p>
                            </div>
                            <div>
                                <label htmlFor="code" className="form-label">6-digit code</label>
                                <input
                                    id="code"
                                    type="text"
                                    inputMode="numeric"
                                    maxLength={7}
                                    autoComplete="one-time-code"
                                    value={confirmForm.data.code}
                                    className="form-input max-w-xs font-mono tracking-widest"
                                    onChange={(e) => confirmForm.setData('code', e.target.value)}
                                />
                                {confirmForm.errors.code && <p className="form-error">{confirmForm.errors.code}</p>}
                            </div>
                            <button type="submit" disabled={confirmForm.processing} className="btn-primary">
                                {confirmForm.processing ? 'Verifying…' : 'Confirm and enable'}
                            </button>
                        </form>
                    )}

                    {twoFactor.enabled && (
                        <div className="space-y-4">
                            <p className="text-sm text-gray-600">
                                Recovery codes remaining: <span className="font-medium">{twoFactor.recovery_codes_remaining}</span>
                            </p>
                            <form onSubmit={regenerate} className="space-y-3">
                                <div>
                                    <label htmlFor="password" className="form-label">Confirm your password</label>
                                    <input
                                        id="password"
                                        type="password"
                                        autoComplete="current-password"
                                        value={passwordForm.data.password}
                                        className="form-input max-w-xs"
                                        onChange={(e) => passwordForm.setData('password', e.target.value)}
                                    />
                                    {passwordForm.errors.password && <p className="form-error">{passwordForm.errors.password}</p>}
                                </div>
                                <div className="flex items-center gap-3">
                                    <button type="submit" disabled={passwordForm.processing} className="btn-secondary">
                                        Regenerate recovery codes
                                    </button>
                                    <button type="button" onClick={disable} disabled={passwordForm.processing} className="btn-danger">
                                        Disable two-factor
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
