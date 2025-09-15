import DataTable, { type ColumnDefinition } from '@/components/data-table';
import FilterCard from '@/components/filter-card';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import channelRoutes from '@/routes/channels';
import { type BreadcrumbItem, type Channel, Environment, type PaginatedResponse } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast, Toaster } from 'sonner';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import environmentRoutes from '@/routes/environments';
import axios from 'axios';
import { useFlash } from '@/hooks/use-flash';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Channel', href: channelRoutes.index.url() },
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
    channels: PaginatedResponse<Channel> | Channel[];
    filters?: { search?: string; page?: number; size?: number };
    errors?: Record<string, string[]> | null;
    flash?: {
        message ?: string;
        error ?: string;
        success ?: string;
    }
}

export default function channelPage({
  channels,
  filters,
}: Props ) {
  const isPaginated = (val: unknown): val is PaginatedResponse<Channel> =>
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
    !!val && typeof val === 'object' && 'data' in (val as any) && 'total' in (val as any);
    const { props } = usePage<Props>();
    const {resetAll} = useFlash(props?.flash);
    const [errors, setErrors] = useState(props.errors);
  const initialSearch = (filters?.search ?? new URLSearchParams(window.location.search).get('search') ?? '') as string;
  const initialPage = (filters?.page ?? (isPaginated(channels) ? channels.current_page : 1)) as number;
  const initialSize = (filters?.size ?? (isPaginated(channels) ? channels.per_page : 10)) as number;

  const [search, setSearch] = useState<string>(initialSearch);
  const [currentPage, setCurrentPage] = useState<number>(initialPage);
  const [itemsPerPage, setItemsPerPage] = useState<number>(initialSize);

  useEffect(() => {
    if (isPaginated(channels)) {
      setCurrentPage(channels.current_page);
      setItemsPerPage(channels.per_page);
    }
  }, [
    JSON.stringify(
      isPaginated(channels)
        ? { p: channels.current_page, s: channels.per_page }
        : {}
    ),
  ]);

  const tableData: Channel[] = useMemo(() => {
    if (isPaginated(channels)) return channels.data;
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    return channels.slice(startIndex, endIndex);
  }, [channels, currentPage, itemsPerPage]);

  const totalItems = isPaginated(channels) ? channels.total : (channels as Channel[]).length;

  const handleSearch = () => {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const params: Record<string, any> = { page: 1, size: itemsPerPage };
    if (search) params.search = search;
    router.get(search ? channelRoutes.search.url() : channelRoutes.index.url(), params, {
      preserveState: true,
      replace: true,
      onError: () => toast.error('Failed load data.'),
    });
  };

  const handleReset = () => {
    setSearch('');
    resetAll();
    setErrors({});
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const params: Record<string, any> = { page: 1, size: itemsPerPage };
    router.get(channelRoutes.index.url(), params, {
      preserveState: true,
      replace: true,
    });
  };


    const handleDelete = useCallback((item: Channel) => {
        toast.warning(`Are you sure you want to delete "${item.name}"?`, {
            description: 'This action cannot be undone.',
            action: {
                label: 'Delete',
                onClick: async () => {
                    try {
                        const req = channelRoutes.destroy(item.id)
                        await axios({
                            url: req.url,
                            method: req.method,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        })

                        toast.success(`Channels "${item.name}" has been deleted.`)

                        router.reload({ only: ['channels'] })
                    } catch (err: any) {
                        const status = err?.response?.status
                        const msg = err?.response?.data?.message
                        console.log(err)
                        if (status >= 400 && status < 500) {
                            toast.error(msg)
                        } else if (status >= 500) {
                            toast.error(msg || 'Internal server error while deleting.')
                        } else {
                            toast.error('Failed to delete the channel.')
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
    router.get(search ? channelRoutes.search.url() : channelRoutes.index.url(), params, {
      preserveState: true,
      replace: true,
    });
  };

  const columns: ColumnDefinition<Channel>[] = useMemo(
    () => [
      { header: 'No', align: 'left', render: (item, index) => index+1 },
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
                <Link href={channelRoutes.edit.url({ channel: item.id })}>Edit</Link>
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
      <Head title="channel" />
      <Toaster richColors theme="system" position="top-right" />

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
        <FilterCard title="Filter channel" description="Filter channel by name" className="mx-auto w-full max-w-2xl">
          <div className="flex w-full items-center gap-2">
            <Input
              type="text"
              placeholder="Search channel..."
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
              {isPaginated(channels) ? (
                <span>
                  Showing <strong>{tableData.length}</strong> of <strong>{channels.total}</strong> items
                </span>
              ) : (
                <span>
                  Total items: <strong>{(channels as Channel[]).length}</strong>
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
                  router.get(search ? channelRoutes.search.url() : channelRoutes.index.url(), params, {
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
                <Link href={channelRoutes.create.url()}>Create</Link>
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
