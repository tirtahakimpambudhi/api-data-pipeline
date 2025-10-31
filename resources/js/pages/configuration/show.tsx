import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronRight, Copy } from 'lucide-react';
import React from 'react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import configurationRoute from '@/routes/configurations/index';
import { Channel, Configuration, ServiceEnvironment } from '@/types';

type PageProps = {
    configuration: Configuration;
    serviceEnvironments: ServiceEnvironment[];
    channels: Channel[];
    flash?: { message?: string; error?: string; success?: string };
};

/* ------------------------------------------
 * Helpers
 * ------------------------------------------ */

function toPrettyJSON(value: any): string {
    try {
        if (value === null || value === undefined) return '{}';

        if (typeof value === 'string') {
            try {
                const parsed = JSON.parse(value);
                return JSON.stringify(parsed, null, 2);
            } catch {
                return value;
            }
        }

        return JSON.stringify(value, null, 2);
    } catch {
        return String(value);
    }
}

function toPairs(value: any): { key: string; value: string }[] {
    if (value == null) return [];

    if (typeof value === 'string') {
        try {
            const parsed = JSON.parse(value);
            if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
                return Object.entries(parsed).map(([k, v]) => ({
                    key: k,
                    value: typeof v === 'string' ? v : JSON.stringify(v),
                }));
            }
        } catch {
            /* ignore */
        }
        return [{ key: 'RAW', value }];
    }

    if (typeof value === 'object') {
        if (Array.isArray(value)) {
            return [{ key: 'RAW', value: JSON.stringify(value) }];
        }
        return Object.entries(value).map(([k, v]) => ({
            key: k,
            value: typeof v === 'string' ? v : JSON.stringify(v),
        }));
    }

    return [{ key: 'RAW', value: String(value) }];
}

function copy(text: string) {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
        navigator.clipboard.writeText(text);
    }
}

function serviceEnvLabel(se?: ServiceEnvironment) {
    if (!se) return '-';
    const serviceName = se.service?.full_name ?? se.service?.name ?? `Service #${se.service_id}`;
    const envName = se.environment?.name ?? `Env #${se.environment_id}`;
    return `${serviceName} / ${envName}`;
}

/* ------------------------------------------
 * Small UI atoms
 * ------------------------------------------ */

