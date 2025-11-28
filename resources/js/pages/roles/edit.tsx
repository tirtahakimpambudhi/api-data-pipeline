import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { useFlash } from '@/hooks/use-flash';
import AppLayout from '@/layouts/app-layout';
import rolesRoute from '@/routes/roles';
import { BreadcrumbItem, Permission } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React from 'react';
import { Toaster } from 'sonner';

type Role = {
    id: number;
    name: string;
    description: string;
    permissions: Permission[];
};

type PageProps = {
    role: Role;
    permissions: Permission[];
    flash?: { message?: string; error?: string; success?: string };
};

export default function EditRolesPage({ role, permissions }: PageProps) {
    const { props } = usePage<PageProps>();
    const { resetAll } = useFlash(props?.flash);

    // Extract permission IDs from role.permissions
    const initialPermissionIds = React.useMemo(() => {
        return role.permissions.map((p) => Number(p.id));
    }, [role.permissions]);

    const { data, setData, put, processing, errors, reset, isDirty } = useForm<{
        name: string;
        description: string;
        permissions_ids: number[];
    }>({
        name: role.name,
        description: role.description,
        permissions_ids: initialPermissionIds,
    });

    const [search, setSearch] = React.useState('');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Roles', href: rolesRoute.index().url },
        { title: role.name, href: '#' },
        { title: 'Edit', href: '#' },
    ];

    const isDisabled = processing || !data.name.trim() || !data.description.trim() || (data.permissions_ids?.length ?? 0) === 0;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(rolesRoute.update(role.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                resetAll();
            },
        });
    };

    const handleReset = () => {
        reset();
        resetAll();
    };

    const togglePermission = (id: number) => {
        const current = Array.isArray(data.permissions_ids) ? data.permissions_ids : [];

        if (current.includes(id)) {
            setData(
                'permissions_ids',
                current.filter((pid) => pid !== id),
            );
        } else {
            setData('permissions_ids', [...current, id]);
        }
    };

    const selectedCount = data.permissions_ids?.length ?? 0;

    const filteredGroupedPermissions = React.useMemo(() => {
        const q = search.trim().toLowerCase();

        const source = q
            ? permissions.filter((p) => {
                  const label = `${p.resource_type} ${p.action} ${p.description ?? ''}`.toLowerCase();
                  return label.includes(q);
              })
            : permissions;

        const map: Record<string, Permission[]> = {};

        source.forEach((p) => {
            const key = p.resource_type;
            if (!map[key]) map[key] = [];
            map[key].push(p);
        });

        const actionOrder: Record<string, number> = {
            create: 1,
            read: 2,
            update: 3,
            delete: 4,
        };

        return Object.entries(map)
            .sort(([a], [b]) => a.localeCompare(b))
            .map(([resource, perms]) => [resource, perms.sort((a, b) => (actionOrder[a.action] ?? 99) - (actionOrder[b.action] ?? 99))]) as [
            string,
            Permission[],
        ][];
    }, [permissions, search]);

    const formatResourceLabel = (resource: string) => resource.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Role - ${role.name}`} />
            <Toaster richColors position="top-right" />

            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-3xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-xl font-semibold">Edit Role</h1>
                            <p className="text-sm text-muted-foreground">Update role details and permissions.</p>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link href={rolesRoute.index().url}>Back to list</Link>
                        </Button>
                    </div>

                    <form id="edit-role-form" onSubmit={handleSubmit} className="space-y-6">
                        {/* Basic info */}
                        <div className="space-y-4">
                            <div>
                                <label className="mb-1 block text-sm font-medium">
                                    Name <span className="text-destructive">*</span>
                                </label>
                                <Input
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g. Admin, Viewer, Operator"
                                    disabled={processing}
                                />
                                {errors.name && <p className="mt-1 text-sm text-destructive">{errors.name}</p>}
                            </div>

                            <div>
                                <label className="mb-1 block text-sm font-medium">
                                    Description <span className="text-destructive">*</span>
                                </label>
                                <textarea
                                    className="min-h-[80px] w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Short description of this role."
                                    disabled={processing}
                                />
                                {errors.description && <p className="mt-1 text-sm text-destructive">{errors.description}</p>}
                            </div>
                        </div>

                        {/* Permissions */}
                        <div className="space-y-3 rounded-lg border bg-background p-4">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 className="text-sm font-semibold">Permissions</h2>
                                    <p className="text-xs text-muted-foreground">Select one or more permissions for this role.</p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <input
                                        type="text"
                                        className="h-8 w-40 rounded-md border bg-card px-2 text-xs placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                        placeholder="Search permissions..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        disabled={processing}
                                    />
                                    <span className="text-xs text-muted-foreground">{selectedCount} selected</span>
                                </div>
                            </div>

                            {errors.permissions_ids && <p className="text-sm text-destructive">{errors.permissions_ids}</p>}

                            <div className="mt-2 max-h-72 space-y-3 overflow-y-auto rounded-md border bg-card p-3">
                                {filteredGroupedPermissions.length === 0 && (
                                    <p className="text-xs text-muted-foreground">No permissions match your search.</p>
                                )}

                                {filteredGroupedPermissions.map(([resource, perms]) => (
                                    <div key={resource} className="space-y-1 rounded-md bg-background p-2">
                                        <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            {formatResourceLabel(resource)}
                                        </div>
                                        <div className="mt-1 grid gap-1 sm:grid-cols-2">
                                            {perms.map((perm) => {
                                                const id = Number(perm.id);
                                                const currentPermissions = Array.isArray(data.permissions_ids) ? data.permissions_ids : [];
                                                const checked = currentPermissions.map((v) => Number(v)).includes(id);

                                                return (
                                                    <label
                                                        key={perm.id}
                                                        className="flex cursor-pointer items-center gap-2 rounded-md px-1 py-1 text-xs hover:bg-muted"
                                                    >
                                                        <Checkbox
                                                            checked={checked}
                                                            onCheckedChange={() => togglePermission(id)}
                                                            disabled={processing}
                                                        />
                                                        <span className="capitalize">
                                                            {perm.action}
                                                            {perm.description ? ` — ${perm.description}` : ''}
                                                        </span>
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-between">
                            <Button asChild variant="ghost">
                                <Link href={rolesRoute.index().url}>Cancel</Link>
                            </Button>
                            <div className="flex gap-2">
                                <Button type="button" variant="outline" onClick={handleReset} disabled={!isDirty || processing}>
                                    Reset
                                </Button>
                                <Button type="submit" disabled={isDisabled}>
                                    {processing ? 'Updating...' : 'Update'}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
