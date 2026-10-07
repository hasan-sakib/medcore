import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface RoleRow {
    id: number;
    name: string;
    locked: boolean;
    users_count: number;
    permissions: string[];
}

interface Module {
    module: string;
    permissions: string[];
}

interface Props extends PageProps {
    tenantRoles: RoleRow[];
    modules: Module[];
}

const label = (s: string) => s.replace(/-/g, ' ').replace(/^./, (c) => c.toUpperCase());
const action = (permission: string) => permission.split('.').slice(1).join('.');

export default function RolesIndex({ tenantRoles, modules }: Props) {
    const [selectedId, setSelectedId] = useState<number>(tenantRoles[0]?.id ?? 0);
    // Local edits per role id; falls back to the server value.
    const [drafts, setDrafts] = useState<Record<number, string[]>>({});
    const [saving, setSaving] = useState(false);

    const role = tenantRoles.find((r) => r.id === selectedId);
    if (!role) {
        return (
            <AppLayout>
                <Head title="Roles" />
                <p className="text-gray-500">No roles found.</p>
            </AppLayout>
        );
    }

    const current = drafts[role.id] ?? role.permissions;
    const dirty = drafts[role.id] !== undefined;

    const toggle = (permission: string) => {
        const next = current.includes(permission)
            ? current.filter((p) => p !== permission)
            : [...current, permission];
        setDrafts({ ...drafts, [role.id]: next });
    };

    const toggleModule = (mod: Module, on: boolean) => {
        const rest = current.filter((p) => !mod.permissions.includes(p));
        setDrafts({ ...drafts, [role.id]: on ? [...rest, ...mod.permissions] : rest });
    };

    const save = () => {
        setSaving(true);
        router.put(
            `/admin/roles/${role.id}`,
            { permissions: current },
            {
                preserveScroll: true,
                onSuccess: () => {
                    const { [role.id]: _removed, ...others } = drafts;
                    setDrafts(others);
                },
                onFinish: () => setSaving(false),
            },
        );
    };

    return (
        <AppLayout>
            <Head title="Roles" />
            <div className="space-y-4">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900">Roles &amp; permissions</h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Choose which permissions each role grants. The tenant admin role always has full access.
                    </p>
                </div>

                <div className="grid gap-6 lg:grid-cols-[16rem_1fr]">
                    <div className="card p-2 space-y-1 self-start">
                        {tenantRoles.map((r) => (
                            <button
                                key={r.id}
                                onClick={() => setSelectedId(r.id)}
                                className={`w-full text-left px-3 py-2 rounded-lg text-sm flex items-center justify-between ${
                                    r.id === role.id ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-700 hover:bg-gray-50'
                                }`}
                            >
                                <span>{label(r.name)}</span>
                                <span className="text-xs text-gray-400">{r.users_count} user{r.users_count === 1 ? '' : 's'}</span>
                            </button>
                        ))}
                    </div>

                    <div className="card p-6 space-y-6">
                        <div className="flex items-center justify-between">
                            <h2 className="font-medium text-gray-900">{label(role.name)}</h2>
                            {role.locked ? (
                                <span className="text-xs text-gray-500">Locked — has every permission</span>
                            ) : (
                                <button onClick={save} disabled={!dirty || saving} className="btn-primary">
                                    {saving ? 'Saving…' : 'Save permissions'}
                                </button>
                            )}
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            {modules.map((mod) => {
                                const checkedCount = mod.permissions.filter((p) => current.includes(p)).length;
                                const all = checkedCount === mod.permissions.length;
                                return (
                                    <fieldset key={mod.module} className="border border-gray-200 rounded-lg p-3">
                                        <legend className="px-1 text-sm font-medium text-gray-900">{label(mod.module)}</legend>
                                        {!role.locked && (
                                            <button
                                                type="button"
                                                onClick={() => toggleModule(mod, !all)}
                                                className="text-xs text-primary-600 hover:underline mb-2"
                                            >
                                                {all ? 'Clear all' : 'Select all'}
                                            </button>
                                        )}
                                        <div className="space-y-1">
                                            {mod.permissions.map((permission) => (
                                                <label key={permission} className="flex items-center gap-2 text-sm text-gray-700">
                                                    <input
                                                        type="checkbox"
                                                        className="h-4 w-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500"
                                                        checked={role.locked || current.includes(permission)}
                                                        disabled={role.locked}
                                                        onChange={() => toggle(permission)}
                                                    />
                                                    {label(action(permission))}
                                                </label>
                                            ))}
                                        </div>
                                    </fieldset>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
