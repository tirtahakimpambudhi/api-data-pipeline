import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import serviceEnvironmentRoute from '@/routes/service-environments';
import { Environment, Service, ServiceEnvironment } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { useRef } from 'react';
import { Toaster } from 'sonner';
import { useFlash } from '@/hooks/use-flash';


type PageProps = {
    serviceEnvironment: ServiceEnvironment;
    services: Service[];
    environments: Environment[];
    flash?: { message?: string; error?: string, success?: string };
};

export default function ServiceEnvironmentEditPage({
    serviceEnvironment,
    services,
    environments,
}: PageProps) {
    const initial = useRef({
        service_id: serviceEnvironment.service?.id ?? '',
        environment_id: serviceEnvironment.environment?.id ?? '',
    });
    const { props } = usePage<PageProps>();
    const {resetAll} = useFlash(props?.flash);
    const { data, setData, put, processing, errors, wasSuccessful } = useForm({
        service_id: initial.current.service_id as string | number | '',
        environment_id: initial.current.environment_id as string | number | '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(serviceEnvironmentRoute.update({ service_environment: serviceEnvironment.id }).url, {
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        setData('service_id', initial.current.service_id);
        setData('environment_id', initial.current.environment_id);
        resetAll();
    };

    const isDirty =
        String(data.service_id ?? '') !== String(initial.current.service_id ?? '') ||
        String(data.environment_id ?? '') !== String(initial.current.environment_id ?? '');

    const isDisabled = processing || !String(data.service_id).trim() || !String(data.environment_id).trim() || !isDirty;

    return (
        <AppLayout>
            <Head title="Edit Service Environment" />
            <Toaster richColors theme="system" position="top-right" />
            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <h1 className="text-xl font-semibold">Edit Service Environment</h1>
                    <p className="mb-4 text-muted-foreground">Update service environment.</p>

                    <form onSubmit={handleSubmit} className="space-y-4">
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

                        <div className="flex items-center justify-between">
                            <Button asChild variant="ghost">
                                <Link href={serviceEnvironmentRoute.index().url}>Cancel</Link>
                            </Button>
                            <div className="flex gap-2">
                                <Button type="button" variant="outline" onClick={handleReset} disabled={!isDirty || processing}>
                                    Reset
                                </Button>
                                <Button type="submit" disabled={isDisabled}>
                                    {processing ? 'Saving...' : 'Save'}
                                </Button>
                            </div>
                        </div>

                        {wasSuccessful && <p className="text-sm text-green-600 dark:text-green-400">Saved successfully.</p>}
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
