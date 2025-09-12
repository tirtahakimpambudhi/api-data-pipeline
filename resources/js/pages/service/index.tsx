import DataTable, { type ColumnDefinition } from '@/components/data-table';
import FilterCard from '@/components/filter-card';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import serviceRoutes from '@/routes/services';
import { PaginatedResponse, Service, type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast, Toaster } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Service', href: serviceRoutes.index.url() },
];

const formatDateTime = (iso?: string) => {
  if (!iso) return '–';
  try {
    return new Date(iso).toLocaleString();
  } catch {
    return '–';
  }
};

export default function ServicePage({
  services,
  filters,
}: {
  services: PaginatedResponse<Service> | Service[];
  filters?: { search?: string; page?: number; size?: number };
}) {
  const isPaginated = (val: unknown): val is PaginatedResponse<Service> =>
    !!val && typeof val === 'object' && 'data' in (val as any) && 'total' in (val as any);

  const initialSearch = (filters?.search ?? new URLSearchParams(window.location.search).get('search') ?? '') as string;
  const initialPage = (filters?.page ?? (isPaginated(services) ? services.current_page : 1)) as number;
  const initialSize = (filters?.size ?? (isPaginated(services) ? services.per_page : 10)) as number;

  const [search, setSearch] = useState<string>(initialSearch);
  const [currentPage, setCurrentPage] = useState<number>(initialPage);
  const [itemsPerPage, setItemsPerPage] = useState<number>(initialSize);

  useEffect(() => {
    if (isPaginated(services)) {
      setCurrentPage(services.current_page);
      setItemsPerPage(services.per_page);
    }
  }, [JSON.stringify(isPaginated(services) ? { p: services.current_page, s: services.per_page } : {})]);

  const tableData: Service[] = useMemo(() => {
    if (isPaginated(services)) return services.data;
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    return services.slice(startIndex, endIndex);
  }, [services, currentPage, itemsPerPage]);

  const totalItems = isPaginated(services) ? services.total : (services as Service[]).length;

  const handleSearch = () => {
    const params: Record<string, any> = { page: 1, size: itemsPerPage };
    if (search) params.search = search;
    router.get(search ? serviceRoutes.search.url() : serviceRoutes.index.url(), params, {
      preserveState: true,
      replace: true,
      onError: () => toast.error('Failed load data.'),
    });
  };

  const handleReset = () => {
    setSearch('');
    const params: Record<string, any> = { page: 1, size: itemsPerPage };
    router.get(serviceRoutes.index.url(), params, {
      preserveState: true,
      replace: true,
    });
  };

  const handleDelete = useCallback((item: Service) => {
    toast.warning(`Delete \"${item.name}\"?`, {
      description: 'This action cannot be undone.',
      action: {
        label: 'Delete',
        onClick: () => {
          router.delete(serviceRoutes.destroy.url({ service: item.id }), {
            onSuccess: () => toast.success(`Service \"${item.name}\" deleted.`),
            onError: () => toast.error('Failed delte service.'),
          });
        },
      },
      cancel: { label: 'Cancel', onClick: () => {} },
      duration: 8000,
    });
  }, []);

  const onPageChange = (page: number) => {
    setCurrentPage(page);
    const params: Record<string, any> = { page, size: itemsPerPage };
    if (search) params.search = search;
    router.get(search ? serviceRoutes.search.url() : serviceRoutes.index.url(), params, {
      preserveState: true,
      replace: true,
    });
  };

  const columns: ColumnDefinition<Service>[] = useMemo(
    () => [
      { header: 'ID', align: 'left', render: (item) => item.id },
      { header: 'Name', align: 'left', render: (item) => item.name },
      { header: 'Namespace', align: 'left', render: (item) => item.namespace?.name ?? '–' },
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
                <Link href={serviceRoutes.edit.url({ service: item.id })}>Edit</Link>
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
      <Head title="Service" />
      <Toaster richColors theme="system" position="top-center" />

      <div className="flex flex-col gap-4 p-4 lg:p-6">
        <FilterCard title="Filter Service" description="Filter service by name" className="mx-auto w-full max-w-2xl">
          <div className="flex w-full items-center gap-2">
            <Input
              type="text"
              placeholder="Search service..."
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
              {isPaginated(services)
                ? (
                  <span>
                    Showing <strong>{tableData.length}</strong> of <strong>{services.total}</strong> items
                  </span>
                ) : (
                  <span>
                    Total items: <strong>{(services as Service[]).length}</strong>
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
                  router.get(search ? serviceRoutes.search.url() : serviceRoutes.index.url(), params, {
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
                <Link href={serviceRoutes.create.url()}>Create</Link>
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
