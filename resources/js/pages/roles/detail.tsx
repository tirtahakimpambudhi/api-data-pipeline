import { Button } from '@/components/ui/button';
import { useFlash } from '@/hooks/use-flash';
import AppLayout from '@/layouts/app-layout';
import rolesRoute from '@/routes/roles';
import { BreadcrumbItem, Role } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';
import { Toaster } from 'sonner';

interface RoleDetailProps {
    role: Role;
    flash?: {
        message?: string;
        error?: string;
        success?: string;
    };
}

export default function RoleDetailPage({ role }: RoleDetailProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Roles', href: rolesRoute.index().url },
        { title: role.name, href: '#' },
    ];

    const { props } = usePage<RoleDetailProps>();

    // Trigger flash (toast) + siapkan fungsi reset
    const { resetAll } = useFlash(props?.flash);

    // Setelah flash dipakai, reset supaya tidak muncul berulang
    useEffect(() => {
        if (!props?.flash) return;
        resetAll();
    }, [props?.flash, resetAll]);

    const groupedPermissions = useMemo(() => {
        const map: Record<string, string[]> = {};

        (role.permissions ?? []).forEach((p) => {
            if (!map[p.resource_type]) map[p.resource_type] = [];
            if (!map[p.resource_type].includes(p.action)) {
                map[p.resource_type].push(p.action);
            }
        });

        const order: Record<string, number> = {
            create: 1,
            read: 2,
            update: 3,
            delete: 4,
        };

        Object.keys(map).forEach((key) => {
            map[key] = map[key].sort((a, b) => (order[a] ?? 99) - (order[b] ?? 99));
        });

        return map;
    }, [role.permissions]);

    const totalPermissions = role.permissions?.length ?? 0;
    const totalResources = Object.keys(groupedPermissions).length;

    const formatResourceLabel = (resource: string) => {
        return resource.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Role: ${role.name}`} />
            <Toaster richColors position="top-right" />

            <div className="flex flex-col gap-4 p-4 lg:p-6">
                {/* Header */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{role.name}</h1>
                        <p className="text-sm text-muted-foreground">{role.description ?? 'No description.'}</p>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href={rolesRoute.index().url}>Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={rolesRoute.edit({ role: role.id }).url}>Edit</Link>
                        </Button>
                    </div>
                </div>

                {/* Summary / Meta card */}
                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-xl border bg-card p-4 text-card-foreground shadow-sm md:col-span-2">
                        <h2 className="mb-2 text-sm font-medium text-muted-foreground">Role Information</h2>
                        <p className="text-sm leading-relaxed">
                            This role is used to control access and permissions within the system. You can update its name, description, and attached
                            permissions from the edit page.
                        </p>
                    </div>
                    <div className="rounded-xl border bg-card p-4 text-card-foreground shadow-sm">
                        <h2 className="mb-2 text-sm font-medium text-muted-foreground">Overview</h2>
                        <dl className="space-y-2 text-sm">
                            <div className="flex items-center justify-between">
                                <dt className="text-muted-foreground">Resources</dt>
                                <dd className="font-medium">{totalResources}</dd>
                            </div>
                            <div className="flex items-center justify-between">
                                <dt className="text-muted-foreground">Total permissions</dt>
                                <dd className="font-medium">{totalPermissions}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {/* Permissions */}
                <div className="rounded-xl border bg-card p-4 text-card-foreground shadow-sm">
                    <div className="mb-3 flex items-center justify-between gap-2">
                        <div>
                            <h2 className="text-lg font-semibold">Permissions</h2>
                            <p className="text-xs text-muted-foreground">
                                Permissions are grouped by resource. Actions are shown as small badges to keep things compact.
                            </p>
                        </div>
                    </div>

                    {(!role.permissions || role.permissions.length === 0) && (
                        <p className="text-sm text-muted-foreground">This role has no permissions.</p>
                    )}

                    {role.permissions && role.permissions.length > 0 && (
                        <div className="mt-2 max-h-96 space-y-3 overflow-y-auto pr-1">
                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                {Object.entries(groupedPermissions).map(([resource, actions]) => (
                                    <div key={resource} className="rounded-lg border bg-background p-3 text-sm">
                                        <div className="mb-2 flex items-center justify-between gap-2">
                                            <span className="font-medium">{formatResourceLabel(resource)}</span>
                                            <span className="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                                {actions.length} action{actions.length > 1 ? 's' : ''}
                                            </span>
                                        </div>
                                        <div className="flex flex-wrap gap-1.5">
                                            {actions.map((act) => (
                                                <span
                                                    key={act}
                                                    className="inline-flex items-center rounded-full border px-2 py-0.5 text-xs capitalize"
                                                >
                                                    {act}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
