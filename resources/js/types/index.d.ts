import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

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
    namespace?: Namespace;
    created_at: string;
    updated_at?: string;
}

export interface Channel {
    id: number;
    name: string;
}

export interface Environment {
    id: number;
    name: string;
}

export interface ServiceEnvironment {
    id: number;
    service_id: number;
    environment_id: number;
    service?: Service;
    environment?: Environment;
}

export interface Configuration {
    id: number;
    service_environment_id: number;
    channel_id: number;
    service_environment?: ServiceEnvironment;
    channel?: Channel;
}

export interface Role {
    id: number;
    name: string;
    description: string | null;
    permissions?: Permission[];
}

export interface Permission {
    id: number;
    resource_type: 'namespace' | 'service' | 'environment' | 'service_environment' | 'configuration' | 'channel' | 'user';
    action: 'view' | 'edit' | 'configure' | 'deploy' | 'manage' | 'assign_roles';
    description: string | null;
}

export interface RolePermission {
    id: number;
    role_id: number;
    permission_id: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedResponse<T> {
    current_page: number;
    data: T[];
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}
