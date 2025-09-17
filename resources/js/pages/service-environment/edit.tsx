import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useFlash } from '@/hooks/use-flash';
import AppLayout from '@/layouts/app-layout';
import serviceEnvironmentRoute from '@/routes/service-environments';
import { Environment, Service, ServiceEnvironment } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown } from 'lucide-react';
import React from 'react';
import { Toaster } from 'sonner';

type PageProps = {
    serviceEnvironment: ServiceEnvironment;
    services: Service[];
    environments: Environment[];
    flash?: { message?: string; error?: string; success?: string };
};

export default function ServiceEnvironmentEditPage({ serviceEnvironment, services, environments }: PageProps) {
    const initial = React.useRef({
        service_id: serviceEnvironment.service?.id ?? '',
        environment_id: serviceEnvironment.environment?.id ?? '',
    });

    const { props } = usePage<PageProps>();
    const { resetAll } = useFlash(props?.flash);

    const { data, setData, put, processing, errors } = useForm({
        service_id: initial.current.service_id as string | number | '',
        environment_id: initial.current.environment_id as string | number | '',
    });

    const [openService, setOpenService] = React.useState(false);
    const [openEnv, setOpenEnv] = React.useState(false);

    const selectedService = services.find((s) => String(s.id) === String(data.service_id));
    const selectedEnv = environments.find((e) => String(e.id) === String(data.environment_id));

    const serviceLabel = (s: Service) => s.full_name ?? s.name;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(serviceEnvironmentRoute.update({ service_environment: serviceEnvironment.id }).url, {
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        setData('service_id', initial.current.service_id);
        setData('environment_id', initial.current.environment_id);
        resetAll();
    };

    const isDirty =
        String(data.service_id ?? '') !== String(initial.current.service_id ?? '') ||
        String(data.environment_id ?? '') !== String(initial.current.environment_id ?? '');

    const isDisabled = processing || !String(data.service_id).trim() || !String(data.environment_id).trim() || !isDirty;

    return (
        <AppLayout>
            <Head title="Edit Service Environment" />
            <Toaster richColors position="top-right" />
            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-xl rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <h1 className="text-xl font-semibold">Edit Service Environment</h1>
                    <p className="mb-4 text-muted-foreground">Update this detail below.</p>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="mb-1 block font-medium">Service</label>
                            <Popover open={openService} onOpenChange={setOpenService}>
                                <PopoverTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={openService}
                                        className={`w-full justify-between ${!selectedService ? 'text-muted-foreground' : ''} ${
                                            errors.service_id ? 'border-destructive focus-visible:ring-destructive' : ''
                                        }`}
                                        disabled={processing}
                                    >
                                        {selectedService ? serviceLabel(selectedService) : 'Select service'}
                                        <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-[--radix-popover-trigger-width] p-0">
                                    <Command>
                                        <CommandInput placeholder="Search service..." />
                                        <CommandList>
                                            <CommandEmpty>No results found.</CommandEmpty>
                                            <CommandGroup>
                                                {services.map((srv) => {
                                                    const id = String(srv.id);
                                                    const label = serviceLabel(srv);
                                                    const isSelected = String(data.service_id) === id;
                                                    return (
                                                        <CommandItem
                                                            key={id}
                                                            value={label}
                                                            onSelect={() => {
                                                                setData('service_id', id);
                                                                setOpenService(false);
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
                            {errors.service_id && <p className="mt-1 text-sm text-destructive">{errors.service_id}</p>}
                        </div>

                        <div>
                            <label className="mb-1 block font-medium">Environment</label>
                            <Popover open={openEnv} onOpenChange={setOpenEnv}>
                                <PopoverTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={openEnv}
                                        className={`w-full justify-between ${!selectedEnv ? 'text-muted-foreground' : ''} ${
                                            errors.environment_id ? 'border-destructive focus-visible:ring-destructive' : ''
                                        }`}
                                        disabled={processing}
                                    >
                                        {selectedEnv ? selectedEnv.name : 'Select environment'}
                                        <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-[--radix-popover-trigger-width] p-0">
                                    <Command>
                                        <CommandInput placeholder="Search environment..." />
                                        <CommandList>
                                            <CommandEmpty>No results found.</CommandEmpty>
                                            <CommandGroup>
                                                {environments.map((env) => {
                                                    const id = String(env.id);
                                                    const isSelected = String(data.environment_id) === id;
                                                    return (
                                                        <CommandItem
                                                            key={id}
                                                            value={env.name}
                                                            onSelect={() => {
                                                                setData('environment_id', id);
                                                                setOpenEnv(false);
                                                            }}
                                                        >
                                                            <Check className={`mr-2 h-4 w-4 ${isSelected ? 'opacity-100' : 'opacity-0'}`} />
                                                            {env.name}
                                                        </CommandItem>
                                                    );
                                                })}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            {errors.environment_id && <p className="mt-1 text-sm text-destructive">{errors.environment_id}</p>}
                        </div>

                        <div className="flex items-center justify-between">
                            <Button asChild variant="ghost">
                                <Link href={serviceEnvironmentRoute.index().url}>Cancel</Link>
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
