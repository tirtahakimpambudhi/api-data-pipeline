import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useFlash } from '@/hooks/use-flash';
import AppLayout from '@/layouts/app-layout';
import usersRoute from '@/routes/users';
import { BreadcrumbItem, Role, User } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React from 'react';
import { Toaster } from 'sonner';

type PageProps = {
    user: User;
    roles: Role[];
    flash?: { message?: string; error?: string; success?: string };
};

export default function EditUsersPage({ user, roles }: PageProps) {
    const { props } = usePage<PageProps>();
    const { resetAll } = useFlash(props?.flash);

    const { data, setData, put, processing, errors, reset, isDirty } = useForm<{
        name: string;
        email: string;
        password?: string;
        password_confirmation?: string;
        role_id: string;
    }>({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        role_id: String(user.role_id),
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Users', href: usersRoute.index().url },
        { title: user.name, href: '#' },
        { title: 'Edit', href: '#' },
    ];

    const isDisabled = processing || !data.name.trim() || !data.email.trim() || !data.role_id;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Remove password fields if empty
        const submitData: any = { ...data };
        if (!submitData.password || submitData.password.trim() === '') {
            delete submitData.password;
            delete submitData.password_confirmation;
        }

        put(usersRoute.update(user.id).url, {
            data: submitData,
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit User - ${user.name}`} />
            <Toaster richColors position="top-right" />

            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-2xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-xl font-semibold">Edit User</h1>
                            <p className="text-sm text-muted-foreground">Update user information.</p>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link href={usersRoute.index().url}>Back to list</Link>
                        </Button>
                    </div>

                    <form id="edit-user-form" onSubmit={handleSubmit} className="space-y-6">
                        {/* Basic info */}
                        <div className="space-y-4">
                            <div>
                                <label className="mb-1 block text-sm font-medium">
                                    Name <span className="text-destructive">*</span>
                                </label>
                                <Input
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g. John Doe"
                                    disabled={processing}
                                />
                                {errors.name && <p className="mt-1 text-sm text-destructive">{errors.name}</p>}
                            </div>

                            <div>
                                <label className="mb-1 block text-sm font-medium">
                                    Email <span className="text-destructive">*</span>
                                </label>
                                <Input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="e.g. john@example.com"
                                    disabled={processing}
                                />
                                {errors.email && <p className="mt-1 text-sm text-destructive">{errors.email}</p>}
                            </div>

                            <div>
                                <label className="mb-1 block text-sm font-medium">Password</label>
                                <Input
                                    type="password"
                                    value={data.password || ''}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Leave blank to keep current password"
                                    disabled={processing}
                                />
                                <p className="mt-1 text-xs text-muted-foreground">Leave blank if you don't want to change the password</p>
                                {errors.password && <p className="mt-1 text-sm text-destructive">{errors.password}</p>}
                            </div>

                            <div>
                                <label className="mb-1 block text-sm font-medium">Confirm Password</label>
                                <Input
                                    type="password"
                                    value={data.password_confirmation || ''}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    placeholder="Confirm new password"
                                    disabled={processing}
                                />
                                {errors.password_confirmation && <p className="mt-1 text-sm text-destructive">{errors.password_confirmation}</p>}
                            </div>

                            <div>
                                <label className="mb-1 block text-sm font-medium">
                                    Role <span className="text-destructive">*</span>
                                </label>
                                <Select value={data.role_id} onValueChange={(value) => setData('role_id', value)} disabled={processing}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((role) => (
                                            <SelectItem key={role.id} value={String(role.id)}>
                                                {role.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.role_id && <p className="mt-1 text-sm text-destructive">{errors.role_id}</p>}
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-between">
                            <Button asChild variant="ghost">
                                <Link href={usersRoute.index().url}>Cancel</Link>
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
