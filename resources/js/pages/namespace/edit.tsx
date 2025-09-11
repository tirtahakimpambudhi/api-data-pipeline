import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import namespaces from '@/routes/namespaces';
import type { Namespace } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import React, { useRef } from 'react';

type Props = {
    namespace: Namespace;
};

export default function EditPage({ namespace }: Props) {
    const initial = useRef({ name: namespace.name });

    const { data, setData, put, processing, errors, wasSuccessful } = useForm({
        name: namespace.name ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(namespaces.update.url({ namespace: namespace.id }), {
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        setData('name', initial.current.name ?? '');
    };

    const isDirty = data.name !== initial.current.name;
    const isDisabled = processing || !data.name.trim() || !isDirty;

    return (
        <AppLayout>
            <Head title="Edit Namespace" />
            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-lg rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <h1 className="text-xl font-semibold">Edit Namespace</h1>
                    <p className="mb-4 text-muted-foreground">Update the details below.</p>

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

                        {wasSuccessful && <p className="text-sm text-green-600 dark:text-green-400">Saved successfully.</p>}
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
