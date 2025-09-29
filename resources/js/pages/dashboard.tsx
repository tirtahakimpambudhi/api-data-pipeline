import { PlaceholderPattern } from '@/components/ui/placeholder-pattern'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Separator } from '@/components/ui/separator'
import { useFlash } from '@/hooks/use-flash'
import AppLayout from '@/layouts/app-layout'
import { dashboard } from '@/routes'
import { SharedData, type BreadcrumbItem, Namespace, Service, ServiceEnvironment, Environment, Channel, Configuration } from '@/types'
import { Head, usePage } from '@inertiajs/react'
import { Toaster } from 'sonner'
import React, { useMemo } from 'react'
import { Activity, AlertTriangle, Cable, CheckCircle2, Globe, Server, Settings, Shield } from 'lucide-react'
import { KpiCard } from '@/components/kpi-card'

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }]

type Props = {
    flash?: { message?: string; error?: string; success?: string }
    data?: {
        namespaces?: Namespace[]
        services?: Service[]
        servicesEnvironments?: ServiceEnvironment[]
        environments?: Environment[]
        channels?: Channel[]
        configurations?: Configuration[]
    }
}

export default function Dashboard({ data }: Props) {
    const { props } = usePage<Props>()
    const { auth } = usePage<SharedData>().props
    useFlash(props?.flash)

    const namespaces: Namespace[] = data?.namespaces ?? []
    const services: Service[] = data?.services ?? []
    const serviceEnvs: ServiceEnvironment[] = data?.servicesEnvironments ?? []
    const environments: Environment[] = data?.environments ?? []
    const channels: Channel[] = data?.channels ?? []
    const configurations: Configuration[] = data?.configurations ?? []

    const nsCount = namespaces.length
    const svcCount = services.length
    const envCount = environments.length
    const chCount = channels.length
    const cfgCount = configurations.length
    const seCount = serviceEnvs.length
    const showKpiConfigurations = cfgCount > 0
    const showKpiServices = svcCount > 0
    const showKpiEnvironments = envCount > 0
    const showKpiChannels = chCount > 0
    const showKpiNamespaces = nsCount > 0

    const hasCoverageData = envCount > 0 && svcCount > 0 && cfgCount > 0 && seCount > 0


    const roleName = (auth as any)?.user?.role?.name?.toLowerCase?.() ?? 'almighty'

    const coverageByEnv = useMemo(() => {
        if (!hasCoverageData) return [] as { env: Environment; coverage: number }[]
        const byEnv: { env: Environment; coverage: number }[] = []
        for (const env of environments) {
            const svcWithCfg = new Set<number>()
            for (const cfg of configurations) {
                const se = serviceEnvs.find((x) => x.id === cfg.service_environment_id)
                if (se?.environment_id === env.id) svcWithCfg.add(se.service_id)
            }
            const coverage = svcCount ? Math.round((svcWithCfg.size / svcCount) * 100) : 0
            byEnv.push({ env, coverage })
        }
        return byEnv
    }, [hasCoverageData, environments, configurations, serviceEnvs, svcCount])

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <Toaster richColors position="top-right" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
                <div className="flex flex-col justify-between gap-2 md:flex-row md:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight md:text-3xl">
                            Welcome Back, {(auth as any)?.user?.name ?? 'User'} 👋
                        </h1>
                        <p className="text-sm text-muted-foreground">Brief system summary — click to access detailed pages.</p>
                    </div>
                </div>

                {(showKpiConfigurations || (showKpiServices || showKpiEnvironments || showKpiChannels || showKpiNamespaces)) && (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        {showKpiConfigurations && (
                            <KpiCard icon={<Settings className="h-4 w-4" />} label="Configurations" value={cfgCount} href="/configurations" />
                        )}
                        <>
                            {showKpiServices && <KpiCard icon={<Server className="h-4 w-4" />} label="Services" value={svcCount} href="/services" />}
                            {showKpiEnvironments && <KpiCard icon={<Globe className="h-4 w-4" />} label="Environments" value={envCount} href="/environments" />}
                            {showKpiChannels && <KpiCard icon={<Cable className="h-4 w-4" />} label="Channels" value={chCount} href="/channels" />}
                            {seCount > 0 && (
                                <KpiCard
                                    icon={<Activity className="h-4 w-4" />}
                                    label="Service Environments"
                                    value={seCount}
                                    href="/service-environments"
                                />
                            )}

                            {showKpiNamespaces && <KpiCard icon={<Shield className="h-4 w-4" />} label="Namespaces" value={nsCount} href="/namespaces" />}
                        </>
                    </div>
                )}

                {hasCoverageData && (
                    <Card className="relative overflow-hidden">
                        <div className="absolute inset-0 opacity-[0.06]">
                            <PlaceholderPattern className="absolute inset-0 size-full" />
                        </div>
                        <CardHeader>
                            <CardTitle>Coverage per Environment</CardTitle>
                            <CardDescription>Percentage of services that have configuration in each environment.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {coverageByEnv.length > 0 && (
                                <div className="space-y-2">
                                    {coverageByEnv.map(({ env, coverage }) => (
                                        <div key={env.id} className="flex items-center justify-between rounded-lg border p-3">
                                            <div className="capitalize font-medium">{env.name}</div>
                                            <div className="flex items-center gap-2">
                                                <div className="text-sm text-muted-foreground">{coverage}%</div>
                                                <div className="h-2 w-32 overflow-hidden rounded-full bg-muted">
                                                    <div className="h-full bg-primary" style={{ width: `${coverage}%` }} />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

            </div>
        </AppLayout>
    )
}
