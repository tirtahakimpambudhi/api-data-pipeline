import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useFlash } from '@/hooks/use-flash';
import AppLayout from '@/layouts/app-layout';
import configurationRoute from '@/routes/configurations/index';
import { Channel, Configuration, ServiceEnvironment } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import JSON5 from 'json5';
import { AlertCircle, Check, CheckCircle2, ChevronsUpDown, Plus, Trash2 } from 'lucide-react';
import React, { useRef } from 'react';
import { toast, Toaster } from 'sonner';

type PageProps = {
    configuration: Configuration;
    serviceEnvironments: ServiceEnvironment[];
    channels: Channel[];
    flash?: { message?: string; error?: string; success?: string };
};

// util sama dengan versi Anda
function tryStringifyMaybe(value: any, fallback = '{}') {
    try {
        if (value === null || value === undefined) return fallback;
        if (typeof value === 'string') return value;
        return JSON.stringify(value, null, 2);
    } catch {
        return fallback;
    }
}

// presets cron agar konsisten dengan create
const CRON_PRESETS = [
    { label: 'Every 5 minutes', value: '*/5 * * * *' },
    { label: 'Every 15 minutes', value: '*/15 * * * *' },
    { label: 'Every 30 minutes', value: '*/30 * * * *' },
    { label: 'Every 1 hour', value: '0 * * * *' },
    { label: 'Every 5 hours', value: '0 */5 * * *' },
    { label: 'Custom', value: 'custom' },
];

const HTTP_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

type HeaderEntry = { key: string; value: string };

