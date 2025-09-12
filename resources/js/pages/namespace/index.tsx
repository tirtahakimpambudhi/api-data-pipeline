import DataTable, { type ColumnDefinition } from '@/components/data-table';
import FilterCard from '@/components/filter-card';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/app-layout';
import namespaceRoutes from '@/routes/namespaces';
import { type BreadcrumbItem, type Namespace, type PaginatedResponse } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast, Toaster } from 'sonner';

type ErrorBag = Record<string, string[]>;

type Props = {
    namespaces?: PaginatedResponse<Namespace>;
    filters?: Record<string, unknown>;
    errors?: ErrorBag | null;
    serverError?: string | null;
    statusCode?: number;
    flash: {
        message ?: string,
        success ?: string,
        error ?: string
    }
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Namespace', href: namespaceRoutes.index.url() }];

const formatDateTime = (iso?: string) => {
    if (!iso) return '–';
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return '–';
    }
};

export default function NamespacePage({ namespaces, errors, serverError, statusCode }: Props) {
    const [search, setSearch] = useState(() => {
        if (typeof window === 'undefined') return '';
        return new URLSearchParams(window.location.search).get('search') || '';
    });

    const { props } = usePage<Props>();

    const [error, setError] = useState(props.flash?.error);
    const [success, setSuccess] = useState(props.flash?.success ?? props.flash?.message);

    useEffect(() => {
        if (serverError) {
            toast.error(serverError);
        }
    }, [serverError]);

    useEffect(() => {
        if (error) toast.error(error);
    }, [error]);

    useEffect(() => {
        if (success) toast.info(success);
    }, [success]);

    const handleSearch = () => {
        if (search) {
            router.get(namespaceRoutes.search.url(), { search }, { preserveState: true, replace: true });
        } else {
            router.get(namespaceRoutes.index.url(), {}, { preserveState: true, replace: true });
        }
    };

    const handleDelete = useCallback((item: Namespace) => {
        toast.warning(`Are you sure you want to delete "${item.name}"?`, {
            description: 'This action cannot be undone.',
            action: {
                label: 'Delete',
                onClick: () => {
                    router.delete(namespaceRoutes.destroy.url({ namespace: item.id }), {
                        onSuccess: () => toast.success(`Namespace "${item.name}" has been deleted.`),
                        onError: () => toast.error('Failed to delete the namespace.'),
                    });
                },
            },
            cancel: { label: 'Cancel', onClick: () => {} },
            duration: 8000,
        });
    }, []);

    const handleReset = () => {
        setSearch('');
        setError(undefined);
        setSuccess(undefined);
        router.get(namespaceRoutes.index.url(), {}, { preserveState: true, replace: true });
    };

    const columns: ColumnDefinition<Namespace>[] = useMemo(
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
                                <Link href={namespaceRoutes.edit.url({ namespace: item.id })}>Edit</Link>
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

    const safePaginated: PaginatedResponse<Namespace> =
        namespaces ?? { data: [], meta: { total: 0, per_page: 0, current_page: 1, last_page: 1 } };

    const rows: Namespace[] = (safePaginated.data) ?safePaginated.data :safePaginated;
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Namespace" />
            <Toaster richColors position="top-right" />

            <div className="flex flex-col gap-4 p-4 lg:p-6">
                {((errors && Object.keys(errors).length > 0)) && (
                    <Alert variant="destructive" className="mx-auto w-full max-w-3xl">
                        <AlertTitle>{statusCode && statusCode !== 200 ? `Error ${statusCode}` : 'Error'}</AlertTitle>
                        <AlertDescription>
                            {errors && (
                                <ul className="list-disc pl-5 space-y-1">
                                    {Object.entries(errors).flatMap(([field, msgs]) =>
                                        msgs.map((msg, i) => (
                                            <li key={`${field}-${i}`}>
                                                {field}: {msg}
                                            </li>
                                        )),
                                    )}
                                </ul>
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                <FilterCard title="Filter Namespace" description="Filter namespace by name" className="mx-auto w-full max-w-lg">
                    <div className="flex w-full max-w-lg items-center">
                        <Input
                            type="text"
                            placeholder="Search namespace..."
                            className="flex-1"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                        />
                        <Button className="ml-2" onClick={handleSearch}>
                            Search
                        </Button>
                        <Button className="ml-2" variant="secondary" onClick={handleReset}>
                            Reset
                        </Button>
                    </div>
                </FilterCard>

                <div className="rounded-xl bg-white p-4 shadow-sm lg:p-6">
                    <div className="mb-4 flex items-center justify-end">
                        <Button asChild>
                            <Link href={namespaceRoutes.create.url()}>Create</Link>
                        </Button>
                    </div>

                    <div className="overflow-x-auto">
                        <DataTable columns={columns} data={rows} />
                    </div>

                </div>
            </div>
        </AppLayout>
    );
}
