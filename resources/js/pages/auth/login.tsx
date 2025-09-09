import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'; 
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/react';
import { Cog, LoaderCircle } from 'lucide-react'; 

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    return (
        <Card className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <Head title="Log in" />
            <Card className="w-full max-w-sm">
                <CardHeader className="text-center">
                    <CardTitle>Welcome Back</CardTitle>
                    <CardDescription>Enter your credentials to access your account.</CardDescription>
                </CardHeader>

                <CardContent>
                    {status && <div className="mb-4 text-sm font-medium text-green-600">{status}</div>}
                    <Form {...AuthenticatedSessionController.store.form()} resetOnSuccess={['password']}>
                        {({ processing, errors }) => (
                            <div className="grid gap-4">
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
                        )}
                    </Form>
                </CardContent>

                <CardFooter className="flex justify-center text-sm">
                    <p className="text-muted-foreground">
                        Don't have an account?{' '}
                        <TextLink href={register()} className="font-semibold">
                            Sign up
                        </TextLink>
                    </p>
                </CardFooter>
            </Card>
        </Card>
    );
}