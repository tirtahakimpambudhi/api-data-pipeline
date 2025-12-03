import DataTable, { type ColumnDefinition } from '@/components/data-table';
import FilterCard from '@/components/filter-card';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { useFlash } from '@/hooks/use-flash';
import AppLayout from '@/layouts/app-layout';
import { numberItemOnPage } from '@/lib/utils';
import rolesRoute from '@/routes/roles';
import { PaginatedResponse, type BreadcrumbItem, Role } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { MoreVertical } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast, Toaster } from 'sonner';



const breadcrumbs: BreadcrumbItem[] = [{ title: 'Roles', href: rolesRoute.index().url }];

type PageProps = {
    roles: PaginatedResponse<Role> | Role[];
    filters?: { search?: string; page?: number; size?: number };
    flash?: {
        message?: string;
        error?: string;
        success?: string;
    };
};

const formatDateTime = (iso?: string) => {
    if (!iso) return '–';
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return '–';
    }
};

export default function RolePage({ roles, filters }: PageProps) {
    const isPaginated = (val: unknown): val is PaginatedResponse<Role> =>
        !!val && typeof val === 'object' && 'data' in (val as any) && 'total' in (val as any);

    const { props } = usePage<PageProps>();
    const { resetAll } = useFlash(props?.flash);

    const initialSearch = (filters?.search ?? new URLSearchParams(window.location.search).get('search') ?? '') as string;
    const initialPage = (filters?.page ?? (isPaginated(roles) ? roles.current_page : 1)) as number;
    const initialSize = (filters?.size ?? (isPaginated(roles) ? roles.per_page : 10)) as number;

    const [search, setSearch] = useState<string>(initialSearch);
    const [currentPage, setCurrentPage] = useState<number>(initialPage);
    const [itemsPerPage, setItemsPerPage] = useState<number>(initialSize);

    useEffect(() => {
        if (isPaginated(roles)) {
            setCurrentPage(roles.current_page);
            setItemsPerPage(roles.per_page);
        }
    }, [JSON.stringify(isPaginated(roles) ? { p: roles.current_page, s: roles.per_page } : {})]);

    const tableData: Role[] = useMemo(() => {
        if (isPaginated(roles)) return roles.data;
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        return (roles as Role[]).slice(startIndex, endIndex);
    }, [roles, currentPage, itemsPerPage]);

    const totalItems = isPaginated(roles) ? roles.total : (roles as Role[]).length;

    const handleSearch = () => {
        const params: Record<string, string | number> = { page: 1, size: itemsPerPage };
        if (search) params.search = search;
        router.get(search ? rolesRoute.search().url : rolesRoute.index().url, params, {
            preserveState: true,
            replace: true,
            onError: () => toast.error('Failed to load data.'),
        });
    };

    const handleReset = () => {
        setSearch('');
        const params: Record<string, any> = { page: 1, size: itemsPerPage };
        resetAll();
        router.get(rolesRoute.index().url, params, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDelete = useCallback((item: Role) => {
        toast.warning(`Are you sure you want to delete role "${item.name}"?`, {
            description: 'This action cannot be undone.',
            action: {
                label: 'Delete',
                onClick: async () => {
                    try {
                        const req = rolesRoute.destroy(item.id);
                        await axios({
                            url: req.url,
                            method: req.method,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        });

                        toast.success(`Role "${item.name}" has been deleted.`);

                        router.reload({ only: ['roles'] });
                    } catch (err: any) {
                        const status = err?.response?.status;
                        const msg = err?.response?.data?.message;
                        if (status >= 400 && status < 500) {
                            toast.error(msg);
                        } else if (status >= 500) {
                            toast.error(msg || 'Internal server error while deleting.');
                        } else {
                            toast.error('Failed to delete the role.');
                        }
                    }
                },
            },
            cancel: { label: 'Cancel', onClick: () => {} },
            duration: 8000,
        });
    }, []);

    const onPageChange = (page: number) => {
        setCurrentPage(page);
        const params: Record<string, string | number> = { page, size: itemsPerPage };
        if (search) params.search = search;
        router.get(search ? rolesRoute.search().url : rolesRoute.index().url, params, {
            preserveState: true,
            replace: true,
        });
    };

    const numberItem = numberItemOnPage(isPaginated(roles) ? roles.current_page : currentPage, isPaginated(roles) ? roles.per_page : itemsPerPage);

    const columns: ColumnDefinition<Role>[] = useMemo(
        () => [
            {
                header: 'No',
                align: 'left',
                render: (item, index) => numberItem(index),
            },
            {
                header: 'Role',
                align: 'left',
                render: (item) => item.name,
            },
            {
                header: 'Description',
                align: 'left',
                render: (item) => item.description ?? '–',
            },
            {
                header: 'Actions',
                align: 'right',
                render: (item) => (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-8 w-8 p-0" aria-label={`Open menu for role ${item.name}`}>
                                <MoreVertical className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            {/* Detail – untuk lihat permission di halaman terpisah */}
                            <DropdownMenuItem asChild>
                                <Link href={rolesRoute.show({ role: item.id }).url}>Detail</Link>
                            </DropdownMenuItem>

                            <DropdownMenuItem asChild>
                                <Link href={rolesRoute.edit({ role: item.id }).url}>Edit</Link>
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
        [numberItem, handleDelete],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles" />
            <Toaster richColors position="top-right" />

            <div className="flex flex-col gap-4 p-4 lg:p-6">
                <FilterCard title="Filter Roles" description="Filter by name or description" className="mx-auto w-full max-w-2xl">
                    <div className="flex w-full items-center gap-2">
                        <Input
                            type="text"
                            placeholder="Search..."
                            className="flex-1"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                        />
                        <Button onClick={handleSearch}>Search</Button>
                        <Button variant="outline" onClick={handleReset}>
                            Reset
                        </Button>
                    </div>
                </FilterCard>

                <div className="rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <div className="mb-4 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                        <div className="text-sm text-muted-foreground">
                            {isPaginated(roles) ? (
                                <span>
                                    Showing <strong>{tableData.length}</strong> of <strong>{roles.total}</strong> items
                                </span>
                            ) : (
                                <span>
                                    Total items: <strong>{(roles as Role[]).length}</strong>
                                </span>
                            )}
                        </div>
                        <div className="flex items-center gap-2">
                            <label className="text-sm text-muted-foreground">Per page</label>
                            <select
                                className="h-9 rounded-md border px-2 text-sm dark:bg-muted dark:text-muted-foreground"
                                value={itemsPerPage}
                                onChange={(e) => {
                                    const size = Number(e.target.value);
                                    setItemsPerPage(size);
                                    const params: Record<string, any> = { page: 1, size };
                                    if (search) params.search = search;
                                    router.get(search ? rolesRoute.search().url : rolesRoute.index().url, params, {
                                        preserveState: true,
                                        replace: true,
                                    });
                                }}
                            >
                                {[5, 10, 20, 50].map((n) => (
                                    <option key={n} value={n}>
                                        {n}
                                    </option>
                                ))}
                            </select>
                            <Button asChild>
                                <Link href={rolesRoute.create().url}>Create</Link>
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
