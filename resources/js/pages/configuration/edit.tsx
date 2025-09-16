import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import configurationRoute from '@/routes/configurations/index';
import { Channel, Configuration, ServiceEnvironment } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { useRef } from 'react';
import { useFlash } from '@/hooks/use-flash';
import { Toaster } from 'sonner';

type PageProps = {
    configuration: Configuration;
    serviceEnvironments: ServiceEnvironment[];
    channels: Channel[];
    flash?: { message?: string; error?: string, success?: string };
};

export default function ConfigurationEditPage({ configuration, serviceEnvironments, channels }: PageProps) {
    const { props } = usePage<PageProps>();
    const {resetAll} = useFlash(props?.flash);
    const initial = useRef({
        service_environment_id: (configuration as any).service_environment?.id ?? (configuration as any).service_environment_id ?? '',
        channel_id: (configuration as any).channel?.id ?? (configuration as any).channel_id ?? '',
    });

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const { data, setData, put, processing, errors, wasSuccessful, reset, clearErrors } = useForm<{
        service_environment_id: string | number | '';
        channel_id: string | number | '';
    }>({
        service_environment_id: initial.current.service_environment_id,
        channel_id: initial.current.channel_id,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(configurationRoute.update({ configuration: (configuration as any).id }).url, {
            preserveScroll: true,
            onSuccess: () => {},
        });
    };

    const handleReset = () => {
        setData('service_environment_id', initial.current.service_environment_id);
        setData('channel_id', initial.current.channel_id);
        clearErrors();
        resetAll()
    };

    const isDirty =
        String(data.service_environment_id ?? '') !== String(initial.current.service_environment_id ?? '') ||
        String(data.channel_id ?? '') !== String(initial.current.channel_id ?? '');

    const isDisabled = processing || !String(data.service_environment_id).trim() || !String(data.channel_id).trim() || !isDirty;

    const seLabel = (se: ServiceEnvironment) => {
        const serviceName =
            se.service?.name ??
            (se as any).service?.name ??
            (se as any).service_name ??
            (se as any).serviceId ??
            (se.service_id ? `Service #${se.service_id}` : '–');
        const envName =
            se.environment?.name ??
            (se as any).environment?.name ??
            (se as any).environment_name ??
            (se as any).environmentId ??
            (se.environment_id ? `Env #${se.environment_id}` : '–');
        return `${serviceName} / ${envName}`;
    };

    return (
        <AppLayout>
            <Head title="Edit Configuration" />
            <Toaster richColors theme="system" position="top-right" />
            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <div className="mb-4 flex items-center justify-between">
                        <h1 className="text-xl font-semibold">Edit Configuration</h1>
                    </div>

                    <p className="mb-4 text-muted-foreground">
                        Update this configuration's service environment or channel as needed.
                    </p>

                    <form id="edit-configuration-form" onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="mb-1 block font-medium">Service / Environment</label>
                            <Select
                                value={String(data.service_environment_id)}
                                onValueChange={(val) => setData('service_environment_id', val)}
                                disabled={processing}
                            >
                                <SelectTrigger className={errors.service_environment_id ? 'border-destructive' : ''}>
                                    <SelectValue placeholder="Pilih service / environment" />
                                </SelectTrigger>
                                <SelectContent>
                                    {serviceEnvironments.map((se) => (
                                        <SelectItem key={se.id} value={String(se.id)}>
                                            {seLabel(se)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.service_environment_id && <p className="mt-1 text-sm text-destructive">{errors.service_environment_id}</p>}
                        </div>

                        <div>
                            <label className="mb-1 block font-medium">Channel</label>
                            <Select value={String(data.channel_id)} onValueChange={(val) => setData('channel_id', val)} disabled={processing}>
                                <SelectTrigger className={errors.channel_id ? 'border-destructive' : ''}>
                                    <SelectValue placeholder="Pilih channel" />
                                </SelectTrigger>
                                <SelectContent>
                                    {channels.map((ch) => (
                                        <SelectItem key={ch.id} value={String(ch.id)}>
                                            {ch.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.channel_id && <p className="mt-1 text-sm text-destructive">{errors.channel_id}</p>}
                        </div>

                        <div className="flex items-center justify-between">
                            <Button asChild variant="ghost">
                                <Link href={configurationRoute.index.url()}>Cancel</Link>
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
