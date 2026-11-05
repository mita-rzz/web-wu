import AppLogoIcon from '@/components/app-logo-icon';
import { Link } from '@inertiajs/react';

interface AuthLayoutProps {
    children: React.ReactNode;
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="bg-background min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 md:p-10">
            <div className="w-full max-w-lg rounded-2xl border border-border bg-card p-8 sm:p-10 shadow-2xl">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-3 text-center">
                        <Link href={route('home')} className="flex flex-col items-center gap-2 group">
                            <div className="flex h-14 w-14 items-center justify-center rounded-2xl">
                                <AppLogoIcon className="size-9 fill-current text-foreground" />
                            </div>
                            <span className="sr-only">{title}</span>
                        </Link>

                        <div className="flex flex-col gap-1">
                            <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
                            {description && <p className="text-muted-foreground text-sm">{description}</p>}
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
