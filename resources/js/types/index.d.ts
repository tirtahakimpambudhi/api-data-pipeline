import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';
import rolesRoute from '@/routes/roles';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: string;
    username: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    role_id: number;
    created_at: string;
    updated_at: string;
    role?: Role;
    avatar?: string;
}

export interface Namespace {
    id: number;
    name: string;
    created_at: string;
    updated_at?: string;
}

export interface Service {
    id: number;
    name: string;
    namespace_id: number;
    full_name?: string;
    namespace?: Namespace;
    created_at: string;
    updated_at?: string;
}

export interface Channel {
    id: number;
    name: string;
    created_at: string;
    updated_at?: string;
}
export interface Environment {
    id: number;
    name: string;
    created_at: string;
    updated_at?: string;
}

export interface ServiceEnvironment {
    id: number;
    name: string;
    service_id: number;
    environment_id: number;
    service?: Service;
    environment?: Environment;
    created_at: string;
    updated_at?: string;
}

export interface Permission {
    id: number;
    resource_type: string;
    action: string;
    description: string;
}

export interface Role {
    id: number;
    name: string;
    description: string;
    permissions?: Permissions[];
}

export interface Configuration {
    id: number;
    name: string;
    service_environment_id: number;
    channel_id: number;
    service_environment?: ServiceEnvironment;
    channel?: Channel;
    destination?: {
        body_template?: string;
        extract: object;
        foreach: string;
        headers: object;
        method: string;
        rangePerRequest: number;
        retryCount: number;
        timeout: number;
        url: string;
    };
    source?: {
        body: object;
        headers: object;
        method: string;
        retryCount: number;
        timeout: number;
        url: string;
    };
    cron_expression: string;
    created_at: string;
    updated_at?: string;
}

export type PermissionResourceType =
    | 'channels'
    | 'configurations'
    | 'environments'
    | 'namespaces'
    | 'services'
    | 'services_environments'
    | 'permissions'
    | 'roles'
    | 'roles_permissions'
    | 'users';

export interface Permission {
    id: number;
    resource_type: PermissionResourceType;
    action: 'create' | 'read' | 'update' | 'delete';
    description: string | null;
}

export interface Role {
    id: number;
    name: string;
    description: string | null;
    permissions?: Permission[];
}


export type PaginatedResponse<T> = {
    data: T[];
    current_page: number;
    per_page: number;
    total: number;
};

export type Filters = {
    search?: string;
    page?: number;
    size?: number;
};
