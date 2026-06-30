export interface Tenant {
    id: number;
    name: string;
    slug: string;
    domain: string | null;
    status: 'active' | 'suspended' | 'trial';
    subscription_plan: string;
    trial_ends_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    tenant_id: number | null;
    email_verified_at: string | null;
    two_factor_confirmed_at: string | null;
}

export interface PageProps {
    auth: {
        user: User | null;
    };
    tenant: Pick<Tenant, 'id' | 'name' | 'slug'> | null;
    permissions: string[];
    roles: string[];
    flash: {
        success: string | null;
        error: string | null;
    };
}

export type PaginatedResource<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};
