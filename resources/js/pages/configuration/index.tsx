import DataTable, { type ColumnDefinition } from '@/components/data-table';
import FilterCard from '@/components/filter-card';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import configurationRoute from '@/routes/configurations/index';
import { PaginatedResponse, Configuration, BreadcrumbItem, Filters } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { toast, Toaster } from 'sonner';
import { useFlash } from '@/hooks/use-flash';
import axios from 'axios';
import { numberItemOnPage } from '@/lib/utils';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Configurations', href: configurationRoute.index().url }];

const formatDateTime = (iso?: string | null) => {
  if (!iso) return '–';
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? '–' : d.toLocaleString();
};


function isPaginated<T>(val: object): val is PaginatedResponse<T> {
  if (!val || typeof val !== 'object') return false;
  const v = val as any;
  return (
    Array.isArray(v.data) &&
    typeof v.total === 'number' &&
    typeof v.current_page === 'number' &&
    typeof v.per_page === 'number'
  );
}

export type PageProps = {
  configurations: PaginatedResponse<Configuration> | Configuration[];
  filters?: Filters;
};

export type InertiaPageProps = {
  flash?: { message?: string; error?: string, success?: string };
};


function buildUrl(base: string, params: Record<string, string | number>) {
  const qs = new URLSearchParams(Object.entries(params).map(([k, v]) => [k, String(v)])).toString();
  return qs ? `${base}?${qs}` : base;
}

