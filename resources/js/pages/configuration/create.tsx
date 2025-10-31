import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useFlash } from '@/hooks/use-flash';
import AppLayout from '@/layouts/app-layout';
import configurationRoute from '@/routes/configurations/index';
import { Channel, ServiceEnvironment } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import JSON5 from 'json5';
import { AlertCircle, Check, CheckCircle2, ChevronsUpDown, Plus, Trash2 } from 'lucide-react';
import React from 'react';
import { toast, Toaster } from 'sonner';

type PageProps = {
    serviceEnvironments: ServiceEnvironment[];
    channels: Channel[];
    flash?: { message?: string; error?: string; success?: string };
};

type HeaderEntry = { key: string; value: string };

const CRON_PRESETS = [
    { label: 'Every 5 minutes', value: '*/5 * * * *' },
    { label: 'Every 15 minutes', value: '*/15 * * * *' },
    { label: 'Every 30 minutes', value: '*/30 * * * *' },
    { label: 'Every 1 hour', value: '0 * * * *' },
    { label: 'Every 5 hours', value: '0 */5 * * *' },
    { label: 'Custom', value: 'custom' },
];

const HTTP_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

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
        source: {
            url: string;
            method: string;
            headers: Record<string, string>;
            body: Record<string, any>;
            timeout: number;
            retry_count: number;
        };
        destination: {
            url: string;
            method: string;
            headers: Record<string, string>;
            extract: Record<string, string>;
            foreach: string;
            body_template: string;
            timeout: number;
            retry_count: number;
            range_per_request: number;
        };
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
            body: {},
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
            body_template: JSON.stringify({}),
            timeout: 20,
            retry_count: 2,
            range_per_request: 1,
        },
        cron_expression: '*/30 * * * *',
    });

    const [openSe, setOpenSe] = React.useState(false);
    const [openCh, setOpenCh] = React.useState(false);

    // Editable headers (source & destination) as array<key,value>
    const [sourceHeaders, setSourceHeaders] = React.useState<HeaderEntry[]>([
        { key: 'Content-Type', value: 'application/json' },
        { key: 'Accept', value: 'application/json' },
    ]);
    const [destHeaders, setDestHeaders] = React.useState<HeaderEntry[]>([
        { key: 'Content-Type', value: 'application/json' },
        { key: 'Accept', value: 'application/json' },
    ]);

    // Extract mappings (variable -> jsonpath)
    const [extractMappings, setExtractMappings] = React.useState<HeaderEntry[]>([{ key: '', value: '' }]);

    // Body (JSON) editors as string
    const [sourceBodyStr, setSourceBodyStr] = React.useState('{}');
    const [destBodyStr, setDestBodyStr] = React.useState('{}');

    // Validation state for JSON body editors
    const [sourceBodyValid, setSourceBodyValid] = React.useState<boolean | null>(true);
    const [destBodyValid, setDestBodyValid] = React.useState<boolean | null>(true);

    // Foreach checkbox
    const [useForeach, setUseForeach] = React.useState(false);

    // Cron preset/custom handling
    const [cronPreset, setCronPreset] = React.useState('*/30 * * * *');
    const [customCron, setCustomCron] = React.useState('');

    // Sync sourceHeaders -> data.source.headers
    React.useEffect(() => {
        const headersObj = sourceHeaders.reduce(
            (acc, h) => {
                if (h.key.trim()) acc[h.key] = h.value;
                return acc;
            },
            {} as Record<string, string>,
        );
        setData('source.headers', headersObj);
    }, [sourceHeaders]);

    // Sync destHeaders -> data.destination.headers
    React.useEffect(() => {
        const headersObj = destHeaders.reduce(
            (acc, h) => {
                if (h.key.trim()) acc[h.key] = h.value;
                return acc;
            },
            {} as Record<string, string>,
        );
        setData('destination.headers', headersObj);
    }, [destHeaders]);

    // Sync extractMappings -> data.destination.extract
    React.useEffect(() => {
        const extractObj = extractMappings.reduce(
            (acc, m) => {
                if (m.key.trim()) acc[m.key] = m.value;
                return acc;
            },
            {} as Record<string, string>,
        );
        setData('destination.extract', extractObj);
    }, [extractMappings]);

    // Validate + sync source body
    const validateAndSetSourceBody = (str: string) => {
        setSourceBodyStr(str);
        try {
            const parsed = JSON5.parse(str);
            setSourceBodyValid(true);
            setData('source.body', parsed);
        } catch {
            setSourceBodyValid(str.trim() === '' ? null : false);
        }
    };

    // Validate + sync destination body template
    const validateAndSetDestBody = (str: string) => {
        setDestBodyStr(str);
        try {
            const parsed = JSON5.parse(str);
            setDestBodyValid(true);
            setData('destination.body_template', parsed);
        } catch {
            setDestBodyValid(str.trim() === '' ? null : false);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Frontend validation before submit
        if (sourceBodyValid === false) {
            toast.error('Source Body must be a valid JSON object.');
            return;
        }
        if (destBodyValid === false) {
            toast.error('Destination Body Template must be a valid JSON object.');
            return;
        }

        // Normalize payload that we send
        const normalized = { ...data };

        // Ensure source.body is valid JSON or {}
        try {

            if (sourceBodyStr == '{}' || sourceBodyStr == '') {
                normalized.source.body = null;
            } else {
                normalized.source.body = JSON5.parse(sourceBodyStr);
            }
        } catch {
            normalized.source.body = null;
        }

        // Ensure destination.body_template is valid JSON or {}
        try {
            normalized.destination.body_template = JSON.stringify(JSON5.parse(destBodyStr));
        } catch {
            normalized.destination.body_template = JSON.stringify({});
        }

        // If foreach is disabled, ensure it's blank
        if (!useForeach) {
            normalized.destination.foreach = '';
        }

        post(configurationRoute.store().url, {
            data: normalized,
            preserveScroll: true,
            forceFormData: true,
        });
    };

    const handleReset = () => {
        reset('service_environment_id', 'channel_id');

        setSourceHeaders([
            { key: 'Content-Type', value: 'application/json' },
            { key: 'Accept', value: 'application/json' },
        ]);
        setDestHeaders([
            { key: 'Content-Type', value: 'application/json' },
            { key: 'Accept', value: 'application/json' },
        ]);
        setExtractMappings([{ key: '', value: '' }]);

        setSourceBodyStr('{}');
        setDestBodyStr('{}');
        setSourceBodyValid(true);
        setDestBodyValid(true);

        setUseForeach(false);

        setCronPreset('*/30 * * * *');
        setCustomCron('');

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

    // Helpers for array-edit UI pieces
    const addSourceHeader = () => {
        setSourceHeaders([...sourceHeaders, { key: '', value: '' }]);
    };

    const removeSourceHeader = (index: number) => {
        setSourceHeaders(sourceHeaders.filter((_, i) => i !== index));
    };

    const updateSourceHeader = (index: number, field: 'key' | 'value', value: string) => {
        const updated = [...sourceHeaders];
        updated[index][field] = value;
        setSourceHeaders(updated);
    };

    const addDestHeader = () => {
        setDestHeaders([...destHeaders, { key: '', value: '' }]);
    };

    const removeDestHeader = (index: number) => {
        setDestHeaders(destHeaders.filter((_, i) => i !== index));
    };

    const updateDestHeader = (index: number, field: 'key' | 'value', value: string) => {
        const updated = [...destHeaders];
        updated[index][field] = value;
        setDestHeaders(updated);
    };

    const addExtractMapping = () => {
        setExtractMappings([...extractMappings, { key: '', value: '' }]);
    };

    const removeExtractMapping = (index: number) => {
        setExtractMappings(extractMappings.filter((_, i) => i !== index));
    };

    const updateExtractMapping = (index: number, field: 'key' | 'value', value: string) => {
        const updated = [...extractMappings];
        updated[index][field] = value;
        setExtractMappings(updated);
    };

    const handleCronChange = (value: string) => {
        setCronPreset(value);
        if (value !== 'custom') {
            setData('cron_expression', value);
        } else {
            setData('cron_expression', customCron);
        }
    };

    const handleCustomCronChange = (value: string) => {
        setCustomCron(value);
        if (cronPreset === 'custom') {
            setData('cron_expression', value);
        }
    };

    return (
        <AppLayout>
            <Head title="Create Configuration" />
            <Toaster richColors position="top-right" />

            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-2xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <div className="mb-4 flex items-center justify-between">
                        <h1 className="text-xl font-semibold">Create Configuration</h1>
                    </div>

                    {flash?.error && (
                        <div className="mb-4 rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">
                            {flash.error}
                        </div>
                    )}

                    <p className="mb-4 text-sm text-muted-foreground">Fill in the details below to create a new configuration.</p>

                    <form id="create-configuration-form" onSubmit={handleSubmit} className="space-y-6">
                        {/* Service Environment */}
                        <div>
                            <label className="mb-1.5 block text-sm font-medium">Service / Environment</label>
                            <Popover open={openSe} onOpenChange={setOpenSe}>
                                <PopoverTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={openSe}
                                        className={`w-full justify-between ${!selectedSe ? 'text-muted-foreground' : ''} ${
                                            errors.service_environment_id ? 'border-destructive focus-visible:ring-destructive' : ''
                                        }`}
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

                        {/* Channel */}
                        <div>
                            <label className="mb-1.5 block text-sm font-medium">Channel</label>
                            <Popover open={openCh} onOpenChange={setOpenCh}>
                                <PopoverTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={openCh}
                                        className={`w-full justify-between ${!selectedCh ? 'text-muted-foreground' : ''} ${
                                            errors.channel_id ? 'border-destructive focus-visible:ring-destructive' : ''
                                        }`}
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

                        {/* Source Section */}
                        <div className="space-y-4 rounded-lg border p-4">
                            <h2 className="text-base font-semibold">Source (Elastic)</h2>
                            {errors['source'] && <p className="mt-1 text-sm text-destructive">{errors['source']}</p>}

                            <div>
                                <label className="mb-1.5 block text-sm font-medium">URL</label>
                                <input
                                    type="text"
                                    value={data.source.url}
                                    onChange={(e) => setData('source.url', e.target.value)}
                                    className="w-full rounded-md border px-3 py-2 text-sm"
                                    placeholder="https://elastic.example.com/_search"
                                />
                                {errors['source.url'] && <p className="mt-1 text-sm text-destructive">{errors['source.url']}</p>}
                            </div>

                            <div>
                                <label className="mb-1.5 block text-sm font-medium">Method</label>
                                <Select value={data.source.method} onValueChange={(v) => setData('source.method', v)}>
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {HTTP_METHODS.map((method) => (
                                            <SelectItem key={method} value={method}>
                                                {method}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <div className="mb-1.5 flex items-center justify-between">
                                    <label className="text-sm font-medium">Headers</label>
                                    <Button type="button" size="sm" variant="outline" onClick={addSourceHeader}>
                                        <Plus className="mr-1 h-3.5 w-3.5" />
                                        Add Header
                                    </Button>
                                </div>

                                <div className="space-y-2">
                                    {sourceHeaders.map((header, index) => (
                                        <div key={index} className="flex gap-2">
                                            <input
                                                type="text"
                                                value={header.key}
                                                onChange={(e) => updateSourceHeader(index, 'key', e.target.value)}
                                                className="flex-1 rounded-md border px-3 py-1.5 font-mono text-sm"
                                                placeholder="Key"
                                            />
                                            <input
                                                type="text"
                                                value={header.value}
                                                onChange={(e) => updateSourceHeader(index, 'value', e.target.value)}
                                                className="flex-1 rounded-md border px-3 py-1.5 font-mono text-sm"
                                                placeholder="Value"
                                            />
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => removeSourceHeader(index)}
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <div className="mb-1.5 flex items-center justify-between">
                                    <label className="text-sm font-medium">Body (JSON)</label>
                                    {sourceBodyValid === true && sourceBodyStr.trim() !== '' && (
                                        <span className="flex items-center text-xs text-green-600">
                                            <CheckCircle2 className="mr-1 h-3.5 w-3.5" />
                                            Valid JSON
                                        </span>
                                    )}
                                    {sourceBodyValid === false && (
                                        <span className="flex items-center text-xs text-destructive">
                                            <AlertCircle className="mr-1 h-3.5 w-3.5" />
                                            Invalid JSON
                                        </span>
                                    )}
                                </div>

                                <textarea
                                    rows={6}
                                    value={sourceBodyStr}
                                    onChange={(e) => validateAndSetSourceBody(e.target.value)}
                                    className={`w-full rounded-md border px-3 py-2 font-mono text-xs ${
                                        sourceBodyValid === false ? 'border-destructive focus:ring-destructive' : ''
                                    }`}
                                    placeholder='{"query": {"match_all": {}}}'
                                />

                                {errors['source.body'] && <p className="mt-1 text-sm text-destructive">{errors['source.body']}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="mb-1.5 block text-sm font-medium">Timeout (seconds)</label>
                                    <input
                                        type="number"
                                        value={data.source.timeout}
                                        onChange={(e) => setData('source.timeout', Number(e.target.value))}
                                        className="w-full rounded-md border px-3 py-2 text-sm"
                                        min="1"
                                    />
                                </div>

                                <div>
                                    <label className="mb-1.5 block text-sm font-medium">Retry Count</label>
                                    <input
                                        type="number"
                                        value={data.source.retry_count}
                                        onChange={(e) => setData('source.retry_count', Number(e.target.value))}
                                        className="w-full rounded-md border px-3 py-2 text-sm"
                                        min="0"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Destination Section */}
                        <div className="space-y-4 rounded-lg border p-4">
                            <h2 className="text-base font-semibold">Destination (Channel)</h2>
                            {errors['destination'] && <p className="mt-1 text-sm text-destructive">{errors['destination']}</p>}

                            <div>
                                <label className="mb-1.5 block text-sm font-medium">URL</label>
                                <input
                                    type="text"
                                    value={data.destination.url}
                                    onChange={(e) => setData('destination.url', e.target.value)}
                                    className="w-full rounded-md border px-3 py-2 text-sm"
                                    placeholder="https://api.telegram.org/bot.../sendMessage"
                                />
                                {errors['destination.url'] && <p className="mt-1 text-sm text-destructive">{errors['destination.url']}</p>}
                            </div>

                            <div>
                                <label className="mb-1.5 block text-sm font-medium">Method</label>
                                <Select value={data.destination.method} onValueChange={(v) => setData('destination.method', v)}>
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {HTTP_METHODS.map((method) => (
                                            <SelectItem key={method} value={method}>
                                                {method}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <div className="mb-1.5 flex items-center justify-between">
                                    <label className="text-sm font-medium">Headers</label>
                                    <Button type="button" size="sm" variant="outline" onClick={addDestHeader}>
                                        <Plus className="mr-1 h-3.5 w-3.5" />
                                        Add Header
                                    </Button>
                                </div>

                                <div className="space-y-2">
                                    {destHeaders.map((header, index) => (
                                        <div key={index} className="flex gap-2">
                                            <input
                                                type="text"
                                                value={header.key}
                                                onChange={(e) => updateDestHeader(index, 'key', e.target.value)}
                                                className="flex-1 rounded-md border px-3 py-1.5 font-mono text-sm"
                                                placeholder="Key"
                                            />
                                            <input
                                                type="text"
                                                value={header.value}
                                                onChange={(e) => updateDestHeader(index, 'value', e.target.value)}
                                                className="flex-1 rounded-md border px-3 py-1.5 font-mono text-sm"
                                                placeholder="Value"
                                            />
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => removeDestHeader(index)}
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <div className="mb-1.5 flex items-center justify-between">
                                    <label className="text-sm font-medium">Extract Mapping</label>
                                    <Button type="button" size="sm" variant="outline" onClick={addExtractMapping}>
                                        <Plus className="mr-1 h-3.5 w-3.5" />
                                        Add Mapping
                                    </Button>
                                </div>

                                <p className="mb-2 text-xs text-muted-foreground">
                                    Map fields from source response to template variables (e.g. <code>id → @._source.alert_id</code>)
                                </p>

                                <div className="space-y-2">
                                    {extractMappings.map((mapping, index) => (
                                        <div key={index} className="flex gap-2">
                                            <input
                                                type="text"
                                                value={mapping.key}
                                                onChange={(e) => updateExtractMapping(index, 'key', e.target.value)}
                                                className="flex-1 rounded-md border px-3 py-1.5 font-mono text-sm"
                                                placeholder="Variable name (e.g. id)"
                                            />
                                            <input
                                                type="text"
                                                value={mapping.value}
                                                onChange={(e) => updateExtractMapping(index, 'value', e.target.value)}
                                                className="flex-1 rounded-md border px-3 py-1.5 font-mono text-sm"
                                                placeholder="JSONPath (e.g. @._source.alert_id)"
                                            />
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => removeExtractMapping(index)}
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center space-x-2">
                                    <Checkbox id="use-foreach" checked={useForeach} onCheckedChange={(checked) => setUseForeach(checked === true)} />
                                    <label
                                        htmlFor="use-foreach"
                                        className="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                    >
                                        Send multiple requests (foreach)
                                    </label>
                                </div>

                                <p className="ml-6 text-xs text-muted-foreground">
                                    Enable this to iterate over an array in the source response and send separate requests per item.
                                </p>

                                {useForeach && (
                                    <div className="mt-2 ml-6">
                                        <label className="mb-1.5 block text-sm font-medium">Foreach Path (JSONPath)</label>
                                        <input
                                            type="text"
                                            value={data.destination.foreach}
                                            onChange={(e) => setData('destination.foreach', e.target.value)}
                                            className="w-full rounded-md border px-3 py-2 font-mono text-sm"
                                            placeholder="$.hits.hits[*]"
                                        />

                                        <p className="mt-1 text-xs text-muted-foreground">
                                            JSONPath expression to iterate over (e.g. <code>$.hits.hits[*]</code>)
                                        </p>
                                    </div>
                                )}
                            </div>

                            <div>
                                <div className="mb-1.5 flex items-center justify-between">
                                    <label className="text-sm font-medium">Body Template (JSON)</label>
                                    {destBodyValid === true && destBodyStr.trim() !== '' && (
                                        <span className="flex items-center text-xs text-green-600">
                                            <CheckCircle2 className="mr-1 h-3.5 w-3.5" />
                                            Valid JSON
                                        </span>
                                    )}
                                    {destBodyValid === false && (
                                        <span className="flex items-center text-xs text-destructive">
                                            <AlertCircle className="mr-1 h-3.5 w-3.5" />
                                            Invalid JSON
                                        </span>
                                    )}
                                </div>

                                <textarea
                                    rows={6}
                                    value={destBodyStr}
                                    onChange={(e) => validateAndSetDestBody(e.target.value)}
                                    className={`w-full rounded-md border px-3 py-2 font-mono text-xs ${
                                        destBodyValid === false ? 'border-destructive focus:ring-destructive' : ''
                                    }`}
                                    placeholder='{"text": "Alert {{{id}}}: {{{name}}}"}'
                                />

                                <p className="mt-1 text-xs text-muted-foreground">
                                    Use Mustache-style placeholders with triple braces to inject values, e.g. <code>{'{{{id}}}'}</code>,{' '}
                                    <code>{'{{{name}}}'}</code>.
                                </p>

                                {errors['destination.body_template'] && (
                                    <p className="mt-1 text-sm text-destructive">{errors['destination.body_template']}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-3 gap-3">
                                <div>
                                    <label className="mb-1.5 block text-sm font-medium">Timeout (s)</label>
                                    <input
                                        type="number"
                                        value={data.destination.timeout}
                                        onChange={(e) => setData('destination.timeout', Number(e.target.value))}
                                        className="w-full rounded-md border px-3 py-2 text-sm"
                                        min="1"
                                    />
                                </div>

                                <div>
                                    <label className="mb-1.5 block text-sm font-medium">Retry Count</label>
                                    <input
                                        type="number"
                                        value={data.destination.retry_count}
                                        onChange={(e) => setData('destination.retry_count', Number(e.target.value))}
                                        className="w-full rounded-md border px-3 py-2 text-sm"
                                        min="0"
                                    />
                                </div>

                                <div>
                                    <label className="mb-1.5 block text-sm font-medium">Range Per Request (s)</label>
                                    <input
                                        type="number"
                                        value={data.destination.range_per_request}
                                        onChange={(e) => setData('destination.range_per_request', Number(e.target.value))}
                                        className="w-full rounded-md border px-3 py-2 text-sm"
                                        min="1"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Schedule */}
                        <div>
                            <label className="mb-1.5 block text-sm font-medium">Schedule (Cron Expression)</label>

                            <Select value={cronPreset} onValueChange={handleCronChange}>
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {CRON_PRESETS.map((preset) => (
                                        <SelectItem key={preset.value} value={preset.value}>
                                            {preset.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            {cronPreset === 'custom' && (
                                <div className="mt-2">
                                    <input
                                        type="text"
                                        value={customCron}
                                        onChange={(e) => handleCustomCronChange(e.target.value)}
                                        className="w-full rounded-md border px-3 py-2 font-mono text-sm"
                                        placeholder="0 */2 * * *"
                                    />
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Enter a custom cron expression (for example, <code>0 */2 * * *</code> for every 2 hours).
                                    </p>
                                </div>
                            )}

                            {errors.cron_expression && <p className="mt-1 text-sm text-destructive">{errors.cron_expression}</p>}
                        </div>

                        {/* Form Actions */}
                        <div className="flex items-center justify-between border-t pt-4">
                            <Button asChild variant="ghost">
                                <Link href={configurationRoute.index.url()}>Cancel</Link>
                            </Button>

                            <div className="flex gap-2">
                                <Button type="button" variant="outline" onClick={handleReset} disabled={!isDirty || processing}>
                                    Reset
                                </Button>

                                <Button type="submit" disabled={processing || isDisabled || sourceBodyValid === false || destBodyValid === false}>
                                    {processing ? 'Saving...' : 'Save Configuration'}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
