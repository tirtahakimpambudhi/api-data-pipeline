import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import serviceEnvironmentRoute from '@/routes/service-environments';
import { Environment, Service } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React from 'react';
import { useFlash } from '@/hooks/use-flash';
import { Toaster } from 'sonner';

type PageProps = {
    services: Service[];
    environments: Environment[];
    flash?: { message?: string; error?: string, success?: string };
};

export default function CreateServiceEnvironmentPage({ services, environments }: PageProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        service_id: '' as string | number | '',
        environment_id: '' as string | number | '',
    });

    const { props } = usePage<PageProps>();
    const {resetAll} = useFlash(props?.flash);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(serviceEnvironmentRoute.store().url, { preserveScroll: true });
    };

    const handleReset = () => {
        reset('service_id', 'environment_id');
        resetAll();
    };

    const isDirty = data.service_id !== '' || data.environment_id !== '';
    const isDisabled = processing || !String(data.service_id).trim() || !String(data.environment_id).trim();

    return (
        <AppLayout>
            <Head title="Create Service Environment" />
            <Toaster richColors theme="system" position="top-right" />
            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <div className="mb-4 flex items-center justify-between">
                        <h1 className="text-xl font-semibold">Create Service Environment</h1>
                        <div className="flex gap-2">
                            <Button type="button" variant="outline" onClick={handleReset} disabled={!isDirty || processing}>
                                Reset
                            </Button>
                            <Button type="submit" form="create-service-environment-form" disabled={isDisabled}>
                                {processing ? 'Saving...' : 'Save'}
                            </Button>
                        </div>
                    </div>

                    <p className="mb-4 text-muted-foreground">Pilih service dan environment untuk membuat relasi baru.</p>

                    <form id="create-service-environment-form" onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="mb-1 block font-medium">Service</label>
                            <Select value={String(data.service_id)} onValueChange={(val) => setData('service_id', val)} disabled={processing}>
                                <SelectTrigger className={errors.service_id ? 'border-destructive' : ''}>
                                    <SelectValue placeholder="Pilih service" />
                                </SelectTrigger>
                                <SelectContent>
                                    {services.map((srv) => (
                                        <SelectItem key={srv.id} value={String(srv.id)}>
                                            {srv.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.service_id && <p className="mt-1 text-sm text-destructive">{errors.service_id}</p>}
                        </div>

                        <div>
                            <label className="mb-1 block font-medium">Environment</label>
                            <Select value={String(data.environment_id)} onValueChange={(val) => setData('environment_id', val)} disabled={processing}>
                                <SelectTrigger className={errors.environment_id ? 'border-destructive' : ''}>
                                    <SelectValue placeholder="Pilih environment" />
                                </SelectTrigger>
                                <SelectContent>
                                    {environments.map((env) => (
                                        <SelectItem key={env.id} value={String(env.id)}>
                                            {env.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.environment_id && <p className="mt-1 text-sm text-destructive">{errors.environment_id}</p>}
                        </div>

                        <div className="flex items-center justify-end gap-2">
                            <Button asChild variant="ghost" disabled={processing}>
                                <Link href={serviceEnvironmentRoute.index().url}>Cancel</Link>
                            </Button>
                            <Button type="submit" disabled={isDisabled}>
                                {processing ? 'Saving...' : 'Save'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
