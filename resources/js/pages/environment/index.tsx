import DataTable, { type ColumnDefinition } from '@/components/data-table';
import FilterCard from '@/components/filter-card';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import environmentRoutes from '@/routes/environments';
import { Environment, type BreadcrumbItem, type PaginatedResponse } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import { toast, Toaster } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Environment', href: environmentRoutes.index.url() }];

const formatDateTime = (iso?: string | null) => {
    if (!iso) return '–';
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return '–';
    }
};

export default function EnvironmentIndexPage({
    environments,
    filters,
}: {
    environments: PaginatedResponse<Environment>;
    filters?: Record<string, string | number | undefined>;
}) {
    console.log(environments);
    const [search, setSearch] = useState<string>(String(filters?.search ?? ''));
    const [currentPage, setCurrentPage] = useState(1);
    const itemsPerPage = 10;

    const paginatedData = useMemo(() => {
        if (!environments) return []; 
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        return environments.slice(startIndex, endIndex);
    }, [environments, currentPage, itemsPerPage]);

    const handleSearch = () => {
        if (search) {
            router.get(environmentRoutes.search.url(), { search }, { preserveState: true, replace: true });
        } else {
            router.get(environmentRoutes.index.url(), {}, { preserveState: true, replace: true });
        }
    };

    const handleReset = useCallback(() => {
        setSearch('');
        router.get(environmentRoutes.index.url(), { page: 1, size: itemsPerPage }, { preserveState: true, replace: true });
    }, [itemsPerPage]);

    const handleDelete = useCallback((item: Environment) => {
        toast.warning(`Are you sure you want to delete "${item.name}"?`, {
            description: 'This action cannot be undone.',
            action: {
                label: 'Delete',
                onClick: () => {
                    router.delete(environmentRoutes.destroy.url({ environment: item.id }), {
                        onSuccess: () => toast.success(`Environment "${item.name}" has been deleted.`),
                        onError: () => toast.error('Failed to delete the environment.'),
                    });
                },
            },
            cancel: { label: 'Cancel', onClick: () => {} },
            duration: 8000,
        });
    }, []);

    const columns: ColumnDefinition<Environment>[] = useMemo(
        () => [
            { header: 'ID', align: 'left', render: (item) => item.id },
            { header: 'Name', align: 'left', render: (item) => item.name },
            { header: 'Created At', align: 'left', render: (item) => formatDateTime(item.created_at) },
            { header: 'Updated At', align: 'left', render: (item) => formatDateTime(item.updated_at) },
            {
                header: 'Actions',
                align: 'right',
                render: (item) => (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-8 w-8 p-0" aria-label={`Open menu for ${item.name}`}>
                                <MoreVertical className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <Link href={environmentRoutes.edit.url({ environment: item.id })}>Edit</Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                className="text-red-600 focus:bg-red-50 focus:text-red-500"
                                onSelect={(e) => {
                                    e.preventDefault();
                                    handleDelete(item);
                                }}
                            >
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                ),
            },
        ],
        [handleDelete],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Environment" />
            <Toaster richColors theme="system" position="top-center" />

            <div className="flex flex-col gap-4 p-4 lg:p-6">
                <FilterCard title="Filter Environment" description="Cari environment berdasarkan nama" className="mx-auto w-full max-w-lg">
                    <div className="flex w-full max-w-lg items-center">
                        <Input
                            type="text"
                            placeholder="Search environment..."
                            className="flex-1"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                        />
                        <Button className="ml-2" onClick={handleSearch}>
                            Search
                        </Button>
                        <Button className="ml-2" onClick={handleReset}>
                            Reset
                        </Button>
                    </div>
                </FilterCard>

                <div className="rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <div className="mb-4 flex items-center justify-end">
                        <Button asChild>
                            <Link href={environmentRoutes.create.url()}>Create</Link>
                        </Button>
                    </div>

                    <div className="overflow-x-auto">
                        <DataTable columns={columns} data={paginatedData} />
                        <Pagination
                            className="mt-6 flex justify-center"
                            currentPage={currentPage}
                            totalItems={environments?.length ?? 0}
                            itemsPerPage={itemsPerPage}
                            onPageChange={(page) => setCurrentPage(page)}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
