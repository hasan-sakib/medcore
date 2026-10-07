import { ReactNode, useEffect } from 'react';

interface ModalProps {
    title: string;
    onClose: () => void;
    children: ReactNode;
}

/** Minimal accessible modal dialog: closes on Escape or backdrop click. */
export function Modal({ title, onClose, children }: ModalProps) {
    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [onClose]);

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4"
            onMouseDown={e => {
                if (e.target === e.currentTarget) onClose();
            }}
        >
            <div role="dialog" aria-modal="true" aria-label={title} className="card w-full max-w-md p-5">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="font-semibold text-gray-900">{title}</h2>
                    <button type="button" onClick={onClose} aria-label="Close" className="text-gray-400 hover:text-gray-600">
                        &times;
                    </button>
                </div>
                {children}
            </div>
        </div>
    );
}
