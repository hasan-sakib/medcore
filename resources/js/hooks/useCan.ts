import { usePermissions } from '@/contexts/PermissionContext';

/**
 * Check one or more permissions for the current user.
 *
 * @example
 * const { can, hasRole } = useCan();
 * if (can('patients.edit')) { ... }
 * if (hasRole('doctor')) { ... }
 */
export function useCan() {
    return usePermissions();
}
