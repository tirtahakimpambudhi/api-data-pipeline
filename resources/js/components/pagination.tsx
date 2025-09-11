import { Link } from '@inertiajs/react';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationProps {
    links: PaginationLink[];
    className?: string;
}

export default function Pagination({ links, className = '' }: PaginationProps) {
    if (!links || links.length <= 3) {
        return null;
    }

    return (
        <nav className={className}>
            <ul className="inline-flex -space-x-px text-sm">
                {links.map((link, index) => {
                    const linkClassName = `
                        flex h-10 items-center justify-center border border-gray-300 px-4 leading-tight 
                        ${index === 0 ? 'rounded-l-lg' : ''} 
                        ${index === links.length - 1 ? 'rounded-r-lg' : ''}
                        ${
                            link.active
                                ? 'z-10 border-blue-400 bg-blue-50 text-blue-600'
                                : 'bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-700'
                        }
                        ${!link.url ? 'cursor-not-allowed bg-gray-100 text-gray-400' : ''}
                    `;

                    if (!link.url) {
                        return (
                            <li key={index}>
                                <div className={linkClassName} dangerouslySetInnerHTML={{ __html: link.label }} />
                            </li>
                        );
                    }

                    return (
                        <li key={index}>
                            <Link href={link.url} className={linkClassName} dangerouslySetInnerHTML={{ __html: link.label }} />
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
