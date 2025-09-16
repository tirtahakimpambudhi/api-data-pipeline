import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/app-layout';
import namespaces from '@/routes/namespaces';
import type { Namespace } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import { toast, Toaster } from 'sonner';
import { useFlash } from '@/hooks/use-flash';

type Props = {
    namespace: Namespace;
};

type PageProps = {
    flash?: { error?: string; success?: string };
    serverError?: string;
};

export default function EditPage({ namespace }: Props) {
    const { props } = usePage<PageProps>();
    const initial = useRef({ name: namespace.name });

    const { data, setData, put, processing, errors, wasSuccessful, clearErrors } = useForm({
        name: namespace.name ?? '',
    });

    const {resetAll} = useFlash(props?.flash);
    const [serverError, setServerError] = useState<string | null>(props.serverError ?? null);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(namespaces.update.url({ namespace: namespace.id }), {
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        setData('name', initial.current.name ?? '');
        clearErrors()
        resetAll();
        setServerError(null);
    };

    const isDirty = data.name !== initial.current.name;
    const isDisabled = processing || !data.name.trim() || !isDirty;

    const topErrorMessages = useMemo(() => {
        const bag = Object.values(errors ?? {}).flat();
        const serverErr = serverError ? [serverError] : [];
        return [...serverErr, ...bag];
    }, [errors, serverError]);

    return (
        <AppLayout>
            <Head title="Edit Namespace" />
            <Toaster richColors theme="system"  position="top-right" />
            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-lg rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <h1 className="text-xl font-semibold">Edit Namespace</h1>
                    <p className="mb-4 text-muted-foreground">Update the details below.</p>


                    {topErrorMessages.length > 0 && (
                        <Alert variant="destructive" className="mb-4">
                            <AlertTitle>Error</AlertTitle>
                            <AlertDescription>
                                <ul className="list-disc pl-5 space-y-1">
                                    {topErrorMessages.map((msg, i) => (
                                        <li key={i}>{msg}</li>
                                    ))}
                                </ul>
                            </AlertDescription>
                        </Alert>
                    )}

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
                        </div>

                        <div className="flex items-center justify-between">
                            <Button asChild variant="ghost">
                                <Link href={namespaces.index.url()}>Cancel</Link>
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

                        {/* Optional: success kecil—silakan hapus bila tak diperlukan */}
                        {wasSuccessful && (
                            <p className="text-sm text-green-600 dark:text-green-400">Saved successfully.</p>
                        )}
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
