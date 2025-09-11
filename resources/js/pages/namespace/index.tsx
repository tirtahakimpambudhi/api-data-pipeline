import DataTable, { type ColumnDefinition } from '@/components/data-table';
import FilterCard from '@/components/filter-card';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import namespaces, { edit } from '@/routes/namespaces';
import { type BreadcrumbItem, type Namespace, type PaginatedResponse } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import { toast, Toaster } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Namespace', href: namespaces.index.url() }];

const dummyNamespaces: PaginatedResponse<Namespace> = {
    data: [
        { id: 1, name: 'Frontend Applications', created_at: '2023-01-15T10:30:00Z', updated_at: '2023-01-15T10:30:00Z' },
        { id: 2, name: 'Backend Services', created_at: '2023-02-20T14:00:00Z', updated_at: '2023-02-22T09:00:00Z' },
        { id: 3, name: 'Data Analytics Platform', created_at: '2023-03-10T11:20:00Z', updated_at: '2023-03-10T11:20:00Z' },
        { id: 4, name: 'Mobile Services', created_at: '2023-04-05T16:45:00Z', updated_at: '2023-04-06T18:00:00Z' },
        { id: 5, name: 'Payment Gateway', created_at: '2023-05-01T08:00:00Z', updated_at: '2023-05-01T08:00:00Z' },
    ],
    links: [
        { url: null, label: '&laquo; Previous', active: false },
        { url: '/?page=1', label: '1', active: true },
        { url: '/?page=2', label: '2', active: false },
        { url: '/?page=3', label: '3', active: false },
        { url: '/?page=2', label: 'Next &raquo;', active: false },
    ],
    current_page: 1,
    last_page: 3,
    from: 1,
    to: 5,
    per_page: 5,
    total: 15,
    first_page_url: '/?page=1',
    last_page_url: '/?page=3',
    next_page_url: '/?page=2',
    prev_page_url: null,
    path: '/',
};

const formatDateTime = (iso?: string) => {
    if (!iso) return '–';
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return '–';
    }
};

export default function NamespacePage() {
    const [search, setSearch] = useState('');

    const handleDelete = useCallback((item: Namespace) => {
        toast.warning(`Are you sure you want to delete "${item.name}"?`, {
            description: 'This action cannot be undone.',
            action: {
                label: 'Delete',
                onClick: () => toast.success(`Namespace "${item.name}" has been deleted.`),
            },
            cancel: { label: 'Cancel', onClick: () => {} },
            duration: 8000,
        });
    }, []);

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
                                <Link href={edit(item.id)}>Edit</Link>
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Namespace" />
            <Toaster richColors position="top-center" />

            <div className="flex flex-col gap-4 p-4 lg:p-6">
                <FilterCard title="Filter Namespace" description="Filter namespace by name" className="mx-auto w-full max-w-lg">
                    <div className="flex w-full max-w-lg items-center">
                        <Input
                            type="text"
                            placeholder="Search namespace..."
                            className="flex-1"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                        <Button className="ml-2">Search</Button>
                    </div>
                </FilterCard>

                <div className="rounded-xl bg-white p-4 shadow-sm lg:p-6">
                    <div className="mb-4 flex items-center justify-end">
                        <Button asChild>
                            <Link href={namespaces.create.url()}>Create</Link>
                        </Button>
                    </div>

                    <div className="overflow-x-auto">
                        <DataTable columns={columns} data={dummyNamespaces.data} />
                    </div>

                    <Pagination links={dummyNamespaces.links} className="mt-6 flex justify-center" />
                </div>
            </div>
        </AppLayout>
    );
}
