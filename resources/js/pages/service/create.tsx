import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import serviceRoutes, { store } from '@/routes/services';
import { Namespace, PaginatedResponse } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { useEffect, useMemo, useState } from 'react';
import { toast, Toaster } from 'sonner';

function isPaginated<T>(val: unknown): val is PaginatedResponse<T> {

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
  return !!val && typeof val === 'object' && 'data' in (val as any) && 'total' in (val as any);
}

type Props = {
    namespaces: PaginatedResponse<Namespace> | Namespace[];
    flash?: {
        message ?: string;
        error ?: string;
        success ?: string;
    }
}

export default function CreatePage({ namespaces }: Props) {
  const nsOptions = useMemo(() => (isPaginated<Namespace>(namespaces) ? namespaces.data : namespaces), [namespaces]);
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const { data, setData, post, processing, errors, reset, wasSuccessful, clearErrors } = useForm({
    name: '',
    namespace_id: '' as string | number | '',
  });

  const {props} = usePage<Props>();

  const [errorFlash, setErrorFlash] = useState<string | undefined>(props.flash?.error);
  const [successFlash, setSuccessFlash] = useState<string | undefined>(
        props.flash?.success ?? props.flash?.message
    );
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(store.url(), { preserveScroll: true });
  };

  const handleReset = () => {
    reset('name', 'namespace_id');
    setErrorFlash(undefined);
    setSuccessFlash(undefined);
    clearErrors();
  };

    useEffect(() => {
        if (errorFlash) toast.error(errorFlash);
    }, [errorFlash]);

    useEffect(() => {
        if (successFlash) toast.info(successFlash);
    }, [successFlash]);

  const isDirty = data.name !== '' || data.namespace_id !== '';
  const isDisabled = processing || !String(data.name).trim() || !String(data.namespace_id).trim();

  return (
    <AppLayout>
      <Head title="Create Service" />
        <Toaster richColors theme="system" position="top-right" />
      <div className="p-4 lg:p-6">
        <div className="mx-auto max-w-xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
          <div className="mb-4 flex items-center justify-between">
            <h1 className="text-xl font-semibold">Create New Service</h1>
            <div className="flex gap-2">
              <Button type="button" variant="outline" onClick={handleReset} disabled={!isDirty || processing}>
                Reset
              </Button>
              <Button type="submit" form="create-service-form" disabled={isDisabled}>
                {processing ? 'Saving...' : 'Save'}
              </Button>
            </div>
          </div>

          <p className="mb-4 text-muted-foreground">Lengkapi detail di bawah ini.</p>

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

            <div>
              <label className="mb-1 block font-medium">Namespace</label>
              <Select
                value={String(data.namespace_id)}
                onValueChange={(val) => setData('namespace_id', val)}
                disabled={processing}
              >
                <SelectTrigger className={errors.namespace_id ? 'border-destructive' : ''}>
                  <SelectValue placeholder="Pilih namespace" />
                </SelectTrigger>
                <SelectContent>
                  {nsOptions.map((ns) => (
                    <SelectItem key={ns.id} value={String(ns.id)}>
                      {ns.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
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
