import { FormEventHandler } from 'react';
import { Link } from '@inertiajs/react';

export interface UserFormData {
    name: string;
    email: string;
    role: string;
    password: string;
    is_active: boolean;
}

interface Props {
    data: UserFormData;
    setData: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void;
    errors: Partial<Record<keyof UserFormData, string>>;
    processing: boolean;
    roles: string[];
    mode: 'create' | 'edit';
    onSubmit: FormEventHandler;
    isSelf?: boolean;
}

const roleLabel = (role: string) => role.replace(/-/g, ' ').replace(/^./, (c) => c.toUpperCase());

export default function UserForm({ data, setData, errors, processing, roles, mode, onSubmit, isSelf = false }: Props) {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div className="card p-6 space-y-4">
                <div>
                    <label htmlFor="name" className="form-label">Full name</label>
                    <input id="name" type="text" className="form-input" value={data.name}
                        onChange={(e) => setData('name', e.target.value)} autoFocus />
                    {errors.name && <p className="form-error">{errors.name}</p>}
                </div>

                <div>
                    <label htmlFor="email" className="form-label">Email</label>
                    <input id="email" type="email" className="form-input" value={data.email}
                        onChange={(e) => setData('email', e.target.value)} />
                    {errors.email && <p className="form-error">{errors.email}</p>}
                </div>

                <div>
                    <label htmlFor="role" className="form-label">Role</label>
                    <select id="role" className="form-input" value={data.role}
                        onChange={(e) => setData('role', e.target.value)}>
                        {mode === 'create' && <option value="">Select a role…</option>}
                        {roles.map((r) => (
                            <option key={r} value={r}>{roleLabel(r)}</option>
                        ))}
                    </select>
                    {errors.role && <p className="form-error">{errors.role}</p>}
                </div>

                <div>
                    <label htmlFor="password" className="form-label">
                        {mode === 'create' ? 'Password' : 'Reset password'}{' '}
                        <span className="text-gray-400">
                            {mode === 'create' ? '(min 12 chars)' : '(leave blank to keep current)'}
                        </span>
                    </label>
                    <input id="password" type="password" className="form-input" value={data.password}
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)} />
                    {errors.password && <p className="form-error">{errors.password}</p>}
                </div>

                {mode === 'edit' && (
                    <div>
                        <label className="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" checked={data.is_active} disabled={isSelf}
                                className="h-4 w-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500"
                                onChange={(e) => setData('is_active', e.target.checked)} />
                            Account active
                        </label>
                        {isSelf && <p className="text-xs text-gray-400 mt-1">You cannot deactivate your own account.</p>}
                        {errors.is_active && <p className="form-error">{errors.is_active}</p>}
                    </div>
                )}
            </div>

            <div className="flex items-center gap-3">
                <button type="submit" disabled={processing} className="btn-primary">
                    {processing ? 'Saving…' : mode === 'create' ? 'Create user' : 'Save changes'}
                </button>
                <Link href="/admin/users" className="btn-secondary">Cancel</Link>
            </div>
        </form>
    );
}
