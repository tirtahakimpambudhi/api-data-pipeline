import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import configurationRoute from '@/routes/configurations/index';
import { Channel, ServiceEnvironment } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React from 'react';
import { useFlash } from '@/hooks/use-flash';
import { Toaster } from 'sonner';

type PageProps = {
    serviceEnvironments: ServiceEnvironment[];
    channels: Channel[];
};

type FlashProps = { flash?: { message?: string; error?: string } };

export default function CreateConfigurationPage({ serviceEnvironments, channels }: PageProps) {
    const { flash } = usePage<FlashProps>().props;
    const {resetAll} = useFlash(flash);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<{
        service_environment_id: string | number | '';
        channel_id: string | number | '';
    }>({
        service_environment_id: '',
        channel_id: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(configurationRoute.store().url, {
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        reset('service_environment_id', 'channel_id');
        clearErrors();
        resetAll()
    };

    const isDirty = data.service_environment_id !== '' || data.channel_id !== '';
    const isDisabled = processing || !String(data.service_environment_id).trim() || !String(data.channel_id).trim();

    const seLabel = (se: ServiceEnvironment) => {
        const serviceName =
            se.service?.name ?? (se as any).service?.name ?? (se as any).service_name ?? (se.service_id ? `Service #${se.service_id}` : '–');
        const envName =
            se.environment?.name ??
            (se as any).environment?.name ??
            (se as any).environment_name ??
            (se.environment_id ? `Env #${se.environment_id}` : '–');
        return `${serviceName} / ${envName}`;
    };

    return (
        <AppLayout>
            <Head title="Create Configuration" />
            <Toaster richColors position="top-right" />
            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <div className="mb-4 flex items-center justify-between">
                        <h1 className="text-xl font-semibold">Create Configuration</h1>

                    </div>

                    {flash?.error && (
                        <div className="mb-4 rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">
                            {flash.error}
                        </div>
                    )}

                    <p className="mb-4 text-muted-foreground">
                        Fill in this detail below.
                    </p>

                    <form id="create-configuration-form" onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="mb-1 block font-medium">Service / Environment</label>
                            <Select
                                value={String(data.service_environment_id)}
                                onValueChange={(val) => setData('service_environment_id', val)}
                                disabled={processing}
                            >
                                <SelectTrigger className={errors.service_environment_id ? 'border-destructive' : ''}>
                                    <SelectValue placeholder="Select service / environment" />
                                </SelectTrigger>
                                <SelectContent>
                                    {serviceEnvironments.map((se) => (
                                        <SelectItem key={se.id} value={String(se.id)}>
                                            {se.name}
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
                                    <SelectValue placeholder="Select channel" />
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
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
