import DataTable, { type ColumnDefinition } from '@/components/data-table';
import FilterCard from '@/components/filter-card';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import channelRoutes from '@/routes/channels';
import { type BreadcrumbItem, type Channel, type PaginatedResponse } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast, Toaster } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'channel', href: channelRoutes.index.url() },
];

const formatDateTime = (iso?: string) => {
  if (!iso) return '–';
  try {
    return new Date(iso).toLocaleString();
  } catch {
    return '–';
  }
};

export default function channelPage({
  channels,
  filters,
}: {
  channels: PaginatedResponse<Channel> | Channel[];
  filters?: { search?: string; page?: number; size?: number };
}) {
  const isPaginated = (val: unknown): val is PaginatedResponse<Channel> =>
    !!val && typeof val === 'object' && 'data' in (val as any) && 'total' in (val as any);

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
    const params: Record<string, any> = { page: 1, size: itemsPerPage };
    router.get(channelRoutes.index.url(), params, {
      preserveState: true,
      replace: true,
    });
  };

  const handleDelete = useCallback((item: Channel) => {
    toast.warning(`Delete "${item.name}"?`, {
      description: 'This action cannot be undone.',
      action: {
        label: 'Delete',
        onClick: () => {
          router.delete(channelRoutes.destroy.url({ channel: item.id }), {
            onSuccess: () => toast.success(`channel "${item.name}" deleted.`),
            onError: () => toast.error('Failed delete channel.'),
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
    router.get(search ? channelRoutes.search.url() : channelRoutes.index.url(), params, {
      preserveState: true,
      replace: true,
    });
  };

  const columns: ColumnDefinition<Channel>[] = useMemo(
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
      <Toaster richColors theme="system" position="top-center" />

      <div className="flex flex-col gap-4 p-4 lg:p-6">
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