export default function ConfigurationIndexPage({ configurations, filters }: PageProps) {
  const { flash } = usePage<InertiaPageProps>().props;

  const { resetAll } = useFlash(flash);

  const [search, setSearch] = useState<string>(() =>
    (filters?.search ?? (typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('search') ?? '' : '')) as string
  );

  const [currentPage, setCurrentPage] = useState<number>(() =>
    isPaginated<Configuration>(configurations)
      ? configurations.current_page
      : (filters?.page ?? 1)
  );

  const [itemsPerPage, setItemsPerPage] = useState<number>(() =>
    isPaginated<Configuration>(configurations)
      ? configurations.per_page
      : (filters?.size ?? 10)
  );

  useEffect(() => {
    if (isPaginated<Configuration>(configurations)) {
      setCurrentPage(configurations.current_page);
      setItemsPerPage(configurations.per_page);
    }
  }, [configurations]);

  const tableData: Configuration[] = useMemo(() => {
    if (isPaginated<Configuration>(configurations)) return configurations.data;
    const start = (currentPage - 1) * itemsPerPage;
    return configurations.slice(start, start + itemsPerPage);
  }, [configurations, currentPage, itemsPerPage]);

  const rows = useMemo(() =>
    tableData.map((c) => ({
      ...c,
      _serviceName: c?.service_environment?.service?.full_name ?? '–',
      _envName: c?.service_environment?.environment?.name ?? '–',
      _channelName: c?.channel?.name ?? '–',
    })),
  [tableData]);

  const totalItems = isPaginated<Configuration>(configurations)
    ? configurations.total
    : (configurations as Configuration[]).length;

  const buildParams = (page: number) => {
    const p: Record<string, string | number> = { page, size: itemsPerPage };
    const q = search.trim();
    if (q) p.search = q;
    return p;
  };

  const fetchList = (page: number) => {
    const params = buildParams(page);
    const base = (params.search ? configurationRoute.search().url : configurationRoute.index().url);
    const urlWithQuery = buildUrl(base, params);

    router.visit(urlWithQuery, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      onError: () => toast.error('Failed to load data.'),
    });
  };

  const handleSearch = () => {
    setCurrentPage(1);
    fetchList(1);
  };

  const handleReset = () => {
    setSearch('');
    setCurrentPage(1);
    resetAll();
    const params = { page: 1, size: itemsPerPage } as Record<string, string | number>;
    const urlWithQuery = buildUrl(configurationRoute.index().url, params);
    router.visit(urlWithQuery, { preserveState: true, preserveScroll: true, replace: true });
  };

    const handleDelete = useCallback((item: Configuration) => {
        toast.warning(`Are you sure you want to delete "${item.name}"?`, {
            description: 'This action cannot be undone.',
            action: {
                label: 'Delete',
                onClick: async () => {
                    try {
                        const req = configurationRoute.destroy(item.id)
                        await axios({
                            url: req.url,
                            method: req.method,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        })

                        toast.success(`Configuration "${item.name}" has been deleted.`)

                        router.reload({ only: ['configurations'] })
                    } catch (err: any) {
                        const status = err?.response?.status
                        const msg = err?.response?.data?.message
                        console.log(err)
                        if (status >= 400 && status < 500) {
                            toast.error(msg)
                        } else if (status >= 500) {
                            toast.error(msg || 'Internal server error while deleting.')
                        } else {
                            toast.error('Failed to delete the Configuration.')
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
    fetchList(page);
  };
    const numberItem = numberItemOnPage(
        isPaginated(configurations)
            ? configurations.current_page
            : currentPage,
        isPaginated(configurations)
            ? configurations.per_page
            : itemsPerPage,
    );
  const columns: ColumnDefinition<Configuration>[] = useMemo(
    () => [
      { header: 'No', align: 'left', render: (item, index) => numberItem(index) },
      {
        header: 'Service',
        align: 'left',
        render: (item) => (item as any)._serviceName,
      },
      {
        header: 'Environment',
        align: 'left',
        render: (item) => (item as any)._envName,
      },
      {
        header: 'Channel',
        align: 'left',
        render: (item) => (item as any)._channelName,
      },
      { header: 'Created At', align: 'left', render: (item) => formatDateTime((item as any).created_at) },
      { header: 'Updated At', align: 'left', render: (item) => formatDateTime((item as any).updated_at) },
      {
        header: 'Actions',
        align: 'right',
        render: (item) => (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0" aria-label={`Open menu for ID ${(item as any).id}`}>
                <MoreVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem asChild>
                <Link href={configurationRoute.edit({ configuration: (item as any).id }).url}>Edit</Link>
              </DropdownMenuItem>
              <DropdownMenuItem
                className="text-red-600 focus:bg-red-50 focus:text-red-500"
                onSelect={(e) => {
                  e.preventDefault();
                  handleDelete(item as any);
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
      <Head title="Configuration" />
        <Toaster richColors position="top-right" />

      <div className="flex flex-col gap-4 p-4 lg:p-6">
        <FilterCard
          title="Filter Configuration"
          description="Filter configuration by service, environment, or channel"
          className="mx-auto w-full max-w-3xl"
        >
          <div className="flex w-full flex-col gap-3 md:flex-row md:items-center">
            <Input
              type="text"
              placeholder="Search…"
              className="flex-1"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
              aria-label="Search"
            />
            <div className="flex gap-2">
              <Button onClick={handleSearch}>Search</Button>
              <Button variant="outline" onClick={handleReset}>
                Reset
              </Button>
            </div>
          </div>
        </FilterCard>

        <div className="rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
          <div className="mb-4 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
            <div className="text-sm text-muted-foreground">
              {isPaginated(configurations) ? (
                <span>
                  Showing <strong>{rows.length}</strong> of <strong>{(configurations as PaginatedResponse<Configuration>).total}</strong> items
                </span>
              ) : (
                <span>
                  Total items: <strong>{(configurations as Configuration[]).length}</strong>
                </span>
              )}
            </div>
            <div className="flex items-center gap-2">
              <label className="text-sm text-muted-foreground" htmlFor="per-page">Per page</label>
              <select
                id="per-page"
                className="h-9 rounded-md border px-2 text-sm dark:bg-muted dark:text-muted-foreground"
                value={itemsPerPage}
                onChange={(e) => {
                  const size = Number(e.target.value);
                  setItemsPerPage(size);
                  setCurrentPage(1);
                  const params = { ...buildParams(1), size } as Record<string, number | string>;
                  const base = (params.search ? configurationRoute.search().url : configurationRoute.index().url);
                  const urlWithQuery = buildUrl(base, params);
                  router.visit(urlWithQuery, { preserveState: true, preserveScroll: true, replace: true });
                }}
              >
                {[5, 10, 20, 50].map((n) => (
                  <option key={n} value={n}>
                    {n}
                  </option>
                ))}
              </select>
              <Button asChild>
                <Link href={configurationRoute.create().url}>Create</Link>
              </Button>
            </div>
          </div>

          <div className="overflow-x-auto">
            <DataTable columns={columns} data={rows} />
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
