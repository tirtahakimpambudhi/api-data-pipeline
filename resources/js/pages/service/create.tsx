import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFlash } from '@/hooks/use-flash';
import AppLayout from '@/layouts/app-layout';
import serviceRoutes, { store } from '@/routes/services';
import { Namespace, PaginatedResponse } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { useMemo } from 'react';
import { Toaster } from 'sonner';
import {
  Popover,
  PopoverTrigger,
  PopoverContent,
} from '@/components/ui/popover';
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command';
import { ChevronsUpDown, Check } from 'lucide-react';

function isPaginated<T>(val: unknown): val is PaginatedResponse<T> {
  return !!val && typeof val === 'object' && 'data' in (val as object) && 'total' in (val as object);
}

type Props = {
  namespaces: PaginatedResponse<Namespace> | Namespace[];
  flash?: {
    message?: string;
    error?: string;
    success?: string;
  };
};

export default function CreatePage({ namespaces }: Props) {
  const nsOptions = useMemo(
    () => (isPaginated<Namespace>(namespaces) ? namespaces.data : namespaces),
    [namespaces]
  );

  const { data, setData, post, processing, errors, reset, clearErrors } = useForm<{
    name: string;
    namespace_id: string | number | '';
  }>({
    name: '',
    namespace_id: '',
  });

  const { props } = usePage<Props>();
  const { resetAll } = useFlash(props?.flash);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(store.url(), { preserveScroll: true });
  };

  const handleReset = () => {
    reset('name', 'namespace_id');
    resetAll();
    clearErrors();
  };

  const isDisabled = processing || !String(data.name).trim() || !String(data.namespace_id).trim();

  const selectedNs = nsOptions.find((ns) => String(ns.id) === String(data.namespace_id));

  return (
    <AppLayout>
      <Head title="Create Service" />
      <Toaster richColors position="top-right" />
      <div className="p-4 lg:p-6">
        <div className="mx-auto max-w-xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
          <div className="mb-4 flex items-center justify-between">
            <h1 className="text-xl font-semibold">Create New Service</h1>
          </div>

          <p className="mb-4 text-muted-foreground">Fill in the details below.</p>

          <form id="create-service-form" onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label htmlFor="name" className="mb-1 block font-medium">
                Name
              </label>
              <Input
                id="name"
                type="text"
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
                className={errors.name ? 'border-destructive' : ''}
                disabled={processing}
              />
              {errors.name && <p className="mt-1 text-sm text-destructive">{errors.name}</p>}
            </div>

            {/* Combobox: Namespace (search by NAME, store ID) */}
            <div>
              <label className="mb-1 block font-medium">Namespace</label>
              <Popover>
                <PopoverTrigger asChild>
                  <Button
                    type="button"
                    variant="outline"
                    role="combobox"
                    className={`w-full justify-between ${!selectedNs ? 'text-muted-foreground' : ''} ${errors.namespace_id ? 'border-destructive focus-visible:ring-destructive' : ''}`}
                    disabled={processing}
                  >
                    {selectedNs ? selectedNs.name : 'Select namespace'}
                    <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />
                  </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[--radix-popover-trigger-width] p-0">
                  <Command>
                    <CommandInput placeholder="Search namespace..." />
                    <CommandList>
                      <CommandEmpty>No results found.</CommandEmpty>
                      <CommandGroup>
                        {nsOptions.map((ns) => {
                          const id = String(ns.id);
                          const isSelected = String(data.namespace_id) === id;
                          return (
                            <CommandItem
                              key={id}
                              value={ns.name} 
                              onSelect={() => {
                                setData('namespace_id', id);
                              }}
                            >
                              <Check className={`mr-2 h-4 w-4 ${isSelected ? 'opacity-100' : 'opacity-0'}`} />
                              {ns.name}
                            </CommandItem>
                          );
                        })}
                      </CommandGroup>
                    </CommandList>
                  </Command>
                </PopoverContent>
              </Popover>
              {errors.namespace_id && (
                <p className="mt-1 text-sm text-destructive">{errors.namespace_id}</p>
              )}
            </div>

            <div className="flex items-center justify-end gap-2">
              <Button asChild variant="ghost" disabled={processing}>
                <Link href={serviceRoutes.index.url()}>Cancel</Link>
              </Button>
              <Button type="submit" disabled={isDisabled}>
                {processing ? 'Saving...' : 'Save'}
              </Button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
