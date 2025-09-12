import { NavMain } from '@/components/nav-main';
import {
  Sidebar, SidebarContent, SidebarFooter, SidebarHeader,
  SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarGroup, SidebarGroupLabel
} from '@/components/ui/sidebar';
import { configuration, dashboard, settings } from '@/routes';
import channels from '@/routes/channels';
import environments from '@/routes/environments';
import namespaces from '@/routes/namespaces';
import serviceEnvironments from '@/routes/service-environments';
import services from '@/routes/services';
import { SharedData, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Cog, FolderTree, LayoutDashboard, Network, Radio, ServerCog, Settings, Wrench, ChevronDown } from 'lucide-react';
import * as React from 'react';

function useUserRole(): 'almighty' | 'slave' | undefined {
  const { auth } = usePage<SharedData>().props;
  const name = auth?.user?.role?.name as 'almighty' | 'slave' | undefined;
  if (name) return name;

  const id = auth?.user?.role_id;
  if (id === 1) return 'almighty';
  if (id === 2) return 'slave';
  return undefined;
}

export function AppSidebar() {
  const role = useUserRole();

  const generalItems: NavItem[] = [
    ...(role === 'almighty'
      ? [{
          title: 'Dashboard',
          href: dashboard.url ? dashboard.url() : dashboard(), 
          icon: LayoutDashboard,
        }] as NavItem[]
      : []),
    {
      title: 'Configuration',
      href: configuration.url(),
      icon: Cog,
    },
  ];

  const masterItems: NavItem[] = role === 'almighty' ? [
    { title: 'Namespace', href: namespaces.index.url(), icon: FolderTree },
    { title: 'Service', href: services.index.url(), icon: ServerCog },
    { title: 'Environment', href: environments.index.url(), icon: Network },
    { title: 'Channel', href: channels.index.url(), icon: Radio },
    { title: 'Service Environment', href: serviceEnvironments.index.url(), icon: Wrench },
  ] : [];

  const [open, setOpen] = React.useState(true);

  return (
    <Sidebar collapsible="icon" variant="inset">
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" asChild>
              <Link href={dashboard.url ? dashboard.url() : dashboard()} prefetch>
                <h1 className="text-lg font-bold">Aino SVC</h1>
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
              <button
                type="button"
                onClick={() => setOpen(v => !v)}
                className="w-full flex items-center justify-between"
              >
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
