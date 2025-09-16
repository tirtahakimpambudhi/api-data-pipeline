import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export function KpiCard({ icon, label, value, href }: { icon: React.ReactNode; label: string; value: number | string; href?: string }) {
    return (
        <a href={href ?? '#'}>
            <Card className="transition hover:shadow-md">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">{label}</CardTitle>
                    {icon}
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">{value}</div>
                </CardContent>
            </Card>
        </a>
    )
}
