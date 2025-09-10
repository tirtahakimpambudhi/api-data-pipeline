import { NavMain } from '@/components/nav-main';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { channel, configuration, dashboard, environment, namespace, service, serviceEnvironment, settings } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { Cog, FolderTree, LayoutDashboard, Network, Radio, ServerCog, Settings, Wrench } from 'lucide-react';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutDashboard,
    },
    {
        title: 'Namespace',
        href: namespace(),
        icon: FolderTree,
    },
    {
        title: 'Service',
        href: service(),
        icon: ServerCog,
    },
    {
        title: 'Environment',
        href: environment(),
        icon: Network,
    },
    {
        title: 'Channel',
        href: channel(),
        icon: Radio,
    },
    {
        title: 'Configuration',
        href: configuration(),
        icon: Cog,
    },
    {
        title: 'Service Environment',
        href: serviceEnvironment(),
        icon: Wrench,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <h1 className="text-lg font-bold">Aino SVC</h1>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>
            <SidebarFooter>
                    <Link href={settings()} prefetch>
                        <Settings />
                    </Link>
                </SidebarFooter>
        </Sidebar>
    );
}
