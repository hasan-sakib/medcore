import { ReactNode } from 'react';
import { useCan } from '@/hooks/useCan';

interface CanProps {
    permission?: string;
    role?: string;
    children: ReactNode;
    fallback?: ReactNode;
}

/**
 * Conditionally render children based on the current user's permissions or role.
 * Server-side policies remain the authoritative enforcement; this is UI only.
 *
 * @example
 * <Can permission="patients.edit">
 *   <EditButton />
 * </Can>
 *
 * <Can role="doctor" fallback={<span>No access</span>}>
 *   <ClinicalNotes />
 * </Can>
 */
export function Can({ permission, role, children, fallback = null }: CanProps) {
    const { can, hasRole } = useCan();

    if (permission && !can(permission)) return <>{fallback}</>;
    if (role && !hasRole(role)) return <>{fallback}</>;

    return <>{children}</>;
}