export default function ConfigurationEditPage({ configuration, serviceEnvironments, channels }: PageProps) {
    const { props } = usePage<PageProps>();
    const { resetAll } = useFlash(props?.flash);

    // Normalisasi kondisi awal agar stabil
    const initial = useRef({
        service_environment_id: configuration.service_environment?.id ?? configuration.service_environment_id ?? '',
        channel_id: configuration.channel?.id ?? configuration.channel_id ?? '',
        source: {
            url: configuration.source?.url ?? configuration?.source?.url ?? '',
            method: configuration.source?.method ?? configuration?.source?.method ?? 'GET',
            headers: tryStringifyMaybe(
                configuration.source?.headers ??
                    configuration.source?.headers ?? {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
            ),
            body: (() => {
                const b = configuration.source?.body ?? configuration.source?.body ?? null;
                if (b === null || b === undefined) return JSON.stringify({}, null, 2);
                if (typeof b === 'string') {
                    // kalau string sudah valid JSON, pakai apa adanya
                    try {
                        JSON.parse(b);
                        return b;
                    } catch {
                        return b;
                    }
                }
                return JSON.stringify(b, null, 2);
            })(),
            timeout: configuration.source?.timeout ?? configuration.source?.timeout ?? 10,
            retry_count: configuration.source?.retryCount ?? configuration.source?.retryCount ?? 2,
        },
        destination: {
            url: configuration.destination?.url ?? configuration?.destination?.url ?? '',
            method: configuration.destination?.method ?? configuration?.destination?.method ?? 'POST',
            headers: tryStringifyMaybe(
                configuration.destination?.headers ??
                    configuration.destination?.headers ?? {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
            ),
            extract: tryStringifyMaybe(configuration.destination?.extract ?? configuration.destination?.extract ?? {}),
            foreach: configuration.destination?.foreach ?? configuration.destination?.foreach ?? '',
            body_template: (() => {
                const bt = configuration.destination?.body_template ?? configuration.destination?.body_template ?? null;
                if (bt === null || bt === undefined) return JSON.stringify({}, null, 2);
                if (typeof bt === 'string') {
                    try {
                        JSON.parse(bt);
                        return bt;
                    } catch {
                        return bt;
                    }
                }
                return JSON.stringify(bt, null, 2);
            })(),
            timeout: configuration.destination?.timeout ?? configuration.destination?.timeout ?? 20,
            retry_count: configuration.destination?.retryCount ?? configuration.destination?.retryCount ?? 2,
            range_per_request: configuration.destination?.rangePerRequest ?? configuration.destination?.rangePerRequest ?? 1,
        },
        cron_expression: configuration.cron_expression ?? '',
    });

    // Hydrate useForm mirip create, tapi pakai data existing
    const { data, setData, put, processing, errors, wasSuccessful, clearErrors } = useForm<{
        service_environment_id: string | number | '';
        channel_id: string | number | '';
        source: {
            url: string;
            method: string;
            headers: Record<string, string> | string;
            body: any;
            timeout: number;
            retry_count: number;
        };
        destination: {
            url: string;
            method: string;
            headers: Record<string, string> | string;
            extract: Record<string, string> | string;
            foreach: string;
            body_template: any;
            timeout: number;
            retry_count: number;
            range_per_request: number;
        };
        cron_expression: string;
    }>({
        service_environment_id: initial.current.service_environment_id,
        channel_id: initial.current.channel_id,
        source: {
            url: initial.current.source.url,
            method: initial.current.source.method,
            headers: (() => {
                try {
                    return JSON.parse(initial.current.source.headers);
                } catch {
                    return initial.current.source.headers;
                }
            })(),
            body: (() => {
                const sb = initial.current.source.body;
                try {
                    const parsed = JSON.parse(sb);
                    if (Array.isArray(parsed)) {
                        return parsed.length > 0 ? parsed[0] : {};
                    }
                    if (parsed !== null && typeof parsed === 'object') {
                        return parsed;
                    }
                    return parsed;
                } catch {
                    return sb;
                }
            })(),
            timeout: initial.current.source.timeout,
            retry_count: initial.current.source.retry_count,
        },
        destination: {
            url: initial.current.destination.url,
            method: initial.current.destination.method,
            headers: (() => {
                try {
                    return JSON.parse(initial.current.destination.headers);
                } catch {
                    return initial.current.destination.headers;
                }
            })(),
            extract: (() => {
                try {
                    return JSON.parse(initial.current.destination.extract);
                } catch {
                    return initial.current.destination.extract;
                }
            })(),
            foreach: initial.current.destination.foreach,
            body_template: (() => {
                try {
                    const parsed = JSON.parse(initial.current.destination.body_template);
                    if (Array.isArray(parsed)) {
                        return parsed.length > 0 ? parsed[0] : {};
                    }
                    if (parsed !== null && typeof parsed === 'object') {
                        return parsed;
                    }
                    return parsed;
                } catch {
                    return initial.current.destination.body_template;
                }
            })(),
            timeout: initial.current.destination.timeout,
            retry_count: initial.current.destination.retry_count,
            range_per_request: (initial.current.destination.range_per_request / 1000),
        },
        cron_expression: initial.current.cron_expression,
    });

    // --- Local UI state mirip create form ---

    // Popover state
    const [openSe, setOpenSe] = React.useState(false);
    const [openCh, setOpenCh] = React.useState(false);

    // Headers as editable arrays
    const [sourceHeaders, setSourceHeaders] = React.useState<HeaderEntry[]>(() => {
        const raw = data.source.headers;
        if (typeof raw === 'string') {
            // can't parse -> put whole thing as single row?
            return [{ key: 'RAW', value: raw }];
        }
        // object -> expand to key/value rows
        return Object.entries(raw || {}).map(([k, v]) => ({
            key: k,
            value: String(v),
        }));
    });

    const [destHeaders, setDestHeaders] = React.useState<HeaderEntry[]>(() => {
        const raw = data.destination.headers;
        if (typeof raw === 'string') {
            return [{ key: 'RAW', value: raw }];
        }
        return Object.entries(raw || {}).map(([k, v]) => ({
            key: k,
            value: String(v),
        }));
    });

    // Extract mappings (destination.extract)
    const [extractMappings, setExtractMappings] = React.useState<HeaderEntry[]>(() => {
        const raw = data.destination.extract;
        if (typeof raw === 'string') {
            return [{ key: 'RAW', value: raw }];
        }
        const entries = Object.entries(raw || {});
        if (entries.length === 0) return [{ key: '', value: '' }];
        return entries.map(([k, v]) => ({
            key: k,
            value: String(v),
        }));
    });

    // JSON body editors
    const [sourceBodyStr, setSourceBodyStr] = React.useState<string>(() => {
        if (typeof data.source.body === 'string') {
            return data.source.body;
        }
        try {
            return JSON.stringify(data.source.body ?? {}, null, 2);
        } catch {
            return '{}';
        }
    });

    const [destBodyStr, setDestBodyStr] = React.useState<string>(() => {
        if (typeof data.destination.body_template === 'string') {
            return data.destination.body_template;
        }
        try {
            return JSON.stringify(data.destination.body_template ?? {}, null, 2);
        } catch {
            return '{}';
        }
    });

    // Validation flags (like create)
    const [sourceBodyValid, setSourceBodyValid] = React.useState<boolean | null>(true);
    const [destBodyValid, setDestBodyValid] = React.useState<boolean | null>(true);

    // foreach toggle
    const [useForeach, setUseForeach] = React.useState(!!(data.destination.foreach && data.destination.foreach.trim()));

    // Cron preset/custom state
    // If existing cron matches one of presets, pick that, else 'custom'
    const detectCronPreset = (cron: string): string => {
        const found = CRON_PRESETS.find((p) => p.value === cron);
        return found ? found.value : 'custom';
    };
    const [cronPreset, setCronPreset] = React.useState(detectCronPreset(data.cron_expression || ''));
    const [customCron, setCustomCron] = React.useState(() => {
        return cronPreset === 'custom' ? data.cron_expression || '' : '';
    });

    // --- Derived labels ---
    const seLabel = (se: ServiceEnvironment): string => {
        const serviceName = se.service?.full_name ?? se.service?.name ?? `Service #${se.service_id}`;
        const envName = se.environment?.name ?? `Env #${se.environment_id}`;
        return `${serviceName} / ${envName}`;
    };

    const selectedSe = serviceEnvironments.find((se) => String(se.id) === String(data.service_environment_id));
    const selectedCh = channels.find((ch) => String(ch.id) === String(data.channel_id));

    // --- Sync helpers ---

    // sync headers arrays back into form data
    React.useEffect(() => {
        const obj = sourceHeaders.reduce(
            (acc, h) => {
                if (h.key.trim()) acc[h.key] = h.value;
                return acc;
            },
            {} as Record<string, string>,
        );
        setData('source.headers', obj);
    }, [sourceHeaders]);

    React.useEffect(() => {
        const obj = destHeaders.reduce(
            (acc, h) => {
                if (h.key.trim()) acc[h.key] = h.value;
                return acc;
            },
            {} as Record<string, string>,
        );
        setData('destination.headers', obj);
    }, [destHeaders]);

    React.useEffect(() => {
        const obj = extractMappings.reduce(
            (acc, m) => {
                if (m.key.trim()) acc[m.key] = m.value;
                return acc;
            },
            {} as Record<string, string>,
        );
        setData('destination.extract', obj);
    }, [extractMappings]);

    // validate+sync source.body
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

    // validate+sync destination.body_template
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

    // cron change
    const handleCronChange = (value: string) => {
        setCronPreset(value);
        if (value !== 'custom') {
            setData('cron_expression', value);
        } else {
            // switch to custom mode
            setData('cron_expression', customCron);
        }
    };

    const handleCustomCronChange = (value: string) => {
        setCustomCron(value);
        if (cronPreset === 'custom') {
            setData('cron_expression', value);
        }
    };

    // row management (headers/extract)
    const addSourceHeaderRow = () => {
        setSourceHeaders([...sourceHeaders, { key: '', value: '' }]);
    };
    const updateSourceHeaderRow = (index: number, field: 'key' | 'value', value: string) => {
        const clone = [...sourceHeaders];
        clone[index][field] = value;
        setSourceHeaders(clone);
    };
    const removeSourceHeaderRow = (index: number) => {
        setSourceHeaders(sourceHeaders.filter((_, i) => i !== index));
    };

    const addDestHeaderRow = () => {
        setDestHeaders([...destHeaders, { key: '', value: '' }]);
    };
    const updateDestHeaderRow = (index: number, field: 'key' | 'value', value: string) => {
        const clone = [...destHeaders];
        clone[index][field] = value;
        setDestHeaders(clone);
    };
    const removeDestHeaderRow = (index: number) => {
        setDestHeaders(destHeaders.filter((_, i) => i !== index));
    };

    const addExtractMappingRow = () => {
        setExtractMappings([...extractMappings, { key: '', value: '' }]);
    };
    const updateExtractMappingRow = (index: number, field: 'key' | 'value', value: string) => {
        const clone = [...extractMappings];
        clone[index][field] = value;
        setExtractMappings(clone);
    };
    const removeExtractMappingRow = (index: number) => {
        setExtractMappings(extractMappings.filter((_, i) => i !== index));
    };

    // --- Submit / Reset ---

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (sourceBodyValid === false) {
            toast.error('Source Body must be a valid JSON object/array/string.');
            return;
        }
        if (destBodyValid === false) {
            toast.error('Destination Body Template must be valid JSON.');
            return;
        }

        // Build normalized payload (similar spirit to create)
        const normalized = { ...data };

        // force-parse again for safety
        try {
            if (sourceBodyStr == '{}' || sourceBodyStr == '') {
                normalized.source.body = null;
            } else {
                normalized.source.body = JSON5.parse(sourceBodyStr);
            }
        } catch {
            normalized.source.body = null;
        }

        try {
            normalized.destination.body_template = JSON.stringify(JSON5.parse(destBodyStr));
        } catch {
            normalized.destination.body_template = JSON.stringify({});
        }


        if (!useForeach) {
            normalized.destination.foreach = '';
        }
        put(configurationRoute.update({ configuration: configuration.id }).url, {
            data: normalized,
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        // reset form state (useForm)
        setData({
            service_environment_id: initial.current.service_environment_id,
            channel_id: initial.current.channel_id,
            source: {
                url: initial.current.source.url,
                method: initial.current.source.method,
                headers: (() => {
                    try {
                        return JSON.parse(initial.current.source.headers);
                    } catch {
                        return initial.current.source.headers;
                    }
                })(),
                body: (() => {
                    try {
                        const parsed = JSON5.parse(initial.current.source.body);
                        return parsed;
                    } catch {
                        return initial.current.source.body;
                    }
                })(),
                timeout: initial.current.source.timeout,
                retry_count: initial.current.source.retry_count,
            },
            destination: {
                url: initial.current.destination.url,
                method: initial.current.destination.method,
                headers: (() => {
                    try {
                        return JSON.parse(initial.current.destination.headers);
                    } catch {
                        return initial.current.destination.headers;
                    }
                })(),
                extract: (() => {
                    try {
                        return JSON.parse(initial.current.destination.extract);
                    } catch {
                        return initial.current.destination.extract;
                    }
                })(),
                foreach: initial.current.destination.foreach,
                body_template: (() => {
                    try {
                        return JSON5.parse(initial.current.destination.body_template);
                    } catch {
                        return initial.current.destination.body_template;
                    }
                })(),
                timeout: initial.current.destination.timeout,
                retry_count: initial.current.destination.retry_count,
                range_per_request: initial.current.destination.range_per_request,
            },
            cron_expression: initial.current.cron_expression,
        });

        // reset UI states
        // headers
        try {
            const sh = JSON5.parse(initial.current.source.headers);
            setSourceHeaders(
                Object.entries(sh || {}).map(([k, v]) => ({
                    key: k,
                    value: String(v),
                })),
            );
        } catch {
            setSourceHeaders([{ key: 'RAW', value: initial.current.source.headers }]);
        }

        try {
            const dh = JSON5.parse(initial.current.destination.headers);
            setDestHeaders(
                Object.entries(dh || {}).map(([k, v]) => ({
                    key: k,
                    value: String(v),
                })),
            );
        } catch {
            setDestHeaders([
                {
                    key: 'RAW',
                    value: initial.current.destination.headers,
                },
            ]);
        }

        try {
            const em = JSON5.parse(initial.current.destination.extract);
            const ent = Object.entries(em || {});
            setExtractMappings(
                ent.length
                    ? ent.map(([k, v]) => ({
                          key: k,
                          value: String(v),
                      }))
                    : [{ key: '', value: '' }],
            );
        } catch {
            setExtractMappings([
                {
                    key: 'RAW',
                    value: initial.current.destination.extract,
                },
            ]);
        }

        // body editors
        setSourceBodyStr(initial.current.source.body);
        setDestBodyStr(initial.current.destination.body_template);

        setSourceBodyValid(true);
        setDestBodyValid(true);

        setUseForeach(!!(initial.current.destination.foreach && initial.current.destination.foreach.trim()));

        // cron
        const detected = detectCronPreset(initial.current.cron_expression || '');
        setCronPreset(detected);
        setCustomCron(detected === 'custom' ? initial.current.cron_expression || '' : '');

        clearErrors();
        resetAll();
    };

    const isDirty =
        String(data.service_environment_id ?? '') !== String(initial.current.service_environment_id ?? '') ||
        String(data.channel_id ?? '') !== String(initial.current.channel_id ?? '') ||
        String(data.cron_expression ?? '') !== String(initial.current.cron_expression ?? '') ||
        JSON.stringify(data.source ?? {}) !== JSON.stringify(initial.current.source ?? {}) ||
        JSON.stringify(data.destination ?? {}) !== JSON.stringify(initial.current.destination ?? {});

    const hasRequiredFields = data.service_environment_id != null && data.channel_id != null && (data.cron_expression?.trim() || '').length > 0;

    const isDisabled = processing || !hasRequiredFields || !isDirty || sourceBodyValid === false || destBodyValid === false;

    return (
        <AppLayout>
            <Head title="Edit Configuration" />
            <Toaster richColors position="top-right" />

            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-2xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <div className="mb-4 flex items-center justify-between">
                        <h1 className="text-xl font-semibold">Edit Configuration</h1>
                    </div>

                    {props?.flash?.error && (
                        <div className="mb-4 rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">
                            {props.flash.error}
                        </div>
                    )}

                    <p className="mb-4 text-sm text-muted-foreground">Update the configuration details below.</p>

                    <form id="edit-configuration-form" onSubmit={handleSubmit} className="space-y-6">
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

                        {/* Source (Elastic) Section */}
                        <div className="space-y-4 rounded-lg border p-4">
                            <h2 className="text-base font-semibold">Source (Elastic)</h2>
                            {errors['source'] && <p className="mt-1 text-sm text-destructive">{errors['source']}</p>}

                            {/* URL */}
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

                            {/* Method */}
                            <div>
                                <label className="mb-1.5 block text-sm font-medium">Method</label>
                                <Select value={data.source.method} onValueChange={(v) => setData('source.method', v)}>
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {HTTP_METHODS.map((m) => (
                                            <SelectItem key={m} value={m}>
                                                {m}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Headers */}
                            <div>
                                <div className="mb-1.5 flex items-center justify-between">
                                    <label className="text-sm font-medium">Headers</label>
                                    <Button type="button" size="sm" variant="outline" onClick={addSourceHeaderRow}>
                                        <Plus className="mr-1 h-3.5 w-3.5" />
                                        Add Header
                                    </Button>
                                </div>

                                <div className="space-y-2">
                                    {sourceHeaders.map((header, idx) => (
                                        <div key={idx} className="flex gap-2">
                                            <input
                                                type="text"
                                                value={header.key}
                                                onChange={(e) => updateSourceHeaderRow(idx, 'key', e.target.value)}
                                                className="flex-1 rounded-md border px-3 py-1.5 font-mono text-sm"
                                                placeholder="Key"
                                            />
                                            <input
                                                type="text"
                                                value={header.value}
                                                onChange={(e) => updateSourceHeaderRow(idx, 'value', e.target.value)}
                                                className="flex-1 rounded-md border px-3 py-1.5 font-mono text-sm"
                                                placeholder="Value"
                                            />
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => removeSourceHeaderRow(idx)}
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Body JSON */}
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
                                    placeholder='{"query":{"match_all":{}}}'
                                />

                                {errors['source.body'] && <p className="mt-1 text-sm text-destructive">{errors['source.body']}</p>}
                            </div>

                            {/* Timeout / Retry */}
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

                            {/* URL */}
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

                            {/* Method */}
                            <div>
                                <label className="mb-1.5 block text-sm font-medium">Method</label>
                                <Select value={data.destination.method} onValueChange={(v) => setData('destination.method', v)}>
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {HTTP_METHODS.map((m) => (
                                            <SelectItem key={m} value={m}>
                                                {m}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Headers */}
                            <div>
                                <div className="mb-1.5 flex items-center justify-between">
                                    <label className="text-sm font-medium">Headers</label>
                                    <Button type="button" size="sm" variant="outline" onClick={addDestHeaderRow}>
                                        <Plus className="mr-1 h-3.5 w-3.5" />
                                        Add Header
                                    </Button>
                                </div>

                                <div className="space-y-2">
                                    {destHeaders.map((header, idx) => (
                                        <div key={idx} className="flex gap-2">
                                            <input
                                                type="text"
                                                value={header.key}
                                                onChange={(e) => updateDestHeaderRow(idx, 'key', e.target.value)}
                                                className="flex-1 rounded-md border px-3 py-1.5 font-mono text-sm"
                                                placeholder="Key"
                                            />
                                            <input
                                                type="text"
                                                value={header.value}
                                                onChange={(e) => updateDestHeaderRow(idx, 'value', e.target.value)}
                                                className="flex-1 rounded-md border px-3 py-1.5 font-mono text-sm"
                                                placeholder="Value"
                                            />
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => removeDestHeaderRow(idx)}
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Extract Mapping */}
                            <div>
                                <div className="mb-1.5 flex items-center justify-between">
                                    <label className="text-sm font-medium">Extract Mapping</label>
                                    <Button type="button" size="sm" variant="outline" onClick={addExtractMappingRow}>
                                        <Plus className="mr-1 h-3.5 w-3.5" />
                                        Add Mapping
                                    </Button>
                                </div>

                                <p className="mb-2 text-xs text-muted-foreground">
                                    Map fields from source response to template variables (e.g. <code>id → @._source.alert_id</code>)
                                </p>

                                <div className="space-y-2">
                                    {extractMappings.map((m, idx) => (
                                        <div key={idx} className="flex gap-2">
                                            <input
                                                type="text"
                                                value={m.key}
                                                onChange={(e) => updateExtractMappingRow(idx, 'key', e.target.value)}
                                                className="flex-1 rounded-md border px-3 py-1.5 font-mono text-sm"
                                                placeholder="Variable name (e.g. id)"
                                            />
                                            <input
                                                type="text"
                                                value={m.value}
                                                onChange={(e) => updateExtractMappingRow(idx, 'value', e.target.value)}
                                                className="flex-1 rounded-md border px-3 py-1.5 font-mono text-sm"
                                                placeholder="JSONPath (e.g. @._source.alert_id)"
                                            />
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => removeExtractMappingRow(idx)}
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Foreach / toggle */}
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
                                    Enable this to iterate over an array in the response and send separate requests for each item.
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

                            {/* Body Template */}
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
                                    placeholder='{"content":"Elastic Alert {{{id}}} with title {{{name}}}"}'
                                />

                                <p className="mt-1 text-xs text-muted-foreground">
                                    Use Mustache-style placeholders: <code>{'{{{id}}}'}</code>, <code>{'{{{name}}}'}</code>, etc.
                                </p>

                                {errors['destination.body_template'] && (
                                    <p className="mt-1 text-sm text-destructive">{errors['destination.body_template']}</p>
                                )}
                            </div>

                            {/* Timeout / Retry / Range */}
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
                                    <label className="mb-1.5 block text-sm font-medium">Range Time Per Request (s)</label>
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

                        {/* Cron / Schedule */}
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
                                        placeholder="*/30 * * * *"
                                    />
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Enter a custom cron expression (for example, <code>0 */2 * * *</code> = every 2 hours).
                                    </p>
                                </div>
                            )}

                            {errors.cron_expression && <p className="mt-1 text-sm text-destructive">{errors.cron_expression}</p>}
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-between border-t pt-4">
                            <Button asChild variant="ghost">
                                <Link href={configurationRoute.index.url()}>Cancel</Link>
                            </Button>
                            <div className="flex gap-2">
                                <Button type="button" variant="outline" onClick={handleReset} disabled={!isDirty || processing}>
                                    Reset
                                </Button>
                                <Button type="submit" disabled={isDisabled}>
                                    {processing ? 'Saving...' : 'Save Configuration'}
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