function Badge({ color = 'slate', children }: { color?: 'green' | 'red' | 'slate' | 'blue'; children: React.ReactNode }) {
    const palette: Record<string, string> = {
        green: 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/30',
        red: 'bg-red-500/15 text-red-600 ring-1 ring-red-500/30',
        slate: 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/30',
        blue: 'bg-blue-500/15 text-blue-600 ring-1 ring-blue-500/30',
    };

    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[11px] leading-none font-medium ${palette[color]}`}>{children}</span>
    );
}

function Section({ title, description, children }: { title: string; description?: React.ReactNode; children: React.ReactNode }) {
    return (
        <div className="space-y-4 rounded-lg border p-4">
            <div>
                <h2 className="text-base font-semibold">{title}</h2>
                {description && <p className="mt-1 text-xs text-muted-foreground">{description}</p>}
            </div>
            {children}
        </div>
    );
}

function FieldRow({ label, value, mono, breakAll }: { label: string; value: React.ReactNode; mono?: boolean; breakAll?: boolean }) {
    return (
        <div className="space-y-1.5">
            <div className="text-[11px] font-medium tracking-wide text-muted-foreground uppercase">{label}</div>
            <div
                className={['rounded-md border bg-muted/30 px-3 py-2 text-sm', mono ? 'font-mono' : '', breakAll ? 'break-all' : '']
                    .filter(Boolean)
                    .join(' ')}
            >
                {value || '-'}
            </div>
        </div>
    );
}

function KeyValueTable({
    label,
    pairs,
    helpText,
    onCopy,
    emptyText = 'No data configured',
}: {
    label: string;
    pairs: { key: string; value: string }[];
    helpText?: React.ReactNode;
    onCopy?: () => void;
    emptyText?: string;
}) {
    return (
        <div className="space-y-2">
            <div className="flex items-start justify-between">
                <div>
                    <div className="text-[11px] font-medium tracking-wide text-muted-foreground uppercase">{label}</div>
                    {helpText && <div className="mt-1 text-[11px] text-muted-foreground">{helpText}</div>}
                </div>

                {onCopy && (
                    <button
                        type="button"
                        title="Copy"
                        onClick={onCopy}
                        className="inline-flex items-center gap-1 rounded-md border bg-background/50 px-2 py-1 text-[11px] hover:bg-accent"
                    >
                        <Copy className="h-3.5 w-3.5" />
                        Copy
                    </button>
                )}
            </div>

            {pairs.length ? (
                <div className="divide-y rounded-md border">
                    {pairs.map((row, idx) => (
                        <div key={idx} className="grid grid-cols-1 gap-2 p-3 font-mono text-xs sm:grid-cols-3">
                            <div className="font-semibold break-all text-card-foreground/80 sm:col-span-1">{row.key}</div>
                            <div className="break-all text-card-foreground/70 sm:col-span-2">{row.value}</div>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="rounded-md border bg-muted/30 px-3 py-2 text-xs text-muted-foreground">{emptyText}</div>
            )}
        </div>
    );
}

function CollapsibleJSON({ label, value, hint, copyLabel = 'Copy JSON' }: { label: string; value: any; hint?: React.ReactNode; copyLabel?: string }) {
    const [open, setOpen] = React.useState(false);
    const pretty = toPrettyJSON(value);

    return (
        <div className="space-y-2">
            <div className="flex items-start justify-between">
                <div>
                    <div className="text-[11px] font-medium tracking-wide text-muted-foreground uppercase">{label}</div>
                    {hint && <div className="mt-1 text-[11px] text-muted-foreground">{hint}</div>}
                </div>

                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        onClick={() => copy(pretty)}
                        className="inline-flex items-center gap-1 rounded-md border bg-background/50 px-2 py-1 text-[11px] hover:bg-accent"
                        title={copyLabel}
                    >
                        <Copy className="h-3.5 w-3.5" />
                        {copyLabel}
                    </button>

                    <button
                        type="button"
                        onClick={() => setOpen((o) => !o)}
                        className="inline-flex items-center gap-1 rounded-md border bg-background/50 px-2 py-1 text-[11px] hover:bg-accent"
                        title={open ? 'Hide raw JSON' : 'Show raw JSON'}
                    >
                        {open ? <ChevronDown className="h-3.5 w-3.5" /> : <ChevronRight className="h-3.5 w-3.5" />}
                        {open ? 'Hide JSON' : 'Show JSON'}
                    </button>
                </div>
            </div>

            {open ? (
                <pre className="max-h-64 overflow-auto rounded-md border bg-muted/30 p-3 font-mono text-[11px] leading-relaxed">{pretty}</pre>
            ) : (
                <div className="rounded-md border bg-muted/30 px-3 py-2 text-[11px] text-muted-foreground">Raw JSON hidden</div>
            )}
        </div>
    );
}

// Variable
const CRON_PRESETS = [
    { label: 'Every 5 minutes', value: '*/5 * * * *' },
    { label: 'Every 15 minutes', value: '*/15 * * * *' },
    { label: 'Every 30 minutes', value: '*/30 * * * *' },
    { label: 'Every 1 hour', value: '0 * * * *' },
    { label: 'Every 5 hours', value: '0 */5 * * *' },
    { label: 'Custom', value: 'custom' },
];

/* ------------------------------------------
 * Main Page
 * ------------------------------------------ */

export default function ConfigurationDetailPage() {
    const { props } = usePage<PageProps>();
    const cfg = props.configuration;

    // channel fallback resolve
    const channelName = cfg.channel?.name ?? props.channels?.find((c) => String(c.id) === String(cfg.channel_id))?.name ?? '-';

    // derived
    const foreachEnabled = !!cfg.destination?.foreach && cfg.destination.foreach.trim() !== '';

    const sourceHeaders = toPairs(cfg.source?.headers);
    const destHeaders = toPairs(cfg.destination?.headers);
    const extractMap = toPairs(cfg.destination?.extract);

    /* ---------- RENDER ---------- */

    return (
        <AppLayout>
            <Head title={`Configuration — ${cfg.name ?? cfg.id}`} />

            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-2xl space-y-6 rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    {/* Top Bar */}
                    <header className="flex items-start justify-between gap-4">
                        <div>
                            <h1 className="text-xl font-semibold text-card-foreground">Configuration Detail</h1>

                            <div className="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <span className="font-medium text-card-foreground">{cfg.name ?? '-'}</span>

                                <Badge color="blue">{channelName || 'No Channel'}</Badge>

                                <Badge color="slate">{serviceEnvLabel(cfg.service_environment)}</Badge>

                                <Badge color="green">
                                    {cfg?.cron_expression
                                        ? (CRON_PRESETS.find((p) => p.value === cfg.cron_expression)?.label ?? `Custom (${cfg.cron_expression})`)
                                        : 'No schedule'}
                                </Badge>
                            </div>

                            <p className="mt-2 text-[11px] leading-relaxed text-muted-foreground">
                                This configuration periodically queries the source (Elastic) and forwards the processed result to the destination
                                channel.
                            </p>
                        </div>

                        <div className="flex items-center gap-2">
                            <Button asChild variant="ghost" size="sm">
                                <Link href={configurationRoute.index.url()}>Back</Link>
                            </Button>

                            <Button asChild size="sm">
                                <Link href={configurationRoute.edit({ configuration: cfg.id })}>Edit</Link>
                            </Button>
                        </div>
                    </header>

                    {/* Overview Section */}
                    <Section
                        title="Overview"
                        description={<>High-level metadata about this configuration, including lifecycle timestamps and routing target.</>}
                    >
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-4">
                                <FieldRow label="Configuration ID" value={cfg.id} />
                                <FieldRow label="Service / Environment" value={serviceEnvLabel(cfg.service_environment)} />
                                <FieldRow label="Channel" value={channelName} />
                            </div>

                            <div className="space-y-4">
                                <FieldRow label="Cron Schedule" value={cfg.cron_expression ?? '-'} mono />
                                <FieldRow label="Created At" value={cfg.created_at ?? '-'} />
                                <FieldRow label="Updated At" value={cfg.updated_at ?? '-'} />
                            </div>
                        </div>
                    </Section>

                    {/* Source Section */}
                    <Section
                        title="Source (Elastic)"
                        description={<>These settings define how data is fetched. The system will call this endpoint on the specified schedule.</>}
                    >
                        {/* basic connection */}
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <FieldRow label="Request URL" value={cfg.source?.url ?? '-'} breakAll />
                            <FieldRow label="HTTP Method" value={cfg.source?.method ?? '-'} />
                            <FieldRow label="Timeout (s)" value={cfg.source?.timeout ?? '-'} />
                        </div>

                        {/* headers */}
                        <KeyValueTable
                            label="Request Headers"
                            pairs={sourceHeaders}
                            helpText="These headers are included in every request to the source."
                            onCopy={() => copy(toPrettyJSON(cfg.source?.headers))}
                            emptyText="No headers configured"
                        />

                        {/* body */}
                        <CollapsibleJSON
                            label="Request Body"
                            value={cfg.source?.body}
                            hint="Payload sent to Elastic. This often includes filters, query DSL, etc."
                            copyLabel="Copy request body"
                        />

                        {/* reliability */}
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FieldRow label="Retry Count" value={cfg.source?.retryCount ?? cfg.source?.retry_count ?? '-'} />
                            <FieldRow label="Notes" value={'If the request fails, the system will retry up to this count.'} />
                        </div>
                    </Section>

                    {/* Destination Section */}
                    <Section
                        title="Destination (Channel)"
                        description={<>After extracting and transforming data from the source, the system will post it here.</>}
                    >
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <FieldRow label="Destination URL" value={cfg.destination?.url ?? '-'} breakAll />
                            <FieldRow label="HTTP Method" value={cfg.destination?.method ?? '-'} />
                            <FieldRow label="Timeout (s)" value={cfg.destination?.timeout ?? '-'} />
                        </div>

                        <KeyValueTable
                            label="Destination Headers"
                            pairs={destHeaders}
                            helpText="These headers are included in every outbound request."
                            onCopy={() => copy(toPrettyJSON(cfg.destination?.headers))}
                            emptyText="No headers configured"
                        />

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {/* Extract mapping */}
                            <KeyValueTable
                                label="Extract Mapping"
                                pairs={extractMap}
                                helpText={<>Maps fields from the source response to template variables used in the body.</>}
                                emptyText="No extract mapping defined"
                            />

                            {/* Foreach / batch */}
                            <div className="space-y-4">
                                <div className="space-y-1.5">
                                    <div className="text-[11px] font-medium tracking-wide text-muted-foreground uppercase">Batch Send</div>
                                    <div className="flex items-center gap-2">
                                        {foreachEnabled ? <Badge color="green">Enabled</Badge> : <Badge color="slate">Disabled</Badge>}
                                        <span className="text-[12px] text-muted-foreground">
                                            Send one request per item in an array from the source.
                                        </span>
                                    </div>
                                </div>

                                <FieldRow label="Foreach Path (JSONPath)" value={cfg.destination?.foreach || '-'} mono breakAll />
                            </div>
                        </div>

                        <CollapsibleJSON
                            label="Body Template"
                            value={cfg.destination?.body_template}
                            hint={
                                <>
                                    This is the message payload that will be sent to the destination. It supports Mustache-style variables such as{' '}
                                    <code>{'{{{id}}}'}</code> or <code>{'{{{name}}}'}</code>, which are filled from the Extract Mapping.
                                </>
                            }
                            copyLabel="Copy body template"
                        />
                    </Section>

                    {/* Delivery / Reliability Section */}
                    <Section title="Delivery Settings" description="Controls how often we send data out and how we handle failures.">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <FieldRow label="Retry Count" value={cfg.destination?.retryCount ?? cfg.destination?.retry_count ?? '-'} />

                            <FieldRow
                                label="Range Per Request(s)"
                                value={cfg.destination?.rangePerRequest / 1000 ?? cfg.destination?.range_per_request / 1000 ?? '-'}
                            />

                            <FieldRow label="Timeout (s)" value={cfg.destination?.timeout ?? '-'} />
                        </div>

                        <div className="text-[11px] text-muted-foreground">
                            <p>
                                <span className="font-medium text-card-foreground">Retry Count</span> defines how many times we attempt delivery if
                                the channel fails. <span className="font-medium text-card-foreground">Range Per Request (s)</span> time interval per
                                request in seconds.
                            </p>
                        </div>
                    </Section>

                    {/* Footer Actions */}
                    <footer className="flex justify-end pt-2">
                        <Button asChild>
                            <Link href={configurationRoute.index.url()}>Close</Link>
                        </Button>
                    </footer>
                </div>
            </div>
        </AppLayout>
    );
}
