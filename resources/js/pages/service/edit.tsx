import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import serviceRoutes from '@/routes/services';
import { Namespace, PaginatedResponse, Service } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { useMemo, useRef } from 'react';
import { Toaster } from 'sonner';
import { useFlash } from '@/hooks/use-flash';
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
  service: Service;
  namespaces: PaginatedResponse<Namespace> | Namespace[];
  flash?: {
    message?: string;
    error?: string;
    success?: string;
  };
};

export default function ServiceEditPage({ service, namespaces }: Props) {
  const nsOptions = useMemo(
    () => (isPaginated<Namespace>(namespaces) ? namespaces.data : namespaces),
    [namespaces]
  );

  const { props } = usePage<Props>();
  const { resetAll } = useFlash(props?.flash);

  const initial = useRef({
    name: service.name ?? '',
    namespace_id: service.namespace?.id ?? '',
  });

  const { data, setData, put, processing, errors, wasSuccessful, clearErrors } = useForm<{
    name: string;
    namespace_id: string | number | '';
  }>({
    name: initial.current.name,
    namespace_id: initial.current.namespace_id as string | number | '',
  });

  const [openNs, setOpenNs] = React.useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    put(serviceRoutes.update.url({ service: service.id }), {
      preserveScroll: true,
    });
  };

  const handleReset = () => {
    setData('name', initial.current.name);
    setData('namespace_id', initial.current.namespace_id);
    clearErrors();
    resetAll();
  };

  const isDirty =
    String(data.name) !== String(initial.current.name) ||
    String(data.namespace_id ?? '') !== String(initial.current.namespace_id ?? '');

  const isDisabled =
    processing ||
    !String(data.name).trim() ||
    !String(data.namespace_id).trim() ||
    !isDirty;

  const selectedNs = nsOptions.find((ns) => String(ns.id) === String(data.namespace_id));

  return (
    <AppLayout>
      <Head title="Edit Service" />
      <Toaster richColors position="top-right" />
      <div className="p-4 lg:p-6">
        <div className="mx-auto max-w-xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
          <h1 className="text-xl font-semibold">Edit Service</h1>
          <p className="mb-4 text-muted-foreground">Update this detail below.</p>

          <form onSubmit={handleSubmit} className="space-y-4">
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
              <Popover open={openNs} onOpenChange={setOpenNs}>
                <PopoverTrigger asChild>
                  <Button
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={openNs}
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
                                setOpenNs(false);
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

            <div className="flex items-center justify-between">
              <Button asChild variant="ghost">
                <Link href={serviceRoutes.index.url()}>Cancel</Link>
              </Button>
              <div className="flex gap-2">
                <Button type="button" variant="outline" onClick={handleReset} disabled={!isDirty || processing}>
                  Reset
                </Button>
                <Button type="submit" disabled={isDisabled}>
                  {processing ? 'Saving...' : 'Save'}
                </Button>
              </div>
            </div>

            {wasSuccessful && (
              <p className="text-sm text-green-600 dark:text-green-400">Saved successfully.</p>
            )}
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
