import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useFlash } from '@/hooks/use-flash';
import AppLayout from '@/layouts/app-layout';
import configurationRoute from '@/routes/configurations/index';
import { Channel, ServiceEnvironment } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown } from 'lucide-react';
import React from 'react';
import { toast, Toaster } from 'sonner';

type PageProps = {
    serviceEnvironments: ServiceEnvironment[];
    channels: Channel[];
    flash?: { message?: string; error?: string; success?: string };
};

export default function CreateConfigurationPage({ serviceEnvironments, channels }: PageProps) {
    const { flash } = usePage<PageProps>().props;
    const { resetAll } = useFlash(flash);

    React.useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        } else if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<{
        service_environment_id: string | number | '';
        channel_id: string | number | '';
        source: any;
        destination: any;
        cron_expression: string;
    }>({
        service_environment_id: '',
        channel_id: '',
        source: {
            url: '',
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({}, null, 2),
            timeout: 10,
            retry_count: 2,
        },
        destination: {
            url: '',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            extract: {},
            foreach: '',
            body_template: JSON.stringify({}, null, 2),
            timeout: 20,
            retry_count: 2,
            range_per_request: 1,
        },
        cron_expression: '',
    });

    const [openSe, setOpenSe] = React.useState(false);
    const [openCh, setOpenCh] = React.useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const normalized = { ...data };
        let hasJsonError = false;

        try {
            const sb = normalized.source?.body;
            if (typeof sb === 'string') {
                try {
                    const parsed = JSON.parse(sb);
                    if (Array.isArray(parsed)) {
                        normalized.source.body = parsed.length > 0 ? parsed[0] : {};
                    } else if (parsed !== null && typeof parsed === 'object') {
                        normalized.source.body = parsed;
                    } else {
                        normalized.source.body = parsed;
                    }
                } catch {
                    normalized.source.body = sb;
                }
            } else if (sb !== null && typeof sb === 'object') {
                if (Array.isArray(sb)) {
                    normalized.source.body = sb.length > 0 ? sb[0] : {};
                } else {
                    normalized.source.body = sb;
                }
            } else {
                normalized.source.body = sb;
            }
        } catch {
            normalized.source.body = data.source.body;
        }

        try {
            const dbt = normalized.destination?.body_template;
            if (typeof dbt === 'string') {
                try {
                    const parsed = JSON.parse(dbt);
                    if (Array.isArray(parsed)) {
                        normalized.destination.body_template = parsed.length > 0 ? parsed[0] : {};
                    } else if (parsed !== null && typeof parsed === 'object') {
                        normalized.destination.body_template = parsed;
                    } else {
                        normalized.destination.body_template = JSON.stringify(parsed);
                    }
                } catch {
                    toast.error('Source Body must be a valid JSON object or array.');
                    hasJsonError = true;
                    return;
                }
            } else if (dbt !== null && typeof dbt === 'object') {
                if (Array.isArray(dbt)) {
                    normalized.destination.body_template = dbt.length > 0 ? JSON.stringify(dbt[0]) : JSON.stringify({});
                } else {
                    normalized.destination.body_template = JSON.stringify(dbt);
                }
            }
        } catch {
            normalized.destination.body_template = data.destination.body_template;
        }

        setData('source', normalized.source);
        setData('destination', normalized.destination);

        post(configurationRoute.store().url, { preserveScroll: true, forceFormData: true });
    };

    const handleReset = () => {
        reset('service_environment_id', 'channel_id');
        clearErrors();
        resetAll();
    };

    const isDirty = data.service_environment_id !== '' || data.channel_id !== '';
    const isDisabled = processing || !String(data.service_environment_id).trim() || !String(data.channel_id).trim();

    const seLabel = (se: ServiceEnvironment): string => {
        const serviceName = se.service?.full_name ?? se.service?.name ?? `Service #${se.service_id}`;
        const envName = se.environment?.name ?? `Env #${se.environment_id}`;
        return `${serviceName} / ${envName}`;
    };

    const selectedSe = serviceEnvironments.find((se) => String(se.id) === String(data.service_environment_id));
    const selectedCh = channels.find((ch) => String(ch.id) === String(data.channel_id));

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

                    <p className="mb-4 text-muted-foreground">Fill in this detail below.</p>

                    <form id="create-configuration-form" onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="mb-1 block font-medium">Service / Environment</label>
                            <Popover open={openSe} onOpenChange={setOpenSe}>
                                <PopoverTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={openSe}
                                        className={`w-full justify-between ${!selectedSe ? 'text-muted-foreground' : ''} ${errors.service_environment_id ? 'border-destructive focus-visible:ring-destructive' : ''}`}
                                        disabled={processing}
                                    >
                                        {selectedSe ? seLabel(selectedSe) : 'Select service / environment'}
                                        <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-[--radix-popover-trigger-width] p-0">
                                    <Command>
                                        <CommandInput placeholder="Search service / environment..." />
                                        <CommandList>
                                            <CommandEmpty>No results found.</CommandEmpty>
                                            <CommandGroup>
                                                {serviceEnvironments.map((se) => {
                                                    const id = String(se.id);
                                                    const label = seLabel(se);
                                                    const isSelected = String(data.service_environment_id) === id;
                                                    return (
                                                        <CommandItem
                                                            key={id}
                                                            value={label}
                                                            onSelect={() => {
                                                                setData('service_environment_id', id);
                                                                setOpenSe(false);
                                                            }}
                                                        >
                                                            <Check className={`mr-2 h-4 w-4 ${isSelected ? 'opacity-100' : 'opacity-0'}`} />
                                                            {label}
                                                        </CommandItem>
                                                    );
                                                })}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            {errors.service_environment_id && <p className="mt-1 text-sm text-destructive">{errors.service_environment_id}</p>}
                        </div>

                        <div>
                            <label className="mb-1 block font-medium">Channel</label>
                            <Popover open={openCh} onOpenChange={setOpenCh}>
                                <PopoverTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={openCh}
                                        className={`w-full justify-between ${!selectedCh ? 'text-muted-foreground' : ''} ${errors.channel_id ? 'border-destructive focus-visible:ring-destructive' : ''}`}
                                        disabled={processing}
                                    >
                                        {selectedCh ? selectedCh.name : 'Select channel'}
                                        <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-[--radix-popover-trigger-width] p-0">
                                    <Command>
                                        <CommandInput placeholder="Search channel..." />
                                        <CommandList>
                                            <CommandEmpty>No results found.</CommandEmpty>
                                            <CommandGroup>
                                                {channels.map((ch) => {
                                                    const id = String(ch.id);
                                                    const isSelected = String(data.channel_id) === id;
                                                    return (
                                                        <CommandItem
                                                            key={id}
                                                            value={ch.name}
                                                            onSelect={() => {
                                                                setData('channel_id', id);
                                                                setOpenCh(false);
                                                            }}
                                                        >
                                                            <Check className={`mr-2 h-4 w-4 ${isSelected ? 'opacity-100' : 'opacity-0'}`} />
                                                            {ch.name}
                                                        </CommandItem>
                                                    );
                                                })}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            {errors.channel_id && <p className="mt-1 text-sm text-destructive">{errors.channel_id}</p>}
                        </div>

                        <div className="border-t pt-2">
                            <h2 className="mb-2 text-sm font-medium">Elastic</h2>

                            <label className="mb-1 block text-xs">URL</label>
                            <input
                                type="text"
                                value={data.source.url}
                                onChange={(e) => setData('source.url', e.target.value)}
                                className="w-full rounded border p-2"
                                placeholder="https://elastic.example.com/_search"
                            />
                            {errors['source.url'] && <p className="text-sm text-destructive">{errors['source.url']}</p>}

                            <label className="mt-2 mb-1 block text-xs">Method</label>
                            <input
                                type="text"
                                value={data.source.method}
                                onChange={(e) => setData('source.method', e.target.value)}
                                className="w-full rounded border p-2"
                                placeholder="GET"
                            />

                            <label className="mt-2 mb-1 block text-xs">Headers (JSON)</label>
                            <textarea
                                rows={3}
                                value={
                                    typeof data.source.headers === 'string' ? data.source.headers : JSON.stringify(data.source.headers || {}, null, 2)
                                }
                                onChange={(e) => {
                                    try {
                                        const parsed = JSON.parse(e.target.value || '{}');
                                        setData('source.headers', parsed);
                                    } catch {
                                        setData('source.headers', e.target.value);
                                    }
                                }}
                                className="w-full rounded border p-2 font-mono text-xs"
                                placeholder='{"Content-Type":"application/json"}'
                            />

                            <label className="mt-2 mb-1 block text-xs">Body (JSON)</label>
                            <textarea
                                rows={6}
                                value={typeof data.source.body === 'string' ? data.source.body : JSON.stringify(data.source.body || {}, null, 2)}
                                onChange={(e) => {
                                    const v = e.target.value;
                                    try {
                                        const parsed = JSON.parse(v);
                                        if (Array.isArray(parsed)) {
                                            setData('source.body', parsed.length > 0 ? parsed[0] : {});
                                        } else if (parsed !== null && typeof parsed === 'object') {
                                            setData('source.body', parsed);
                                        } else {
                                            setData('source.body', parsed);
                                        }
                                    } catch {
                                        setData('source.body', v);
                                    }
                                }}
                                className="w-full rounded border p-2 font-mono text-xs"
                                placeholder='{"message":"Hello World!"}'
                            />
                            {errors['source.body'] && <p className="mt-1 text-sm text-destructive">{errors['source.body']}</p>}

                            <div className="mt-2 flex gap-2">
                                <div className="flex-1">
                                    <label className="mb-1 block text-xs">Timeout (s)</label>
                                    <input
                                        type="number"
                                        value={data.source.timeout}
                                        onChange={(e) => setData('source.timeout', Number(e.target.value))}
                                        className="w-full rounded border p-2"
                                    />
                                </div>
                                <div className="flex-1">
                                    <label className="mb-1 block text-xs">Retry Count</label>
                                    <input
                                        type="number"
                                        value={data.source.retry_count}
                                        onChange={(e) => setData('source.retry_count', Number(e.target.value))}
                                        className="w-full rounded border p-2"
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="border-t pt-2">
                            <h2 className="mb-2 text-sm font-medium">Channel Configuration</h2>

                            <label className="mb-1 block text-xs">URL</label>
                            <input
                                type="text"
                                value={data.destination.url}
                                onChange={(e) => setData('destination.url', e.target.value)}
                                className="w-full rounded border p-2"
                                placeholder="https://telegram.com/api/..."
                            />

                            <label className="mt-2 mb-1 block text-xs">Method</label>
                            <input
                                type="text"
                                value={data.destination.method}
                                onChange={(e) => setData('destination.method', e.target.value)}
                                className="w-full rounded border p-2"
                                placeholder="POST"
                            />

                            <label className="mt-2 mb-1 block text-xs">Headers (JSON)</label>
                            <textarea
                                rows={3}
                                value={
                                    typeof data.destination.headers === 'string'
                                        ? data.destination.headers
                                        : JSON.stringify(data.destination.headers || {}, null, 2)
                                }
                                onChange={(e) => {
                                    try {
                                        const parsed = JSON.parse(e.target.value || '{}');
                                        setData('destination.headers', parsed);
                                    } catch {
                                        setData('destination.headers', e.target.value);
                                    }
                                }}
                                className="w-full rounded border p-2 font-mono text-xs"
                                placeholder='{"Content-Type":"application/json"}'
                            />

                            <label className="mt-2 mb-1 block text-xs">Extract mapping</label>
                            <textarea
                                rows={3}
                                value={
                                    typeof data.destination.extract === 'string'
                                        ? data.destination.extract
                                        : JSON.stringify(data.destination.extract || {}, null, 2)
                                }
                                onChange={(e) => {
                                    try {
                                        const parsed = JSON.parse(e.target.value || '{}');
                                        setData('destination.extract', parsed);
                                    } catch {
                                        setData('destination.extract', e.target.value);
                                    }
                                }}
                                className="w-full rounded border p-2 font-mono text-xs"
                                placeholder='{"id":"@._source.alert_id","name":"@._source.rule_name"}'
                            />

                            <label className="mt-2 mb-1 block text-xs">Foreach</label>
                            <input
                                type="text"
                                value={data.destination.foreach}
                                onChange={(e) => setData('destination.foreach', e.target.value)}
                                className="w-full rounded border p-2"
                                placeholder="$.hits.hits[*]"
                            />

                            <label className="mt-2 mb-1 block text-xs">Body Template (JSON)</label>
                            <textarea
                                rows={6}
                                value={
                                    typeof data.destination.body_template === 'string'
                                        ? data.destination.body_template
                                        : JSON.stringify(data.destination.body_template || {}, null, 2)
                                }
                                onChange={(e) => {
                                    const v = e.target.value;
                                    try {
                                        const parsed = JSON.parse(v);
                                        if (Array.isArray(parsed)) {
                                            setData('destination.body_template', parsed.length > 0 ? parsed[0] : {});
                                        } else if (parsed !== null && typeof parsed === 'object') {
                                            setData('destination.body_template', parsed);
                                        } else {
                                            setData('destination.body_template', parsed);
                                        }
                                    } catch {
                                        setData('destination.body_template', v);
                                    }
                                }}
                                className="w-full rounded border p-2 font-mono text-xs"
                                placeholder='{"content":"Elastic Alert {{{id}}} with title {{{name}}}"}'
                            />
                            {errors['destination.body_template'] && (
                                <p className="mt-1 text-sm text-destructive">{errors['destination.body_template']}</p>
                            )}

                            <div className="mt-2 flex gap-2">
                                <div className="flex-1">
                                    <label className="mb-1 block text-xs">Timeout (s)</label>
                                    <input
                                        type="number"
                                        value={data.destination.timeout}
                                        onChange={(e) => setData('destination.timeout', Number(e.target.value))}
                                        className="w-full rounded border p-2"
                                    />
                                </div>

                                <div className="flex-1">
                                    <label className="mb-1 block text-xs">Retry Count</label>
                                    <input
                                        type="number"
                                        value={data.destination.retry_count}
                                        onChange={(e) => setData('destination.retry_count', Number(e.target.value))}
                                        className="w-full rounded border p-2"
                                    />
                                </div>

                                <div className="flex-1">
                                    <label className="mb-1 block text-xs">Range Per Request</label>
                                    <input
                                        type="number"
                                        value={data.destination.range_per_request}
                                        onChange={(e) => setData('destination.range_per_request', Number(e.target.value))}
                                        className="w-full rounded border p-2"
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label className="mb-1 block font-medium">Cron Expression</label>
                            <input
                                type="text"
                                value={data.cron_expression}
                                onChange={(e) => setData('cron_expression', e.target.value)}
                                className="w-full rounded border p-2"
                                placeholder="*/30 * * * *"
                            />
                            {errors.cron_expression && <p className="mt-1 text-sm text-destructive">{errors.cron_expression}</p>}
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
