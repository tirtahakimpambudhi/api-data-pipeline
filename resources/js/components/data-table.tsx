import { ReactNode } from 'react';

export interface ColumnDefinition<T> {
    header: string;
    accessor?: keyof T;
    render?: (item: T) => ReactNode;
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
        <div className="overflow-x-auto rounded-lg border border-gray-200">
            <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        {columns.map((column, index) => (
                            <th
                                key={index}
                                scope="col"
                                className={`px-6 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase ${column.align === 'center' ? 'text-center' : ''} ${column.align === 'right' ? 'text-right' : 'text-left'} `}
                            >
                                {column.header}
                            </th>
                        ))}
                    </tr>
                </thead>

                <tbody className="divide-y divide-gray-200 bg-white">
                    {data.map((item) => (
                        <tr key={item.id} className="hover:bg-gray-50">
                            {columns.map((column, colIndex) => (
                                <td
                                    key={colIndex}
                                    className={`px-6 py-4 align-middle text-sm whitespace-nowrap ${column.align === 'center' ? 'text-center' : ''} ${column.align === 'right' ? 'text-right' : 'text-left'} ${column.accessor ? 'text-gray-900' : 'text-gray-500'} `}
                                >
                                    {column.render ? column.render(item) : column.accessor ? String(item[column.accessor]) : ''}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
