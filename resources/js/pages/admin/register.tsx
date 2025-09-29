import RegisteredAdminController from '@/actions/App/Http/Controllers/Auth/RegisteredAdminController';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';
import { Form, Head, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useFlash } from '@/hooks/use-flash';
import { Toaster } from 'sonner';


type Props = {
    flash?: {
        error?: string;
        success?: string;
        message?: string;
    };
}

export default function Register({flash} : Props) {
    const {props} = usePage<Props>();
    const {errorFlash, messageFlash} = useFlash(props?.flash ?? flash);
    console.log(errorFlash);
    console.log(messageFlash);
    return (
        <>
            <Toaster richColors position="top-right" />
            <Card className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
                <Head title="Register" />
                <Card className="w-full max-w-sm">
                    <CardHeader className="text-center">
                        <CardTitle>Create an Account</CardTitle>
                        <CardDescription>Enter your details below to create account.</CardDescription>
                    </CardHeader>

                    <CardContent>
                        <Form {...RegisteredAdminController.store.form()} resetOnSuccess={['password', 'password_confirmation']}>
                            {({ processing, errors }) => (
                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            type="text"
                                            placeholder="Your full name"
                                            autoComplete="name"
                                            autoFocus
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input id="email" name="email" type="email" placeholder="email@example.com" autoComplete="email" required />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">Password</Label>
                                        <Input
                                            id="password"
                                            name="password"
                                            type="password"
                                            placeholder="••••••••"
                                            autoComplete="new-password"
                                            required
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation">Confirm Password</Label>
                                        <Input
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            type="password"
                                            placeholder="••••••••"
                                            autoComplete="new-password"
                                            required
                                        />
                                        <InputError message={errors.password_confirmation} />
                                    </div>

                                    <Button type="submit" className="w-full" disabled={processing}>
                                        {processing ? (
                                            <>
                                                <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                                Creating Account...
                                            </>
                                        ) : (
                                            'Create Account'
                                        )}
                                    </Button>
                                </div>
                            )}
                        </Form>
                    </CardContent>

                    <CardFooter className="flex justify-center text-sm">
                        <p className="text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink href={login()} className="font-semibold">
                                Log in
                            </TextLink>
                        </p>
                    </CardFooter>
                </Card>
            </Card>
        </>
    );
}
