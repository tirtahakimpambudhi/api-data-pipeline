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
import { useMemo } from 'react'
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

    // ---- Safety defaults ----
    const namespaces: Namespace[] = data?.namespaces ?? []
    const services: Service[] = data?.services ?? []
    const serviceEnvs: ServiceEnvironment[] = data?.servicesEnvironments ?? []
    const environments: Environment[] = data?.environments ?? []
    const channels: Channel[] = data?.channels ?? []
    const configurations: Configuration[] = data?.configurations ?? []

    // ---- Counts ----
    const nsCount = namespaces.length
    const svcCount = services.length
    const envCount = environments.length
    const chCount = channels.length
    const cfgCount = configurations.length
    const seCount = serviceEnvs.length

    const hasAnyData =
        nsCount > 0 || svcCount > 0 || envCount > 0 || chCount > 0 || cfgCount > 0 || seCount > 0

    const showKpiConfigurations = cfgCount > 0
    const showKpiServices = svcCount > 0
    const showKpiEnvironments = envCount > 0
    const showKpiChannels = chCount > 0
    const showKpiNamespaces = nsCount > 0

    // Coverage butuh: environments>0, services>0, configurations>0, serviceEnvs>0
    const hasCoverageData = envCount > 0 && svcCount > 0 && cfgCount > 0 && seCount > 0


    // Role awareness
    const roleName = (auth as any)?.user?.role?.name?.toLowerCase?.() ?? 'almighty'
    const isAlmighty = roleName === 'almighty'

    // Coverage per Environment (compute only when visible)
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

    // Alerts: cari env "prod" ATAU "production"
    const prodEnv =
        environments.find((e) => e.name?.toLowerCase() === 'prod') ??
        environments.find((e) => e.name?.toLowerCase() === 'production')

    // Alerts butuh: prodEnv ada, services>0, configurations>0, serviceEnvs>0
    const canShowAlerts = !!prodEnv && svcCount > 0 && cfgCount > 0 && seCount > 0

    const prodGaps = useMemo(() => {
        if (!canShowAlerts || !prodEnv) return [] as string[]
        const svcSet = new Set<number>()
        for (const cfg of configurations) {
            const se = serviceEnvs.find((x) => x.id === cfg.service_environment_id)
            if (se?.environment_id === prodEnv.id) svcSet.add(se.service_id)
        }
        return services.filter((s) => !svcSet.has(s.id)).map((s) => s.name)
    }, [canShowAlerts, configurations, serviceEnvs, prodEnv, services])

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <Toaster richColors theme="system" position="top-right" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-col justify-between gap-2 md:flex-row md:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight md:text-3xl">
                            Selamat datang, {(auth as any)?.user?.name ?? 'Pengguna'} 👋
                        </h1>
                        <p className="text-sm text-muted-foreground">Ringkasan singkat sistem — klik untuk masuk ke halaman detail.</p>
                    </div>
                    <Badge variant="secondary" className="capitalize">Role: {isAlmighty ? 'almighty' : 'slave'}</Badge>
                </div>

                {/* Row 1: KPIs — hanya render kartu yang ada datanya */}
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

                {/* Row 2: Coverage — render HANYA jika semua data pendukung ada */}
                {hasCoverageData && (
                    <Card className="relative overflow-hidden">
                        <div className="absolute inset-0 opacity-[0.06]">
                            <PlaceholderPattern className="absolute inset-0 size-full" />
                        </div>
                        <CardHeader>
                            <CardTitle>Coverage per Environment</CardTitle>
                            <CardDescription>Persentase service yang memiliki configuration pada tiap environment.</CardDescription>
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

                {/* Row 4: Shortcuts — tampil hanya jika ada data relevan */}
                {(cfgCount > 0 || (svcCount > 0 || envCount > 0 || chCount > 0 || nsCount > 0)) && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Masuk ke Halaman</CardTitle>
                            <CardDescription>Shortcut ke halaman detail yang sudah ada.</CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-wrap gap-2">
                            {cfgCount > 0 && (
                                <Button asChild variant="secondary">
                                    <a href="/configurations"><Settings className="mr-2 h-4 w-4" />Configurations</a>
                                </Button>
                            )}
                            <>
                                {svcCount > 0 && (
                                    <Button asChild variant="outline">
                                        <a href="/services"><Server className="mr-2 h-4 w-4" />Services</a>
                                    </Button>
                                )}
                                {envCount > 0 && (
                                    <Button asChild variant="outline">
                                        <a href="/environments"><Globe className="mr-2 h-4 w-4" />Environments</a>
                                    </Button>
                                )}
                                {chCount > 0 && (
                                    <Button asChild variant="outline">
                                        <a href="/channels"><Cable className="mr-2 h-4 w-4" />Channels</a>
                                    </Button>
                                )}
                                {nsCount > 0 && (
                                    <Button asChild variant="outline">
                                        <a href="/namespaces"><Shield className="mr-2 h-4 w-4" />Namespaces</a>
                                    </Button>
                                )}
                                {seCount > 0 && (
                                    <Button asChild variant="outline">
                                        <a href="/service-environments">
                                            <Activity className="mr-2 h-4 w-4" />
                                            Service Environments
                                        </a>
                                    </Button>
                                )}

                            </>
                        </CardContent>
                    </Card>
                )}

            </div>
        </AppLayout>
    )
}
