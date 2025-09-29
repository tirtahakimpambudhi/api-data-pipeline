import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import configurationRoute from '@/routes/configurations/index';
import { Channel, ServiceEnvironment } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React from 'react';
import { useFlash } from '@/hooks/use-flash';
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

type PageProps = {
  serviceEnvironments: ServiceEnvironment[];
  channels: Channel[];
  flash?: { message?: string; error?: string; success?: string };
};

export default function CreateConfigurationPage({
  serviceEnvironments,
  channels,
}: PageProps) {
  const { flash } = usePage<PageProps>().props;
  const { resetAll } = useFlash(flash);

  const { data, setData, post, processing, errors, reset, clearErrors } = useForm<{
    service_environment_id: string | number | '';
    channel_id: string | number | '';
  }>({
    service_environment_id: '',
    channel_id: '',
  });

  const [openSe, setOpenSe] = React.useState(false);
  const [openCh, setOpenCh] = React.useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(configurationRoute.store().url, { preserveScroll: true });
  };

  const handleReset = () => {
    reset('service_environment_id', 'channel_id');
    clearErrors();
    resetAll();
  };

  const isDirty = data.service_environment_id !== '' || data.channel_id !== '';
  const isDisabled =
    processing ||
    !String(data.service_environment_id).trim() ||
    !String(data.channel_id).trim();

  const seLabel = (se: ServiceEnvironment): string => {
    const serviceName =
      se.service?.full_name ?? se.service?.name ?? `Service #${se.service_id}`;
    const envName = se.environment?.name ?? `Env #${se.environment_id}`;
    return `${serviceName} / ${envName}`;
  };

  const selectedSe = serviceEnvironments.find(
    (se) => String(se.id) === String(data.service_environment_id),
  );
  const selectedCh = channels.find(
    (ch) => String(ch.id) === String(data.channel_id),
  );

  return (
    <AppLayout>
      <Head title="Create Configuration" />
      <Toaster richColors position="top-right" />
      <div className="p-4 lg:p-6">
        <div className="mx-auto max-w-xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
          <div className="mb-4 flex items-center justify-between">
            <h1 className="text-xl font-semibold">Create Configuration</h1>
          </div>

          {flash?.error && (
            <div className="mb-4 rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">
              {flash.error}
            </div>
          )}

          <p className="mb-4 text-muted-foreground">
            Fill in this detail below.
          </p>

          <form id="create-configuration-form" onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="mb-1 block font-medium">Service / Environment</label>
              <Popover open={openSe} onOpenChange={setOpenSe}>
                <PopoverTrigger asChild>
                  <Button
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={openSe}
                    className={`w-full justify-between ${!selectedSe ? 'text-muted-foreground' : ''} ${errors.service_environment_id ? 'border-destructive focus-visible:ring-destructive' : ''}`}
                    disabled={processing}
                  >
                    {selectedSe ? seLabel(selectedSe) : 'Select service / environment'}
                    <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />
                  </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[--radix-popover-trigger-width] p-0">
                  <Command>
                    <CommandInput placeholder="Search service / environment..." />
                    <CommandList>
                      <CommandEmpty>No results found.</CommandEmpty>
                      <CommandGroup>
                        {serviceEnvironments.map((se) => {
                          const id = String(se.id);
                          const label = seLabel(se);
                          const isSelected = String(data.service_environment_id) === id;
                          return (
                            <CommandItem
                              key={id}
                              value={label}
                              onSelect={() => {
                                setData('service_environment_id', id); 
                                setOpenSe(false);
                              }}
                            >
                              <Check className={`mr-2 h-4 w-4 ${isSelected ? 'opacity-100' : 'opacity-0'}`} />
                              {label}
                            </CommandItem>
                          );
                        })}
                      </CommandGroup>
                    </CommandList>
                  </Command>
                </PopoverContent>
              </Popover>
              {errors.service_environment_id && (
                <p className="mt-1 text-sm text-destructive">{errors.service_environment_id}</p>
              )}
            </div>

            <div>
              <label className="mb-1 block font-medium">Channel</label>
              <Popover open={openCh} onOpenChange={setOpenCh}>
                <PopoverTrigger asChild>
                  <Button
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={openCh}
                    className={`w-full justify-between ${!selectedCh ? 'text-muted-foreground' : ''} ${errors.channel_id ? 'border-destructive focus-visible:ring-destructive' : ''}`}
                    disabled={processing}
                  >
                    {selectedCh ? selectedCh.name : 'Select channel'}
                    <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />
                  </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[--radix-popover-trigger-width] p-0">
                  <Command>
                    <CommandInput placeholder="Search channel..." />
                    <CommandList>
                      <CommandEmpty>No results found.</CommandEmpty>
                      <CommandGroup>
                        {channels.map((ch) => {
                          const id = String(ch.id);
                          const isSelected = String(data.channel_id) === id;
                          return (
                            <CommandItem
                              key={id}
                              value={ch.name} 
                              onSelect={() => {
                                setData('channel_id', id);
                                setOpenCh(false);
                              }}
                            >
                              <Check className={`mr-2 h-4 w-4 ${isSelected ? 'opacity-100' : 'opacity-0'}`} />
                              {ch.name}
                            </CommandItem>
                          );
                        })}
                      </CommandGroup>
                    </CommandList>
                  </Command>
                </PopoverContent>
              </Popover>
              {errors.channel_id && (
                <p className="mt-1 text-sm text-destructive">{errors.channel_id}</p>
              )}
            </div>

            <div className="flex items-center justify-between">
              <Button asChild variant="ghost">
                <Link href={configurationRoute.index.url()}>Cancel</Link>
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
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
