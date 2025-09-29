import DataTable, { type ColumnDefinition } from '@/components/data-table';
import FilterCard from '@/components/filter-card';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import serviceEnvironmentRoute from '@/routes/service-environments';
import { PaginatedResponse, ServiceEnvironment, type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { toast, Toaster } from 'sonner';
import { useFlash } from '@/hooks/use-flash';
import axios from 'axios';
import { numberItemOnPage } from '@/lib/utils';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Service Environments', href: serviceEnvironmentRoute.index().url },
];

const formatDateTime = (iso?: string) => {
  if (!iso) return '–';
  try {
    return new Date(iso).toLocaleString();
  } catch {
    return '–';
  }
};


type PageProps = {
    serviceEnvironments: PaginatedResponse<ServiceEnvironment> | ServiceEnvironment[];
    filters?: { search?: string; page?: number; size?: number; };
    flash?: {
        message ?: string;
        error ?: string;
        success ?: string;
    }
}


export default function ServiceEnvironmentPage({
  serviceEnvironments,
  filters,
} : PageProps) {
  const isPaginated = (val: unknown): val is PaginatedResponse<ServiceEnvironment> =>
    !!val && typeof val === 'object' && 'data' in (val as any) && 'total' in (val as any);
    const { props } = usePage<PageProps>();
    const {resetAll} = useFlash(props?.flash);
  const initialSearch = (filters?.search ?? new URLSearchParams(window.location.search).get('search') ?? '') as string;
  const initialPage = (filters?.page ?? (isPaginated(serviceEnvironments) ? serviceEnvironments.current_page : 1)) as number;
  const initialSize = (filters?.size ?? (isPaginated(serviceEnvironments) ? serviceEnvironments.per_page : 10)) as number;

  const [search, setSearch] = useState<string>(initialSearch);
  const [currentPage, setCurrentPage] = useState<number>(initialPage);
  const [itemsPerPage, setItemsPerPage] = useState<number>(initialSize);

  useEffect(() => {
    if (isPaginated(serviceEnvironments)) {
      setCurrentPage(serviceEnvironments.current_page);
      setItemsPerPage(serviceEnvironments.per_page);
    }
  }, [JSON.stringify(isPaginated(serviceEnvironments) ? { p: serviceEnvironments.current_page, s: serviceEnvironments.per_page } : {})]);

  const tableData: ServiceEnvironment[] = useMemo(() => {
    if (isPaginated(serviceEnvironments)) return serviceEnvironments.data;
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    return serviceEnvironments.slice(startIndex, endIndex);
  }, [serviceEnvironments, currentPage, itemsPerPage]);

  const totalItems = isPaginated(serviceEnvironments) ? serviceEnvironments.total : (serviceEnvironments as ServiceEnvironment[]).length;

  const handleSearch = () => {
    const params: Record<string, string | number> = { page: 1, size: itemsPerPage };
    if (search) params.search = search;
    router.get(search ? serviceEnvironmentRoute.search().url : serviceEnvironmentRoute.index().url, params, {
      preserveState: true,
      replace: true,
      onError: () => toast.error('Failed to load data.'),
    });
  };

  const handleReset = () => {
    setSearch('');
    const params: Record<string, any> = { page: 1, size: itemsPerPage };
    resetAll();
    router.get(serviceEnvironmentRoute.index().url, params, {
      preserveState: true,
      replace: true,
    });
  };

  const handleDelete = useCallback((item: ServiceEnvironment) => {
        toast.warning(`Are you sure you want to delete "${item.name}"?`, {
            description: 'This action cannot be undone.',
            action: {
                label: 'Delete',
                onClick: async () => {
                    try {
                        const req = serviceEnvironmentRoute.destroy(item.id)
                        await axios({
                            url: req.url,
                            method: req.method,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        })

                        toast.success(`Service Environment "${item.name}" has been deleted.`)

                        router.reload({ only: ['serviceEnvironments'] })
                    } catch (err: any) {
                        const status = err?.response?.status
                        const msg = err?.response?.data?.message
                        console.log(err)
                        if (status >= 400 && status < 500) {
                            toast.error(msg)
                        } else if (status >= 500) {
                            toast.error(msg || 'Internal server error while deleting.')
                        } else {
                            toast.error('Failed to delete the Service Environment.')
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
    const params: Record<string, string | number> = { page, size: itemsPerPage };
    if (search) params.search = search;
    router.get(search ? serviceEnvironmentRoute.search().url : serviceEnvironmentRoute.index().url, params, {
      preserveState: true,
      replace: true,
    });
  };

    const numberItem = numberItemOnPage(
        isPaginated(serviceEnvironments)
            ? serviceEnvironments.current_page
            : currentPage,
        isPaginated(serviceEnvironments)
            ? serviceEnvironments.per_page
            : itemsPerPage,
    );

    const columns: ColumnDefinition<ServiceEnvironment>[] = useMemo(
    () => [
      { header: 'No', align: 'left', render: (item, index) => numberItem(index)},
      { header: 'Service', align: 'left', render: (item) => item.service?.name ?? '–' },
      { header: 'Environment', align: 'left', render: (item) => item.environment?.name ?? '–' },
      { header: 'Created At', align: 'left', render: (item) => formatDateTime(item.created_at) },
      { header: 'Updated At', align: 'left', render: (item) => formatDateTime(item.updated_at) },
      {
        header: 'Actions',
        align: 'right',
        render: (item) => (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0" aria-label={`Open menu for ID ${item.id}`}>
                <MoreVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem asChild>
                <Link href={serviceEnvironmentRoute.edit({ service_environment: item.id }).url}>Edit</Link>
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
    [numberItem, handleDelete]
  );

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Service Environments" />
        <Toaster richColors position="top-right" />

      <div className="flex flex-col gap-4 p-4 lg:p-6">
        <FilterCard title="Filter Service Environment" description="Filter by service or environment" className="mx-auto w-full max-w-2xl">
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
            <Button variant="outline" onClick={handleReset}>Reset</Button>
          </div>
        </FilterCard>

        <div className="rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
          <div className="mb-4 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
            <div className="text-sm text-muted-foreground">
              {isPaginated(serviceEnvironments)
                ? (
                  <span>
                    Showing <strong>{tableData.length}</strong> of <strong>{serviceEnvironments.total}</strong> items
                  </span>
                ) : (
                  <span>
                    Total items: <strong>{(serviceEnvironments as ServiceEnvironment[]).length}</strong>
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
                  router.get(search ? serviceEnvironmentRoute.search().url : serviceEnvironmentRoute.index().url, params, {
                    preserveState: true,
                    replace: true,
                  });
                }}
              >
                {[5, 10, 20, 50].map((n) => (
                  <option key={n} value={n}>{n}</option>
                ))}
              </select>
              <Button asChild>
                <Link href={serviceEnvironmentRoute.create().url}>Create</Link>
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
