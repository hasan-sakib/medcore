import { Link } from '@inertiajs/react';
import type { PaginatedResource } from '@/types';

interface Props<T> {
    data: PaginatedResource<T>;
    className?: string;
}

export function Pagination<T>({ data, className = '' }: Props<T>) {
    if (data.last_page <= 1) return null;

    return (
        <nav className={`flex items-center justify-between ${className}`} aria-label="Pagination">
            <p className="text-sm text-gray-600">
                Showing <span className="font-medium">{data.from ?? 0}</span>
                {' '}to <span className="font-medium">{data.to ?? 0}</span>
                {' '}of <span className="font-medium">{data.total}</span> results
            </p>
            <div className="flex gap-1">
                {data.links.map((link, i) => {
                    if (link.url === null) {
                        return (
                            <span
                                key={i}
                                className="px-3 py-1 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed"
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        );
                    }
                    return (
                        <Link
                            key={i}
                            href={link.url}
                            className={`px-3 py-1 text-sm border rounded transition-colors ${
                                link.active
                                    ? 'bg-primary-600 text-white border-primary-600'
                                    : 'text-gray-700 border-gray-200 hover:bg-gray-50'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    );
                })}
            </div>
        </nav>
    );
}
