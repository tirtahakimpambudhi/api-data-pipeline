import { ReactNode } from 'react';

export interface ColumnDefinition<T> {
    header: string;
    accessor?: keyof T;
    render?: (item: T, index: number) => ReactNode;
    align?: 'left' | 'center' | 'right';
}

interface DataTableProps<T> {
    data: T[];
    columns: ColumnDefinition<T>[];
}

export default function DataTable<T extends { id: number | string }>({ data, columns }: DataTableProps<T>) {
    if (!data || data.length === 0) {
        return <div className="p-4 text-center text-gray-500">No data available.</div>;
    }

    return (
        <div className="overflow-x-auto rounded-lg border border-border bg-card">
            <table className="min-w-full divide-y divide-border">
                <thead className="bg-muted">
                    <tr>
                        {columns.map((column, index) => (
                            <th
                                key={index}
                                scope="col"
                                className={[
                                    'px-6 py-3 text-xs font-medium tracking-wider text-muted-foreground uppercase',
                                    column.align === 'center' ? 'text-center' : '',
                                    column.align === 'right' ? 'text-right' : 'text-left',
                                ].join(' ')}
                            >
                                {column.header}
                            </th>
                        ))}
                    </tr>
                </thead>

                <tbody className="divide-y divide-border bg-card">
                    {data.map((item, index) => (
                        <tr key={item.id} className="transition-colors hover:bg-muted/60">
                            {columns.map((column, colIndex) => (
                                <td
                                    key={colIndex}
                                    className={[
                                        'px-6 py-4 align-middle text-sm whitespace-nowrap',
                                        column.align === 'center' ? 'text-center' : '',
                                        column.align === 'right' ? 'text-right' : 'text-left',
                                        column.accessor ? 'text-foreground' : 'text-muted-foreground',
                                    ].join(' ')}
                                >
                                    {column.render ? column.render(item,index) : column.accessor ? String(item[column.accessor]) : ''}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
