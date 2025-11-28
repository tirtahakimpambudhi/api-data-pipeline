import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import usersRoute from '@/routes/users';
import { BreadcrumbItem, User } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Mail, UserCircle, Shield, Calendar } from 'lucide-react';

type PageProps = {
    user: User;
};

const formatDateTime = (iso?: string) => {
    if (!iso) return '–';
    try {
        return new Date(iso).toLocaleString('id-ID', {
            dateStyle: 'long',
            timeStyle: 'short',
        });
    } catch {
        return '–';
    }
};

export default function UserDetailPage({ user }: PageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Users', href: usersRoute.index().url },
        { title: user.name, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`User Detail - ${user.name}`} />

            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-3xl space-y-6">
                    {/* Header */}
                    <div className="rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                        <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 className="text-2xl font-semibold">User Details</h1>
                                <p className="text-sm text-muted-foreground">View user information</p>
                            </div>
                            <div className="flex gap-2">
                                <Button asChild variant="outline" size="sm">
                                    <Link href={usersRoute.index().url}>Back to list</Link>
                                </Button>
                                <Button asChild size="sm">
                                    <Link href={usersRoute.edit({ user: user.id }).url}>Edit</Link>
                                </Button>
                            </div>
                        </div>

                        {/* User Info Grid */}
                        <div className="space-y-4">
                            {/* Name */}
                            <div className="flex items-start gap-3 rounded-lg border bg-background p-4">
                                <UserCircle className="mt-0.5 h-5 w-5 text-muted-foreground" />
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-muted-foreground">Name</p>
                                    <p className="mt-1 text-base font-semibold">{user.name}</p>
                                </div>
                            </div>

                            {/* Email */}
                            <div className="flex items-start gap-3 rounded-lg border bg-background p-4">
                                <Mail className="mt-0.5 h-5 w-5 text-muted-foreground" />
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-muted-foreground">Email</p>
                                    <p className="mt-1 text-base font-semibold">{user.email}</p>
                                </div>
                            </div>

                            {/* Role */}
                            <div className="flex items-start gap-3 rounded-lg border bg-background p-4">
                                <Shield className="mt-0.5 h-5 w-5 text-muted-foreground" />
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-muted-foreground">Role</p>
                                    <p className="mt-1 text-base font-semibold">{user.role?.name ?? '–'}</p>
                                    {user.role?.description && (
                                        <p className="mt-1 text-sm text-muted-foreground">{user.role.description}</p>
                                    )}
                                </div>
                            </div>

                            {/* Timestamps */}
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="flex items-start gap-3 rounded-lg border bg-background p-4">
                                    <Calendar className="mt-0.5 h-5 w-5 text-muted-foreground" />
                                    <div className="flex-1">
                                        <p className="text-sm font-medium text-muted-foreground">Created At</p>
                                        <p className="mt-1 text-sm">{formatDateTime(user.created_at)}</p>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3 rounded-lg border bg-background p-4">
                                    <Calendar className="mt-0.5 h-5 w-5 text-muted-foreground" />
                                    <div className="flex-1">
                                        <p className="text-sm font-medium text-muted-foreground">Updated At</p>
                                        <p className="mt-1 text-sm">{formatDateTime(user.updated_at)}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Permissions Section */}
                    {user.role?.permissions && user.role.permissions.length > 0 && (
                        <div className="rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                            <h2 className="mb-4 text-lg font-semibold">Role Permissions</h2>
                            <div className="space-y-3">
                                {(() => {
                                    // Group permissions by resource_type
                                    const grouped = user.role.permissions.reduce((acc, perm) => {
                                        const key = perm.resource_type;
                                        if (!acc[key]) acc[key] = [];
                                        acc[key].push(perm);
                                        return acc;
                                    }, {} as Record<string, typeof user.role.permissions>);

                                    return Object.entries(grouped)
                                        .sort(([a], [b]) => a.localeCompare(b))
                                        .map(([resource, perms]) => (
                                            <div key={resource} className="rounded-lg border bg-background p-3">
                                                <h3 className="mb-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                                    {resource.replace(/_/g, ' ')}
                                                </h3>
                                                <div className="flex flex-wrap gap-2">
                                                    {perms
                                                        .sort((a, b) => {
                                                            const order: Record<string, number> = {
                                                                create: 1,
                                                                read: 2,
                                                                update: 3,
                                                                delete: 4,
                                                            };
                                                            return (order[a.action] ?? 99) - (order[b.action] ?? 99);
                                                        })
                                                        .map((perm) => (
                                                            <span
                                                                key={perm.id}
                                                                className="inline-flex items-center rounded-md bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                                                            >
                                                                {perm.action}
                                                            </span>
                                                        ))}
                                                </div>
                                            </div>
                                        ));
                                })()}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
