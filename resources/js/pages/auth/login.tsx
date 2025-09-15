import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Form, Head, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import React, { useEffect } from 'react';
import { toast, Toaster } from 'sonner';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

type SharedPageProps = {
    flash?: {
        error?: string;
        success?: string;
    };
};

export default function Login({ status, canResetPassword }: LoginProps) {
    const { props } = usePage<SharedPageProps>();
    useEffect(() => {
        if (props.flash?.error) {
            toast.error(props.flash?.error);
        }
    }, [props.flash?.error]);
    return (
        <>
            <Toaster richColors position="top-center" closeButton />
            <Card className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
                <Head title="Log in" />
                <Card className="w-full max-w-sm">
                    <CardHeader className="text-center">
                        <CardTitle>Welcome Back</CardTitle>
                        <CardDescription>Enter your credentials to access your account.</CardDescription>
                    </CardHeader>

                    <CardContent>
                        {status && (
                            <Alert className="mb-4" variant="default">
                                <AlertTitle>Success</AlertTitle>
                                <AlertDescription>{status}</AlertDescription>
                            </Alert>
                        )}

                        {props.flash?.success && (
                            <Alert className="mb-4" variant="default">
                                <AlertTitle>Success</AlertTitle>
                                <AlertDescription>{props.flash.success}</AlertDescription>
                            </Alert>
                        )}

                        <Form {...AuthenticatedSessionController.store.form()} resetOnSuccess={['password']}>
                            {({ processing, errors }) => {
                                const generalErrorKeys = Object.keys(errors || {}).filter(
                                    (k) => k !== 'email' && k !== 'password'
                                );
                                const hasGeneralErrors = generalErrorKeys.length > 0;

                                return (
                                    <div className="grid gap-4">
                                        {/* Flash error umum */}
                                        {props.flash?.error && (
                                            <Alert variant="destructive" className="mb-2">
                                                <AlertTitle>Error</AlertTitle>
                                                <AlertDescription>{props.flash.error}</AlertDescription>
                                            </Alert>
                                        )}

                                        {/* Error non-field dari withErrors() */}
                                        {hasGeneralErrors && (
                                            <Alert variant="destructive" className="mb-2">
                                                <AlertTitle>Error</AlertTitle>
                                                <AlertDescription>
                                                    <ul className="list-disc pl-5 space-y-1">
                                                        {generalErrorKeys.map((k) => (
                                                            <li key={k}>{(errors as Record<string, string>)[k]}</li>
                                                        ))}
                                                    </ul>
                                                </AlertDescription>
                                            </Alert>
                                        )}

                                        <div className="grid gap-2">
                                            <Label htmlFor="email">Email</Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                name="email"
                                                placeholder="email@example.com"
                                                autoComplete="email"
                                                autoFocus
                                                required
                                                className={errors.email ? 'border-destructive' : ''}
                                            />
                                            <InputError message={errors.email} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="password">Password</Label>
                                            <Input
                                                id="password"
                                                type="password"
                                                name="password"
                                                placeholder="••••••••"
                                                autoComplete="current-password"
                                                required
                                                className={errors.password ? 'border-destructive' : ''}
                                            />
                                            <InputError message={errors.password} />
                                        </div>

                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center space-x-2">
                                                <Checkbox id="remember" name="remember" />
                                                <Label htmlFor="remember" className="text-sm font-normal">
                                                    Remember me
                                                </Label>
                                            </div>
                                            {canResetPassword && (
                                                <TextLink href={request()} className="text-sm">
                                                    Forgot password?
                                                </TextLink>
                                            )}
                                        </div>

                                        <Button type="submit" className="w-full" disabled={processing}>
                                            {processing ? (
                                                <>
                                                    <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                                    Signing In...
                                                </>
                                            ) : (
                                                'Log In'
                                            )}
                                        </Button>
                                    </div>
                                );
                            }}
                        </Form>
                    </CardContent>

                    <CardFooter className="flex justify-center text-sm">
                        <p className="text-muted-foreground">
                            Don&apos;t have an account?{' '}
                            <TextLink href={register()} className="font-semibold">
                                Sign up
                            </TextLink>
                        </p>
                    </CardFooter>
                </Card>
            </Card>
        </>
    );
}
