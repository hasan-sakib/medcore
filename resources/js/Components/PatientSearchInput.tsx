import { useState, useEffect, useRef, useCallback } from 'react';
import { router } from '@inertiajs/react';
import type { Patient } from '@/types';

interface Props {
    onSelect: (patient: Patient) => void;
    placeholder?: string;
    className?: string;
}

function useDebounce(value: string, delay: number) {
    const [debounced, setDebounced] = useState(value);
    useEffect(() => {
        const t = setTimeout(() => setDebounced(value), delay);
        return () => clearTimeout(t);
    }, [value, delay]);
    return debounced;
}

export function PatientSearchInput({ onSelect, placeholder = 'Search patients…', className = '' }: Props) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<Patient[]>([]);
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);
    const debouncedQuery = useDebounce(query, 300);
    const abortRef = useRef<AbortController | null>(null);
    const containerRef = useRef<HTMLDivElement>(null);

    const search = useCallback(async (term: string) => {
        if (!term.trim()) { setResults([]); setOpen(false); return; }

        abortRef.current?.abort();
        abortRef.current = new AbortController();
        setLoading(true);

        try {
            const res = await fetch(`/patients?q=${encodeURIComponent(term)}&json=1`, {
                signal: abortRef.current.signal,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            setResults(data.data ?? data ?? []);
            setOpen(true);
        } catch {
            // aborted or network error — ignore
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => { search(debouncedQuery); }, [debouncedQuery, search]);

    useEffect(() => {
        const handler = (e: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    const handleSelect = (patient: Patient) => {
        setQuery(`${patient.first_name} ${patient.last_name} (${patient.mrn})`);
        setOpen(false);
        onSelect(patient);
    };

    return (
        <div ref={containerRef} className={`relative ${className}`}>
            <input
                type="text"
                value={query}
                onChange={e => setQuery(e.target.value)}
                placeholder={placeholder}
                className="w-full rounded border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                aria-autocomplete="list"
                aria-expanded={open}
            />
            {loading && (
                <div className="absolute right-3 top-2.5 h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-primary-500" />
            )}
            {open && results.length > 0 && (
                <ul className="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg text-sm">
                    {results.map(p => (
                        <li key={p.id}>
                            <button
                                type="button"
                                className="w-full px-3 py-2 text-left hover:bg-gray-50 flex justify-between items-center"
                                onMouseDown={() => handleSelect(p)}
                            >
                                <span>
                                    <span className="font-medium">{p.first_name} {p.last_name}</span>
                                    <span className="ml-2 text-gray-500 text-xs">{p.mrn}</span>
                                </span>
                                <span className="text-xs text-gray-400">{p.date_of_birth}</span>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            {open && !loading && results.length === 0 && query.trim() && (
                <div className="absolute z-50 mt-1 w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-500 shadow-lg">
                    No patients found
                </div>
            )}
        </div>
    );
}
