import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import configurationRoute from '@/routes/configurations/index';
import { Channel, Configuration, ServiceEnvironment } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Copy } from 'lucide-react';

type PageProps = {
    configuration: Configuration;
    serviceEnvironments: ServiceEnvironment[];
    channels: Channel[];
    flash?: { message?: string; error?: string; success?: string };
};

function prettyJSON(value: any) {
    try {
        if (value === null || value === undefined) return 'null';
        if (typeof value === 'string') {
            try {
                const p = JSON.parse(value);
                return JSON.stringify(p, null, 2);
            } catch {
                return value;
            }
        }
        return JSON.stringify(value, null, 2);
    } catch {
        return String(value);
    }
}

function copyToClipboard(text: string) {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
        navigator.clipboard.writeText(text);
    }
}

export default function ConfigurationDetailPage() {
    const { props } = usePage<PageProps>();
    const configuration = props.configuration;

    const seLabel = (se?: ServiceEnvironment) => {
        if (!se) return '-';
        const serviceName = se.service?.full_name ?? se.service?.name ?? `Service #${se.service_id}`;
        const envName = se.environment?.name ?? `Env #${se.environment_id}`;
        return `${serviceName} / ${envName}`;
    };

    const channelLabel = (chId?: number | string) => {
        const ch = props.channels?.find((c) => String(c.id) === String(chId));
        return ch?.name ?? `-`;
    };

    return (
        <AppLayout>
            <Head title={`Configuration — ${configuration.name ?? configuration.id}`} />

            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-3xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <div className="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <h1 className="text-lg font-semibold">Configuration detail</h1>
                            <p className="text-sm text-muted-foreground">{configuration.name}</p>
                        </div>

                        <div className="flex items-center gap-2">
                            <Button asChild variant="ghost">
                                <Link href={configurationRoute.index.url()}>Back</Link>
                            </Button>
                            <Button asChild>
                                <Link href={configurationRoute.edit({ configuration: configuration.id })}>Edit</Link>
                            </Button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <label className="text-xs font-medium">ID</label>
                            <div className="rounded border bg-muted/30 p-2 text-sm">{configuration.id}</div>

                            <label className="mt-2 text-xs font-medium">Service / Environment</label>
                            <div className="rounded border bg-muted/30 p-2 text-sm">{seLabel(configuration.service_environment)}</div>

                            <label className="mt-2 text-xs font-medium">Cron Expression</label>
                            <div className="rounded border bg-muted/30 p-2 text-sm">{configuration.cron_expression ?? '-'}</div>

                            <label className="mt-2 text-xs font-medium">Created At</label>
                            <div className="rounded border bg-muted/30 p-2 text-sm">{configuration.created_at ?? '-'}</div>

                            <label className="mt-2 text-xs font-medium">Updated At</label>
                            <div className="rounded border bg-muted/30 p-2 text-sm">{configuration.updated_at ?? '-'}</div>
                        </div>

                        <div className="space-y-2">
                            <label className="text-xs font-medium">Name</label>
                            <div className="rounded border bg-muted/30 p-2 text-sm">{configuration.name ?? '-'}</div>

                            <label className="mt-2 text-xs font-medium">ServiceEnvironment (ref)</label>
                            <div className="rounded border bg-muted/30 p-2 text-sm">{configuration.service_environment?.name ?? '-'}</div>

                            <label className="mt-2 text-xs font-medium">Channel (ref)</label>
                            <div className="rounded border bg-muted/30 p-2 text-sm">
                                {configuration.channel?.name ?? channelLabel(configuration.channel_id)}
                            </div>
                        </div>
                    </div>

                    <div className="mt-4 border-t pt-4">
                        <h2 className="mb-2 text-sm font-medium">Source</h2>

                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div>
                                <label className="text-xs">URL</label>
                                <div className="rounded border bg-muted/30 p-2 text-sm break-all">{configuration.source?.url ?? '-'}</div>
                            </div>

                            <div>
                                <label className="text-xs">Method</label>
                                <div className="rounded border bg-muted/30 p-2 text-sm">{configuration.source?.method ?? '-'}</div>
                            </div>

                            <div>
                                <label className="text-xs">Timeout (s)</label>
                                <div className="rounded border bg-muted/30 p-2 text-sm">{configuration.source?.timeout ?? '-'}</div>
                            </div>
                        </div>

                        <div className="mt-3">
                            <label className="text-xs">Headers</label>
                            <div className="relative">
                                <pre className="max-h-48 overflow-auto rounded border bg-muted/30 p-3 font-mono text-xs">
                                    {prettyJSON(configuration.source?.headers)}
                                </pre>
                                <button
                                    type="button"
                                    className="absolute top-2 right-2 inline-flex items-center gap-2 rounded px-2 py-1 text-xs"
                                    onClick={() => copyToClipboard(prettyJSON(configuration.source?.headers))}
                                    title="Copy headers"
                                >
                                    <Copy className="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <div className="mt-3">
                            <label className="text-xs">Body</label>
                            <div className="relative">
                                <pre className="max-h-64 overflow-auto rounded border bg-muted/30 p-3 font-mono text-xs">
                                    {prettyJSON(configuration.source?.body)}
                                </pre>
                                <button
                                    type="button"
                                    className="absolute top-2 right-2 inline-flex items-center gap-2 rounded px-2 py-1 text-xs"
                                    onClick={() => copyToClipboard(prettyJSON(configuration.source?.body))}
                                    title="Copy body"
                                >
                                    <Copy className="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div className="mt-4 border-t pt-4">
                        <h2 className="mb-2 text-sm font-medium">Destination</h2>

                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div>
                                <label className="text-xs">URL</label>
                                <div className="rounded border bg-muted/30 p-2 text-sm break-all">{configuration.destination?.url ?? '-'}</div>
                            </div>

                            <div>
                                <label className="text-xs">Method</label>
                                <div className="rounded border bg-muted/30 p-2 text-sm">{configuration.destination?.method ?? '-'}</div>
                            </div>

                            <div>
                                <label className="text-xs">Timeout (s)</label>
                                <div className="rounded border bg-muted/30 p-2 text-sm">{configuration.destination?.timeout ?? '-'}</div>
                            </div>
                        </div>

                        <div className="mt-3">
                            <label className="text-xs">Headers</label>
                            <div className="relative">
                                <pre className="max-h-48 overflow-auto rounded border bg-muted/30 p-3 font-mono text-xs">
                                    {prettyJSON(configuration.destination?.headers)}
                                </pre>
                                <button
                                    type="button"
                                    className="absolute top-2 right-2 inline-flex items-center gap-2 rounded px-2 py-1 text-xs"
                                    onClick={() => copyToClipboard(prettyJSON(configuration.destination?.headers))}
                                    title="Copy headers"
                                >
                                    <Copy className="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <div className="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <div>
                                <label className="text-xs">Extract Mapping</label>
                                <pre className="max-h-40 overflow-auto rounded border bg-muted/30 p-3 font-mono text-xs">
                                    {prettyJSON(configuration.destination?.extract)}
                                </pre>
                            </div>

                            <div>
                                <label className="text-xs">Foreach</label>
                                <div className="rounded border bg-muted/30 p-2 text-sm">{configuration.destination?.foreach ?? '-'}</div>
                            </div>
                        </div>

                        <div className="mt-3">
                            <label className="text-xs">Body Template</label>
                            <div className="relative">
                                <pre className="max-h-64 overflow-auto rounded border bg-muted/30 p-3 font-mono text-xs">
                                    {prettyJSON(configuration.destination?.body_template)}
                                </pre>
                                <button
                                    type="button"
                                    className="absolute top-2 right-2 inline-flex items-center gap-2 rounded px-2 py-1 text-xs"
                                    onClick={() => copyToClipboard(prettyJSON(configuration.destination?.body_template))}
                                    title="Copy body template"
                                >
                                    <Copy className="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <div className="mt-3 flex gap-2">
                            <div className="flex-1">
                                <label className="text-xs">Retry Count</label>
                                <div className="rounded border bg-muted/30 p-2 text-sm">
                                    {configuration.destination?.retryCount ?? configuration.destination?.retryCount ?? '-'}
                                </div>
                            </div>

                            <div className="flex-1">
                                <label className="text-xs">Range Per Request</label>
                                <div className="rounded border bg-muted/30 p-2 text-sm">
                                    {configuration.destination?.rangePerRequest ?? configuration.destination?.rangePerRequest ?? '-'}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end">
                        <Button asChild>
                            <Link href={configurationRoute.index.url()}>Close</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
