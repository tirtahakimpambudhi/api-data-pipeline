import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import channels, { store } from '@/routes/channels';
import { Head, Link, useForm } from '@inertiajs/react';
import React from 'react';

export default function CreatePage() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store.url(), { preserveScroll: true });
    };

    const handleReset = () => {
        reset('name');
    };

    const isDirty = data.name !== '';
    const isDisabled = processing || !data.name.trim();

    return (
        <AppLayout>
            <Head title="Create channel" />
            <div className="p-4 lg:p-6">
                <div className="mx-auto max-w-lg rounded-xl border bg-card p-4 text-card-foreground shadow-sm lg:p-6">
                    <div className="mb-4 flex items-center justify-between">
                        <h1 className="text-xl font-semibold">Create New channel</h1>
                        <div className="flex gap-2">
                            <Button type="button" variant="outline" onClick={handleReset} disabled={!isDirty || processing}>
                                Reset
                            </Button>
                            <Button type="submit" form="create-form" disabled={isDisabled}>
                                {processing ? 'Saving...' : 'Save'}
                            </Button>
                        </div>
                    </div>

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
                            {errors.name && <p className="mt-1 text-sm text-destructive">{errors.name}</p>}
                        </div>

                        <div className="flex items-center justify-end">
                            <Button asChild variant="ghost">
                                <Link href={channels.index.url()}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
