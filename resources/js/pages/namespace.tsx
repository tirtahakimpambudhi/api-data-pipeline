import FilterCard from '@/components/filter-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { namespace } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Namespace',
        href: namespace().url,
    },
];

export default function Namespace() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Namespace" />
            <div className="flex h-full flex-1 flex-col items-center gap-4 p-4">
                <FilterCard title="Filter Namespace" description="Filter namespace by name" className="w-full max-w-lg">
                    <div className="flex w-full max-w-lg items-center">
                        <Input type="text" placeholder="Search namespace..." className="flex-1" />
                        <Button className="ml-2">Search</Button>
                    </div>
                </FilterCard>

                <div className="relative w-full flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </AppLayout>
    );
}
