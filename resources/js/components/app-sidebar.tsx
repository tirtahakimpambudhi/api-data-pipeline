import { NavMain } from '@/components/nav-main';
import { Sidebar, SidebarContent, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { channels, configuration, dashboard, environments, namespace, services } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { FolderTree, LayoutDashboard, Network, Radio, ServerCog, Settings } from 'lucide-react';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutDashboard,
    },
    {
        title: 'Namespaces',
        href: namespace(),
        icon: FolderTree,
    },
    {
        title: 'Services',
        href: services(),
        icon: ServerCog,
    },
    {
        title: 'Environments',
        href: environments(),
        icon: Network,
    },
    {
        title: 'Channels',
        href: channels(),
        icon: Radio,
    },
    {
        title: 'Configuration',
        href: configuration(),
        icon: Settings,
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
        </Sidebar>
    );
}
