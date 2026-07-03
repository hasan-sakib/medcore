import { createContext, ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';

interface PermissionContextValue {
    permissions: string[];
    roles: string[];
    can: (permission: string) => boolean;
    hasRole: (role: string) => boolean;
}

const PermissionContext = createContext<PermissionContextValue>({
    permissions: [],
    roles: [],
    can: () => false,
    hasRole: () => false,
});

export function PermissionProvider({
    children,
    permissions,
    roles,
}: {
    children: ReactNode;
    permissions: string[];
    roles: string[];
}) {
    const can = (permission: string): boolean => permissions.includes(permission);
    const hasRole = (role: string): boolean => roles.includes(role);

    return (
        <PermissionContext.Provider value={{ permissions, roles, can, hasRole }}>
            {children}
        </PermissionContext.Provider>
    );
}

/**
 * Always reads live permissions from the current Inertia page props so that
 * client-side navigation (e.g. post-login redirect) picks up the authenticated
 * user's permissions without requiring a full page reload.
 */
export function usePermissions(): PermissionContextValue {
    const { permissions, roles } = usePage<PageProps>().props;
    const permArray = (permissions as string[]) ?? [];
    const roleArray = (roles as string[]) ?? [];
    return {
        permissions: permArray,
        roles: roleArray,
        can: (p: string) => permArray.includes(p),
        hasRole: (r: string) => roleArray.includes(r),
    };
}
