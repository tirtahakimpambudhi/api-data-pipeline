import DataTable, { type ColumnDefinition } from '@/components/data-table';
import FilterCard from '@/components/filter-card';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/app-layout';
import namespaceRoutes from '@/routes/namespaces';
import { type BreadcrumbItem, type Namespace } from '@/types';
import { Head, Link, router, usePage  } from '@inertiajs/react';
import { destroy as destroyNamespace } from '@/routes/namespaces/index'
import { MoreVertical } from 'lucide-react';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import axios from 'axios'

import { toast, Toaster } from 'sonner';

type ErrorBag = Record<string, string[]>;

// Dukungan dua bentuk paginated
type MetaPaginated<T> = {
    data: T[];
    meta: { total: number; per_page: number; current_page: number; last_page?: number };
};
type FlatPaginated<T> = {
    data: T[];
    total: number;
    per_page: number;
    current_page: number;
};

type PaginatedLike<T> = MetaPaginated<T> | FlatPaginated<T>;

// Props gabungan (local + remote)
type Props = {
    namespaces: PaginatedLike<Namespace> | Namespace[];
    filters?: { search?: string; page?: number; size?: number } | Record<string, unknown>;
    errors?: ErrorBag | null;
    serverError?: string | null;
    statusCode?: number;
    flash?: {
        message?: string;
        success?: string;
        error?: string;
    };
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


const isArrayData = (val: unknown): val is Namespace[] => Array.isArray(val);

const hasMeta = <T,>(val: unknown): val is MetaPaginated<T> =>
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    !!val && typeof val === 'object' && 'data' in (val as any) && 'meta' in (val as any);

const isFlatPaginated = <T,>(val: unknown): val is FlatPaginated<T> =>
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    !!val && typeof val === 'object' && 'data' in (val as any) && 'total' in (val as any) && !('meta' in (val as any));

export default function NamespacePage({
                                          namespaces,
                                          filters,
                                          errors,
                                          serverError,
                                          statusCode,
                                      }: Props) {
    const { props } = usePage<Props>();


    const [errorFlash, setErrorFlash] = useState<string | undefined>(props.flash?.error);
    const [successFlash, setSuccessFlash] = useState<string | undefined>(
        props.flash?.success ?? props.flash?.message
    );


    const initialSearch =
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        (filters as any)?.search ??
        (typeof window !== 'undefined'
            ? new URLSearchParams(window.location.search).get('search') || ''
            : '');

    const derivedPage = hasMeta<Namespace>(namespaces)
        ? namespaces.meta.current_page
        : isFlatPaginated<Namespace>(namespaces)
            ? namespaces.current_page
            : 1;

    const derivedSize = hasMeta<Namespace>(namespaces)
        ? namespaces.meta.per_page
        : isFlatPaginated<Namespace>(namespaces)
            ? namespaces.per_page
            : 10;
// eslint-disable-next-line @typescript-eslint/no-explicit-any
    const [search, setSearch] = useState<string>(String((filters as any)?.search ?? initialSearch));
    const [currentPage, setCurrentPage] = useState<number>(
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        Number((filters as any)?.page ?? derivedPage)
    );
    const [itemsPerPage, setItemsPerPage] = useState<number>(
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        Number((filters as any)?.size ?? derivedSize)
    );

    // Toast server error sekali saat berubah (fitur local)
    useEffect(() => {
        if (serverError) toast.error(serverError);
    }, [serverError]);

    // Toast flash error/success (fitur local)
    useEffect(() => {
        if (errorFlash) toast.error(errorFlash);
    }, [errorFlash]);

    useEffect(() => {
        if (successFlash) toast.info(successFlash);
    }, [successFlash]);

    // Sinkronisasi page/size jika server mengirim paginated baru
    useEffect(() => {
        if (hasMeta<Namespace>(namespaces)) {
            setCurrentPage(namespaces.meta.current_page);
            setItemsPerPage(namespaces.meta.per_page);
        } else if (isFlatPaginated<Namespace>(namespaces)) {
            setCurrentPage(namespaces.current_page);
            setItemsPerPage(namespaces.per_page);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [
        JSON.stringify(
            hasMeta<Namespace>(namespaces)
                ? { p: namespaces.meta.current_page, s: namespaces.meta.per_page }
                : isFlatPaginated<Namespace>(namespaces)
                    ? { p: namespaces.current_page, s: namespaces.per_page }
                    : {}
        ),
    ]);

    const tableData: Namespace[] = useMemo(() => {
        if (hasMeta<Namespace>(namespaces)) return namespaces.data;
        if (isFlatPaginated<Namespace>(namespaces)) return namespaces.data;
        if (isArrayData(namespaces)) {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            return namespaces.slice(startIndex, endIndex);
        }
        return [];
    }, [namespaces, currentPage, itemsPerPage]);

    const totalItems: number = useMemo(() => {
        if (hasMeta<Namespace>(namespaces)) return namespaces.meta.total;
        if (isFlatPaginated<Namespace>(namespaces)) return namespaces.total;
        if (isArrayData(namespaces)) return namespaces.length;
        return 0;
    }, [namespaces]);

    // Actions
    const handleSearch = () => {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const params: Record<string, any> = { page: 1, size: itemsPerPage };
        if (search) params.search = search;
        router.get(search ? namespaceRoutes.search.url() : namespaceRoutes.index.url(), params, {
            preserveState: true,
            replace: true,
            onError: () => toast.error('Failed load data.'),
        });
    };

    const handleReset = () => {
        setSearch('');
        setErrorFlash(undefined);
        setSuccessFlash(undefined);
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const params: Record<string, any> = { page: 1, size: itemsPerPage };
        router.get(namespaceRoutes.index.url(), params, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDelete = useCallback((item: Namespace) => {
        toast.warning(`Are you sure you want to delete "${item.name}"?`, {
            description: 'This action cannot be undone.',
            action: {
                label: 'Delete',
                onClick: async () => {
                    try {
                        const req = destroyNamespace(item.id)
                        await axios({
                            url: req.url,
                            method: req.method,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        })

                        toast.success(`Namespace "${item.name}" has been deleted.`)

                        router.reload({ only: ['namespaces'] })
                    } catch (err: any) {
                        const status = err?.response?.status
                        const msg = err?.response?.data?.message
                        console.log(err)
                        if (status >= 400 && status < 500) {
                            toast.error(msg)
                        } else if (status >= 500) {
                            toast.error(msg || 'Internal server error while deleting.')
                        } else {
                            toast.error('Failed to delete the namespace.')
                        }
                    }
                },
            },
            cancel: { label: 'Cancel', onClick: () => {} },
            duration: 8000,
        })
    }, [])

    const onPageChange = (page: number) => {
        setCurrentPage(page);
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const params: Record<string, any> = { page, size: itemsPerPage };
        if (search) params.search = search;
        router.get(search ? namespaceRoutes.search.url() : namespaceRoutes.index.url(), params, {
            preserveState: true,
            replace: true,
        });
    };

    const onChangePerPage = (size: number) => {
        setItemsPerPage(size);
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const params: Record<string, any> = { page: 1, size };
        if (search) params.search = search;
        router.get(search ? namespaceRoutes.search.url() : namespaceRoutes.index.url(), params, {
            preserveState: true,
            replace: true,
        });
    };

    const columns: ColumnDefinition<Namespace>[] = useMemo(
        () => [
            { header: 'No', align: 'left', render: (_item, index) => index+1 },
            // eslint-disable-next-line @typescript-eslint/no-unused-vars
            { header: 'Name', align: 'left', render: (item, _) => item.name },
            // eslint-disable-next-line @typescript-eslint/no-unused-vars
            { header: 'Created At', align: 'left', render: (item, _) => formatDateTime(item.created_at) },
            // eslint-disable-next-line @typescript-eslint/no-unused-vars
            { header: 'Updated At', align: 'left', render: (item, _) => formatDateTime(item.updated_at) },
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
        [handleDelete]
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Namespace" />
            <Toaster richColors position="top-right" />

            <div className="flex flex-col gap-4 p-4 lg:p-6">
                {/* Alert error dari server (ErrorBag) — fitur local */}
                {(errors && Object.keys(errors).length > 0) && (
                    <Alert variant="destructive" className="mx-auto w-full max-w-3xl">
                        <AlertTitle>{statusCode && statusCode !== 200 ? `Error ${statusCode}` : 'Error'}</AlertTitle>
                        <AlertDescription>
                            <ul className="list-disc pl-5 space-y-1">
                                {Object.entries(errors).flatMap(([field, msgs]) =>
                                    msgs.map((msg, i) => (
                                        <li key={`${field}-${i}`}>
                                            {field}: {msg}
                                        </li>
                                    ))
                                )}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                <FilterCard title="Filter Namespace" description="Filter namespace by name" className="mx-auto w-full max-w-2xl">
                    <div className="flex w-full items-center gap-2">
                        <Input
                            type="text"
                            placeholder="Search namespace..."
                            className="flex-1"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                        />
                        <Button onClick={handleSearch}>Search</Button>
                        <Button variant="secondary" onClick={handleReset}>Reset</Button>
                    </div>
                </FilterCard>

                <div className="rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <div className="mb-4 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                        <div className="text-sm text-muted-foreground">
                            {hasMeta<Namespace>(namespaces) ? (
                                <span>
                  Showing <strong>{tableData.length}</strong> of <strong>{namespaces.meta.total}</strong> items
                </span>
                            ) : isFlatPaginated<Namespace>(namespaces) ? (
                                <span>
                  Showing <strong>{tableData.length}</strong> of <strong>{namespaces.total}</strong> items
                </span>
                            ) : (
                                <span>
                  Total items: <strong>{(namespaces as Namespace[]).length}</strong>
                </span>
                            )}
                        </div>
                        <div className="flex items-center gap-2">
                            <label className="text-sm text-muted-foreground">Per page</label>
                            <select
                                className="h-9 rounded-md border px-2 text-sm dark:bg-muted dark:text-muted-foreground"
                                value={itemsPerPage}
                                onChange={(e) => onChangePerPage(Number(e.target.value))}
                            >
                                {[5, 10, 20, 50].map((n) => (
                                    <option key={n} value={n}>{n}</option>
                                ))}
                            </select>
                            <Button asChild>
                                <Link href={namespaceRoutes.create.url()}>Create</Link>
                            </Button>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <DataTable columns={columns} data={tableData} />
                        <Pagination
                            className="mt-6 flex justify-center"
                            currentPage={currentPage}
                            totalItems={totalItems}
                            itemsPerPage={itemsPerPage}
                            onPageChange={onPageChange}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
