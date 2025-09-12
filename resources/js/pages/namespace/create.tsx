import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/app-layout';
import namespaces, { store } from '@/routes/namespaces';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { useEffect, useMemo, useState } from 'react';
import { toast, Toaster } from 'sonner';

type PageProps = {
    flash?: { error?: string; success?: string };
    serverError?: string;
};

export default function CreatePage() {
    const { props } = usePage<PageProps>();

    const { data, setData, post, processing, errors, reset, wasSuccessful,clearErrors } = useForm({
        name: '',
    });
    const [flashError, setFlashError] = useState<string | null>(props.flash?.error ?? null);
    const [serverError, setServerError] = useState<string | null>(props.serverError ?? null);


    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store.url(), { preserveScroll: true });
    };

    const handleReset = () => {
        reset('name');
        clearErrors()
        setFlashError(null);
        setServerError(null);
    };

    const isDirty = data.name;
    const isDisabled = processing || !data.name.trim() || !isDirty;
    useEffect(() => {
        if (flashError) {
            toast.error(flashError);
        }
    }, [flashError]);
    const topErrorMessages = useMemo(() => {
        const bag = Object.values(errors ?? {}).flat();
        const serverErr = serverError ? [serverError] : [];
        return [...serverErr, ...bag];
    }, [errors, serverError]);

    return (
        <AppLayout>
            <Head title="Create Namespace" />
            <Toaster richColors position="top-center" />
            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-lg rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <div className="mb-4 flex items-center justify-between">
                        <h1 className="text-xl font-semibold">Create New Namespace</h1>
                        <div className="flex gap-2">
                            <Button type="button" variant="outline" onClick={handleReset} disabled={!isDirty || processing}>
                                Reset
                            </Button>
                            <Button type="submit" form="create-form" disabled={isDisabled}>
                                {processing ? 'Saving...' : 'Save'}
                            </Button>
                        </div>
                    </div>

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

                    <p className="mb-4 text-muted-foreground">Fill in the details below.</p>

                    <form id="create-form" onSubmit={handleSubmit} className="space-y-4">
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
                            {/* Tampilkan error validasi di bawah input */}
                            {errors.name && (
                                <p className="mt-1 text-sm text-destructive">{errors.name}</p>
                            )}
                        </div>

                        <div className="flex items-center justify-end">
                            <Button asChild variant="ghost">
                                <Link href={namespaces.index.url()}>Cancel</Link>
                            </Button>
                        </div>

                        {wasSuccessful && (
                            <p className="text-sm text-green-600 dark:text-green-400">
                                Saved successfully.
                            </p>
                        )}
                    </form>

                </div>
            </div>
        </AppLayout>
    );
}
