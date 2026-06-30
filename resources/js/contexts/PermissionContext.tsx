import { createContext, useContext, ReactNode } from 'react';

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
    const can = (permission: string): boolean =>
        permissions.includes(permission);

    const hasRole = (role: string): boolean =>
        roles.includes(role);

    return (
        <PermissionContext.Provider value={{ permissions, roles, can, hasRole }}>
            {children}
        </PermissionContext.Provider>
    );
}

export function usePermissions(): PermissionContextValue {
    return useContext(PermissionContext);
}
