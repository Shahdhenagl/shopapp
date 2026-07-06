import type { ReactNode } from 'react';
import { LoadingState, EmptyState, ErrorState } from './States';

export interface Column<T> {
  key: string;
  header: ReactNode;
  render: (row: T) => ReactNode;
  className?: string;
}

interface DataTableProps<T> {
  columns: Column<T>[];
  rows: T[];
  rowKey: (row: T) => string;
  loading?: boolean;
  error?: string | null;
  onRetry?: () => void;
  emptyLabel?: string;
  onRowClick?: (row: T) => void;
}

export function DataTable<T>({
  columns,
  rows,
  rowKey,
  loading,
  error,
  onRetry,
  emptyLabel,
  onRowClick,
}: DataTableProps<T>) {
  if (loading) return <LoadingState />;
  if (error) return <ErrorState message={error} onRetry={onRetry} />;
  if (rows.length === 0) return <EmptyState label={emptyLabel} />;

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-start text-sm">
        <thead>
          <tr className="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:text-slate-400">
            {columns.map((col) => (
              <th key={col.key} className={`px-4 py-3 font-medium ${col.className ?? ''}`}>
                {col.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr
              key={rowKey(row)}
              onClick={onRowClick ? () => onRowClick(row) : undefined}
              className={`border-b border-slate-100 transition last:border-0 dark:border-slate-800/60 ${
                onRowClick
                  ? 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/40'
                  : ''
              }`}
            >
              {columns.map((col) => (
                <td key={col.key} className={`px-4 py-3 ${col.className ?? ''}`}>
                  {col.render(row)}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
