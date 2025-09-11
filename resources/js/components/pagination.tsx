import { Button } from './ui/button';

interface PaginationProps {
    className?: string;
    currentPage: number;
    totalItems: number;
    itemsPerPage: number;
    onPageChange: (page: number) => void;
}

export default function Pagination({ className = '', currentPage, totalItems, itemsPerPage, onPageChange }: PaginationProps) {
    const totalPages = Math.ceil(totalItems / itemsPerPage);

    if (totalPages <= 1) {
        return null;
    }

    const handlePrevious = () => {
        if (currentPage > 1) {
            onPageChange(currentPage - 1);
        }
    };

    const handleNext = () => {
        if (currentPage < totalPages) {
            onPageChange(currentPage + 1);
        }
    };

    const pageNumbers = Array.from({ length: totalPages }, (_, i) => i + 1);

    return (
        <nav className={className}>
            <ul className="inline-flex items-center -space-x-px text-sm">
                <li>
                    <Button variant="outline" className="ml-0 rounded-l-lg" onClick={handlePrevious} disabled={currentPage === 1}>
                        Previous
                    </Button>
                </li>
                {pageNumbers.map((page) => (
                    <li key={page}>
                        <Button variant={currentPage === page ? 'default' : 'outline'} className="rounded-none" onClick={() => onPageChange(page)}>
                            {page}
                        </Button>
                    </li>
                ))}
                <li>
                    <Button variant="outline" className="rounded-r-lg" onClick={handleNext} disabled={currentPage === totalPages}>
                        Next
                    </Button>
                </li>
            </ul>
        </nav>
    );
}
