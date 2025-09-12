import DataTable, { type ColumnDefinition } from '@/components/data-table';
import FilterCard from '@/components/filter-card';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import namespaceRoutes from '@/routes/namespaces';
import { type BreadcrumbItem, type Namespace, type PaginatedResponse } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast, Toaster } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Namespace', href: namespaceRoutes.index.url() },
];

const formatDateTime = (iso?: string) => {
  if (!iso) return '–';
  try {
    return new Date(iso).toLocaleString();
  } catch {
    return '–';
  }
};

export default function NamespacePage({
  namespaces,
  filters,
}: {
  namespaces: PaginatedResponse<Namespace> | Namespace[];
  filters?: { search?: string; page?: number; size?: number };
}) {
  const isPaginated = (val: unknown): val is PaginatedResponse<Namespace> =>
    !!val && typeof val === 'object' && 'data' in (val as any) && 'total' in (val as any);

  const initialSearch = (filters?.search ?? new URLSearchParams(window.location.search).get('search') ?? '') as string;
  const initialPage = (filters?.page ?? (isPaginated(namespaces) ? namespaces.current_page : 1)) as number;
  const initialSize = (filters?.size ?? (isPaginated(namespaces) ? namespaces.per_page : 10)) as number;

  const [search, setSearch] = useState<string>(initialSearch);
  const [currentPage, setCurrentPage] = useState<number>(initialPage);
  const [itemsPerPage, setItemsPerPage] = useState<number>(initialSize);

  useEffect(() => {
    if (isPaginated(namespaces)) {
      setCurrentPage(namespaces.current_page);
      setItemsPerPage(namespaces.per_page);
    }
  }, [
    JSON.stringify(
      isPaginated(namespaces)
        ? { p: namespaces.current_page, s: namespaces.per_page }
        : {}
    ),
  ]);

  const tableData: Namespace[] = useMemo(() => {
    if (isPaginated(namespaces)) return namespaces.data;
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    return namespaces.slice(startIndex, endIndex);
  }, [namespaces, currentPage, itemsPerPage]);

  const totalItems = isPaginated(namespaces) ? namespaces.total : (namespaces as Namespace[]).length;

  const handleSearch = () => {
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
    const params: Record<string, any> = { page: 1, size: itemsPerPage };
    router.get(namespaceRoutes.index.url(), params, {
      preserveState: true,
      replace: true,
    });
  };

  const handleDelete = useCallback((item: Namespace) => {
    toast.warning(`Delete "${item.name}"?`, {
      description: 'This action cannot be undone.',
      action: {
        label: 'Delete',
        onClick: () => {
          router.delete(namespaceRoutes.destroy.url({ namespace: item.id }), {
            onSuccess: () => toast.success(`Namespace "${item.name}" deleted.`),
            onError: () => toast.error('Failed delete namespace.'),
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
    router.get(search ? namespaceRoutes.search.url() : namespaceRoutes.index.url(), params, {
      preserveState: true,
      replace: true,
    });
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
    [handleDelete]
  );

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Namespace" />
      <Toaster richColors theme="system" position="top-center" />

      <div className="flex flex-col gap-4 p-4 lg:p-6">
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
            <Button variant="outline" onClick={handleReset}>Reset</Button>
          </div>
        </FilterCard>

        <div className="rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
          <div className="mb-4 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
            <div className="text-sm text-muted-foreground">
              {isPaginated(namespaces) ? (
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
                onChange={(e) => {
                  const size = Number(e.target.value);
                  setItemsPerPage(size);
                  const params: Record<string, any> = { page: 1, size };
                  if (search) params.search = search;
                  router.get(search ? namespaceRoutes.search.url() : namespaceRoutes.index.url(), params, {
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
