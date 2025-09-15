import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function numberItemOnPage(currentPage : number, sizePage :number) {
    const offset = (currentPage- 1) * sizePage;
    return function(index : number) :number {
        return offset + index + 1
    }
}
