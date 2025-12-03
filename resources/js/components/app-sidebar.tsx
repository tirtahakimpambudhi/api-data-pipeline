// AppSidebar.tsx

import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, settings } from '@/routes';
import channels from '@/routes/channels';
import configurations from '@/routes/configurations';
import environments from '@/routes/environments';
import namespaces from '@/routes/namespaces';
import roles from '@/routes/roles';
import users from '@/routes/users';
import serviceEnvironments from '@/routes/service-environments';
import services from '@/routes/services';
import { Permission, SharedData, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Cog, Shield, FolderTree, LayoutDashboard, Network, Radio, ServerCog, Settings, Wrench, User } from 'lucide-react';
import * as React from 'react';


function useUserPermissions(): Permission[] {
    const { auth } = usePage<SharedData>().props;
    return (auth?.user?.role?.permissions ?? []) as Permission[];
}

function usePermissionChecker() {
    const permissions = useUserPermissions();

    const hasResource = React.useCallback(
        (resourceType: Permission['resource_type']): boolean => permissions.some((p) => p.resource_type === resourceType),
        [permissions],
    );

    return {
        permissions,
        hasResource,
    };
}

export function AppSidebar() {
    const { hasResource } = usePermissionChecker();

    const generalItems: NavItem[] = [];

    generalItems.push({
        title: 'Dashboard',
        href: dashboard.url ? dashboard.url() : dashboard(),
        icon: LayoutDashboard,
    });

    if (hasResource('configurations')) {
        generalItems.push({
            title: 'Configuration',
            href: configurations.index.url(),
            icon: Cog,
        });
    }

    const masterItems: NavItem[] = [];

    if (hasResource('namespaces')) {
        masterItems.push({
            title: 'Namespace',
            href: namespaces.index.url(),
            icon: FolderTree,
        });
    }

    if (hasResource('services')) {
        masterItems.push({
            title: 'Service',
            href: services.index.url(),
            icon: ServerCog,
        });
    }

    if (hasResource('environments')) {
        masterItems.push({
            title: 'Environment',
            href: environments.index.url(),
            icon: Network,
        });
    }

    if (hasResource('channels')) {
        masterItems.push({
            title: 'Channel',
            href: channels.index.url(),
            icon: Radio,
        });
    }

    if (hasResource('services_environments')) {
        masterItems.push({
            title: 'Service Environment',
            href: serviceEnvironments.index.url(),
            icon: Wrench,
        });
    }

    if (hasResource('roles')) {
        masterItems.push({
            title: 'Roles',
            href: roles.index.url(),
            icon: Shield,
        });
    }

    if (hasResource('users')) {
        masterItems.push({
            title: 'User',
            href: users.index.url(),
            icon: User,
        });
    }


    const [open, setOpen] = React.useState(true);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard.url ? dashboard.url() : dashboard()} prefetch>
                                <h1 className="text-lg font-bold">Elastic Connector Alert</h1>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={generalItems} />

                {masterItems.length > 0 && (
                    <SidebarGroup className="px-2 py-0">
                        <SidebarGroupLabel>
                            <button type="button" onClick={() => setOpen((v) => !v)} className="flex w-full items-center justify-between">
                                <span>Master Data</span>
                                <ChevronDown className={`h-4 w-4 transition-transform ${open ? 'rotate-180' : ''}`} />
                            </button>
                        </SidebarGroupLabel>

                        {open && (
                            <div className="mt-1 ml-2">
                                <NavMain items={masterItems} />
                            </div>
                        )}
                    </SidebarGroup>
                )}
            </SidebarContent>

            <SidebarFooter>
                <Link href={settings.url ? settings.url() : settings()} prefetch>
                    <Settings />
                </Link>
            </SidebarFooter>
        </Sidebar>
    );
}
