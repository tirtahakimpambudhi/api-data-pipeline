import DataTable, { type ColumnDefinition } from '@/components/data-table';
import FilterCard from '@/components/filter-card';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import environmentRoutes from '@/routes/environments';
import { Environment, type BreadcrumbItem, type PaginatedResponse } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import  { useCallback, useEffect, useMemo, useState } from 'react';
import { toast, Toaster } from 'sonner';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import axios from 'axios';
import { useFlash } from '@/hooks/use-flash';
import { numberItemOnPage } from '@/lib/utils';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Environment', href: environmentRoutes.index.url() },
];

const formatDateTime = (iso?: string) => {
  if (!iso) return '–';
  try {
    return new Date(iso).toLocaleString();
  } catch {
    return '–';
  }
};

type Props = {
    environments: PaginatedResponse<Environment> | Environment[];
    filters?: { search?: string; page?: number; size?: number };
    errors?: Record<string, string[]> | null;
    flash?: {
        message ?: string;
        error ?: string;
        success ?: string;
    }
}

export default function environmentPage({
  environments,
  filters,
    errors,
}: Props ) {
  const isPaginated = (val: unknown): val is PaginatedResponse<Environment> =>
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
      !!val && typeof val === 'object' && 'data' in (val as any) && 'total' in (val as any);
    const { props } = usePage<Props>();
    const {resetAll} = useFlash(props?.flash);
    const [_, setErrors] = useState(props.errors);
  const initialSearch = (filters?.search ?? new URLSearchParams(window.location.search).get('search') ?? '') as string;
  const initialPage = (filters?.page ?? (isPaginated(environments) ? environments.current_page : 1)) as number;
  const initialSize = (filters?.size ?? (isPaginated(environments) ? environments.per_page : 10)) as number;

  const [search, setSearch] = useState<string>(initialSearch);
  const [currentPage, setCurrentPage] = useState<number>(initialPage);
  const [itemsPerPage, setItemsPerPage] = useState<number>(initialSize);

  useEffect(() => {
    if (isPaginated(environments)) {
      setCurrentPage(environments.current_page);
      setItemsPerPage(environments.per_page);
    }
  }, [
    JSON.stringify(
      isPaginated(environments)
        ? { p: environments.current_page, s: environments.per_page }
        : {}
    ),
  ]);

  const tableData: Environment[] = useMemo(() => {
    if (isPaginated(environments)) return environments.data;
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    return environments.slice(startIndex, endIndex);
  }, [environments, currentPage, itemsPerPage]);

  const totalItems = isPaginated(environments) ? environments.total : (environments as Environment[]).length;

  const handleSearch = () => {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const params: Record<string, any> = { page: 1, size: itemsPerPage };
    if (search) params.search = search;
    router.get(search ? environmentRoutes.search.url() : environmentRoutes.index.url(), params, {
      preserveState: true,
      replace: true,
      onError: () => toast.error('Failed load data.'),
    });
  };

  const handleReset = () => {
    setSearch('');
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const params: Record<string, any> = { page: 1, size: itemsPerPage };
      resetAll()
      setErrors({});
    router.get(environmentRoutes.index.url(), params, {
      preserveState: true,
      replace: true,
    });

  };

    const handleDelete = useCallback((item: Environment) => {
        toast.warning(`Are you sure you want to delete "${item.name}"?`, {
            description: 'This action cannot be undone.',
            action: {
                label: 'Delete',
                onClick: async () => {
                    try {
                        const req = environmentRoutes.destroy(item.id)
                        await axios({
                            url: req.url,
                            method: req.method,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        })

                        toast.success(`Environments "${item.name}" has been deleted.`)

                        router.reload({ only: ['environments'] })
                    } catch (err: any) {
                        const status = err?.response?.status
                        const msg = err?.response?.data?.message
                        console.log(err)
                        if (status >= 400 && status < 500) {
                            toast.error(msg)
                        } else if (status >= 500) {
                            toast.error(msg || 'Internal server error while deleting.')
                        } else {
                            toast.error('Failed to delete the environment.')
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
    router.get(search ? environmentRoutes.search.url() : environmentRoutes.index.url(), params, {
      preserveState: true,
      replace: true,
    });
  };


    const numberItem = numberItemOnPage(
        isPaginated(environments)
            ? environments.current_page
            : currentPage,
        isPaginated(environments)
            ? environments.per_page
            : itemsPerPage,
    );
  const columns: ColumnDefinition<Environment>[] = useMemo(
    () => [
      { header: 'No', align: 'left', render: (item, index) => numberItem(index)},
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
    [numberItem,handleDelete]
  );

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="environment" />
        <Toaster richColors position="top-right" />

      <div className="flex flex-col gap-4 p-4 lg:p-6">
          {(errors && Object.keys(errors).length > 0) && (
              <Alert variant="destructive" className="mx-auto w-full max-w-3xl">
                  <AlertTitle>Error</AlertTitle>
                  <AlertDescription>
                      <ul className="list-disc pl-5 space-y-1">
                          {Object.entries(errors).flatMap(([field, messages]) =>
                              messages.map((msg :string, i :number) => (
                                  <li key={`${field}-${i}`}>
                                      {field}: {msg}
                                  </li>
                              ))
                          )}
                      </ul>
                  </AlertDescription>
              </Alert>
          )}
        <FilterCard title="Filter environment" description="Filter environment by name" className="mx-auto w-full max-w-2xl">
          <div className="flex w-full items-center gap-2">
            <Input
              type="text"
              placeholder="Search environment..."
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
              {isPaginated(environments) ? (
                <span>
                  Showing <strong>{tableData.length}</strong> of <strong>{environments.total}</strong> items
                </span>
              ) : (
                <span>
                  Total items: <strong>{(environments as Environment[]).length}</strong>
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
                    // eslint-disable-next-line @typescript-eslint/no-explicit-any
                  const params: Record<string, any> = { page: 1, size };
                  if (search) params.search = search;
                  router.get(search ? environmentRoutes.search.url() : environmentRoutes.index.url(), params, {
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
                <Link href={environmentRoutes.create.url()}>Create</Link>
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
